<?php

namespace App\Controllers;

use App\Controller;
use App\Database;
use App\models\Rekening;
use App\models\Transaksi;
use App\Route;

class Databases extends Controller
{
  public function __construct()
  {
    parent::__construct();
    if (!CheckUser()) {
      showAlert('Akses Ditolak', 'warning');
      Route::Redirect('/Auth/Logout');
      exit;
    }
  }
  public function index()
  {
    function CSRFToken($valid)
    {
      if (!$valid) {
        showAlert('Token CSRF tidak valid atau sudah kedaluwarsa. Silakan coba lagi.', 'danger');
        Route::Referer('/Databases');
        exit;
      }
    }
    if (isset($_GET['export'])) {
      CSRFToken($this->validateCSRFToken($_GET['csrf_token'] ?? ''));
      return $this->exportCSV();
    } else if (!empty($_FILES['attachment']['name'])) {
      CSRFToken($this->validateCSRFToken($_GET['csrf_token'] ?? ''));
      return $this->importCSV();
    } else if (isset($_POST['dbVersion']) && !empty($_POST['dbVersion'])) {
      CSRFToken($this->validateCSRFToken($_GET['csrf_token'] ?? ''));
      return $this->changeDatabase($_POST['dbVersion']);
    } else if (isset($_GET['sqllite']) && $_GET['sqllite'] === 'download') {
      CSRFToken($this->validateCSRFToken($_GET['csrf_token'] ?? ''));
      return $this->downloadSQLite();
    } else if (isset($_GET['sqllite']) && $_GET['sqllite'] === 'reinitialize') {
      CSRFToken($this->validateCSRFToken($_GET['csrf_token'] ?? ''));
      return $this->reinitializeSQLite();
    }
    $data['csrf_token'] = $this->generateCSRFToken();
    $data['dbVersions'] =  /* need all sqlite   files in .databases folder */ array_map(function ($file) {
      return pathinfo($file, PATHINFO_FILENAME);
    }, glob(dirname(DATABASES['default']->path) . '/*.sqlite'));
    // }, glob(dirname(__DIR__, 2) . '/.databases/*.sqlite'));
    $data['title'] = 'Ekspor & Impor Database';
    $data['subTitle'] = '<i class="fas fa-book"></i> Ekspor & Impor Database <i class="fas fa-database"></i>';
    // Never cache this page: it embeds a single-use, 5-minute CSRF token
    // directly in its links/form action. A cached copy would keep serving an
    // already-expired or already-consumed token, making every action fail
    // with "Token CSRF tidak valid" until the user manually hard-refreshes.
    setCacheControl(0);
    $data['view'] = 'database';
    $data['top-left-view'] = 'components/header';
    $data['right-bottom-view'] = 'components/navbar';
    $this->view('templates/template', $data);
  }
  private function exportCSV()
  {
    header("Access-Control-Allow-Methods: POST,GET");
    header("Access-Control-Allow-Headers: Content-Type");
    setCacheControl(0);

    $model = new Transaksi();
    $data = $model->getInRange($_GET['startDate'] ?? date('Y-m-01'), $_GET['endDate'] ?? date('Y-m-d'));

    $filename = "uangku_export_" . date('YmdHis') . ".csv";

    // Standard CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    // 1. THE EXCEL FIX: Output UTF-8 BOM to force Excel to recognize UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    // 2. THE DELIMITER FIX: Explicitly tell Excel to use a comma
    // Some versions of Excel need this on the very first line
    fwrite($output, "sep=,\n");

    if (!empty($data)) {
      // Output Headers
      fputcsv($output, array_keys($data[0]), escape: "");
      // Output Rows
      foreach ($data as $row) {
        fputcsv($output, $row, escape: "");
      }
    }
    fclose($output);
    exit;
  }
  private function importCSV()
  {

    header("Access-Control-Allow-Methods: POST,GET");
    header("Access-Control-Allow-Headers: Content-Type");
    setCacheControl(0);

    $model = new Transaksi();
    $filePath = $_FILES['attachment']['tmp_name'];

    $file = fopen($filePath, 'r');

    // 1. Detect delimiter by frequency: whichever of , ; \t appears most in
    // the header line wins. A plain "contains ';'" check breaks the moment
    // any export (mobile spreadsheet apps, other locales, etc.) uses a
    // different delimiter than Excel-on-Windows' European/Indonesian ';'.
    $firstLine = fgets($file);
    rewind($file);
    $delimiterCounts = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
    arsort($delimiterCounts);
    $delimiter = $delimiterCounts && reset($delimiterCounts) > 0 ? array_key_first($delimiterCounts) : ',';

    // 2. Skip BOM if present
    $bom = fread($file, 3);
    if ($bom !== "\xEF\xBB\xBF") rewind($file);

    // 3. Skip the 'sep=' hint line if present (Excel-specific convention;
    // most other CSV producers, e.g. mobile spreadsheet apps, omit it)
    $afterBomPos = ftell($file);
    $testLine = fgets($file);
    if ($testLine === false || stripos($testLine, 'sep=') === false) {
      fseek($file, $afterBomPos);
    }

    // Now read the header with the detected delimiter
    $header = fgetcsv($file, 0, $delimiter, '"', '');

    // Clean invisible characters/whitespace from header keys (Excel/mobile-app artifact)
    $header = array_map(function ($h) {
      return preg_replace('/[^a-zA-Z0-9_]/', '', trim($h));
    }, $header);

    $required_columns = ['id', 'jenis_transaksi', 'harta', 'barang', 'rekening_sumber', 'rekening_masuk', 'nominal', 'nominal_asing', 'kuantitas', 'penyusutan_bunga', 'rutin', 'kelompok', 'tanggal', 'relasi_transaksi', 'attachment', 'keterangan'];

    // Validate
    foreach ($required_columns as $col) {
      if (!in_array($col, $header)) {
        fclose($file);
        showAlert("Format tidak valid. Kolom '{$col}' hilang.", 'danger');
        Route::Referer('/Databases');
        exit;
      }
    }
    // 4. Process Rows
    $successCount = 0;
    $required_keys = array_flip($required_columns); // Flip for high-speed key checking
    try {
      // START TRANSACTION HERE
      // This stops SQLite from writing to disk for every single row
      $model->beginTransaction();

      $decimalCommaColumns = ['nominal', 'nominal_asing', 'kuantitas', 'penyusutan_bunga'];
      while ($row = fgetcsv($file, 0, $delimiter, '"', '')) {
        if ($successCount >= 15) break; // limit to 15 updates per import to prevent server overload
        // Trim stray whitespace/line-ending remnants some mobile apps leave on fields
        $row = array_map('trim', $row);
        if (count($header) === count($row)) {
          $rowData = array_combine($header, $row);
          $sanitizedData = array_intersect_key($rowData, $required_keys);

          // Locale number formatting: spreadsheet apps set to a ',' decimal
          // locale (common outside the US) write "1234,56" and, to avoid
          // colliding with that comma, export with ';' as the delimiter. If
          // we detected a non-comma delimiter, numeric fields are safe to
          // normalize back to "1234.56" for PHP/SQLite.
          if ($delimiter !== ',') {
            foreach ($decimalCommaColumns as $col) {
              if (isset($sanitizedData[$col]) && $sanitizedData[$col] !== '') {
                $sanitizedData[$col] = str_replace(',', '.', $sanitizedData[$col]);
              }
            }
          }

          // Date Formatting
          if (!empty($sanitizedData['tanggal'])) {
            $parsedDate = $this->parseFlexibleDate($sanitizedData['tanggal']);
            if ($parsedDate === null) {
              throw new \Exception("Format tanggal tidak dikenali pada baris ID {$sanitizedData['id']}: '{$sanitizedData['tanggal']}'");
            }
            $sanitizedData['tanggal'] = $parsedDate;
          }

          $targetId = $sanitizedData['id'] ?? null;

          if ($targetId) {
            $updateData = $sanitizedData;
            unset($updateData['id']);

            // This now happens in memory, making it lightning fast
            $model->updateTransaksi($updateData, ['id' => $targetId]);
            $successCount++;
          }
        }
      }
      // COMMIT EVERYTHING AT ONCE
      $model->commit();

      if ($successCount > 0) {
        if ($successCount >= 15)
          showAlert("Hanya Mendukung Maksimal 15 Data Per Import", 'Warning');
        showAlert("Berhasil memperbarui {$successCount} data transaksi.", 'success');
      } else {
        showAlert("Tidak ada data yang diperbarui. Pastikan ID cocok dengan transaksi yang telah ada.", 'warning');
      }
    } catch (\Exception $e) {
      // ROLLBACK IF SOMETHING FAILS
      $model->rollback();
      showAlert("Gagal: " . $e->getMessage(), 'danger');
    }
    fclose($file);
    Route::Referer('/Databases');
    exit;
  }

  /**
   * Parses a date value that may come from any spreadsheet app/locale, e.g.
   * "2026-07-30" (ISO, the database's native format), "30/07/2026" (Windows
   * Excel with Indonesian regional settings), or "7/30/2026" (mobile apps,
   * which often default to US M/D/Y regardless of device region). Field
   * order is disambiguated by value, not by separator character: a segment
   * greater than 12 can only be a day, never a month, which resolves the
   * common cross-platform mismatch unambiguously. When both segments are
   * <= 12 and genuinely ambiguous, defaults to D/M/Y to match this app's
   * Indonesian-locale convention used everywhere else.
   * Returns 'Y-m-d', or null if the value can't be parsed as a valid date.
   */
  private function parseFlexibleDate(string $value): ?string
  {
    $value = trim($value);
    if ($value === '') return null;

    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
      return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $value : null;
    }

    if (preg_match('#^(\d{1,4})[/\-](\d{1,2})[/\-](\d{1,4})$#', $value, $m)) {
      [, $a, $b, $c] = $m;
      if (strlen($a) === 4) {
        [$year, $month, $day] = [(int) $a, (int) $b, (int) $c];
      } else {
        $a = (int) $a;
        $b = (int) $b;
        $year = (int) $c;
        if ($a > 12) {
          [$day, $month] = [$a, $b];
        } elseif ($b > 12) {
          [$month, $day] = [$a, $b];
        } else {
          [$day, $month] = [$a, $b];
        }
      }
      return checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
    }

    return null;
  }

  private function changeDatabase($version)
  {
    /**
     * Helper to reduce repetition
     */
    function redirectWithAlert($message, $type)
    {
      showAlert($message, $type);
      Route::Referer('/Databases');
      exit;
    }
    $version = str_replace(['..', '/', '\\'], '', $version);
    $sqliteName = $version . '.sqlite';
    $currentDb = DATABASES['default'];

    // 1. Validation: Prevent redundant switching
    if ($sqliteName === basename($currentDb->name)) {
      redirectWithAlert("Database version '{$version}' is already in use.", 'info');
    }

    // 2. Backup Logic: Only attempt if the source exists
    if (file_exists($currentDb->path)) {
      $backupPath = dirname($currentDb->path) . '/backup_' . date('YmdHis') . '_' . basename($currentDb->path);
      copy($currentDb->path, $backupPath);
    }

    // 3. Deployment Logic: Move the new file to the target path
    if (file_exists(dirname($currentDb->path) . '/' . $sqliteName)) {
      copy(dirname($currentDb->path) . '/' . $sqliteName, $currentDb->path);
      redirectWithAlert("Database switched to version: {$version}", 'success');
    }

    // 4. Fallback: If the target file was missing
    redirectWithAlert('Requested database file not found.', 'danger');
  }

  private function downloadSQLite()
  {
    $filePath = DATABASES['default']->path;
    if (file_exists($filePath)) {
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
      header('Expires: 0');
      header('Cache-Control: must-revalidate');
      header('Pragma: public');
      header('Content-Length: ' . filesize($filePath));
      readfile($filePath);
      exit;
    } else {
      showAlert('File tidak ditemukan', 'danger');
      Route::Referer('/Databases');
      exit;
    }
  }
  private function reinitializeSQLite()
  {
    $filePath = DATABASES['default']->path;
    // Backup current database before reinitialization
    $backupPath = dirname($filePath) . '/backup_' . date('YmdHis') . '_' . basename($filePath);
    if (file_exists($filePath)) {
      copy($filePath, $backupPath);
    }
    try {
      new Database()->reinitialize();
      showAlert('Database berhasil di-reinitialize. Saldo awal telah diperbarui.', 'success');
    } catch (\Exception $e) {
      showAlert('Gagal melakukan reinitialization: ' . $e->getMessage(), 'danger');
    }
    // This function is now handled directly in the database() method for <simplicity>    </simplicity>
  }
}
