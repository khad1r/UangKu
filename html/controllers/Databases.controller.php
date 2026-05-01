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
    setCacheControl(259200/* 3 Day Expired */);
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
    $data = $model->getAll();
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
      fputcsv($output, array_keys($data[0]));
      // Output Rows
      foreach ($data as $row) {
        fputcsv($output, $row);
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

    // 1. Detect Delimiter (Excel often uses ; in Europe/Indonesia)
    $fileHandle = fopen($filePath, 'r');
    $firstLine = fgets($fileHandle);
    $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';

    // 2. Handle the "sep=," line if it exists
    if (trim($firstLine) === 'sep=,' || trim($firstLine) === 'sep=;') {
      // If the first line is the separator hint, the header is actually on line 2
      $headerLine = fgets($fileHandle);
    } else {
      // Otherwise, the first line we already read IS the header
      $headerLine = $firstLine;
    }
    rewind($fileHandle); // Reset to start for proper processing

    // 3. Clean the Header (Remove BOM and special characters)
    // We read the file again using the detected logic
    $file = fopen($filePath, 'r');

    // Skip BOM if present
    $bom = fread($file, 3);
    if ($bom !== "\xEF\xBB\xBF") rewind($file);

    // Skip the 'sep=' line if it exists
    $testSep = fgets($file);
    if (stripos($testSep, 'sep=') === false) {
      rewind($file);
      if ($bom === "\xEF\xBB\xBF") fseek($file, 3);
    }

    // Now read the header with the detected delimiter
    $header = fgetcsv($file, 0, $delimiter);

    // Clean invisible characters from header keys (Excel artifact)
    $header = array_map(function ($h) {
      return preg_replace('/[^a-zA-Z0-9_]/', '', $h);
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
      while ($row = fgetcsv($file, 0, $delimiter)) {
        if (count($header) === count($row)) {
          $rowData = array_combine($header, $row);
          // UNSET/FILTER: Keep only what is in $required_columns
          $sanitizedData = array_intersect_key($rowData, $required_keys);

          // B. DATE FORMATTING (yyyy-mm-dd)
          // B. DATE FORMATTING (yyyy-mm-dd)
          if (!empty($sanitizedData['tanggal'])) {
            $date = new \DateTime(str_replace('/', '-', $sanitizedData['tanggal']));
            $sanitizedData['tanggal'] = $date->format('Y-m-d');
          }
          $targetId = $sanitizedData['id'] ?? null;

          if ($targetId) {
            // Remove ID from the update data so you aren't trying to UPDATE the ID column
            $updateData = $sanitizedData;
            unset($updateData['id']);

            if ($model->updateTransaksi($updateData, ['id' => $targetId]) > 0) {
              $successCount++;
            }
          }
        }
      }

      if ($successCount > 0) {
        showAlert("Berhasil memperbarui {$successCount} data transaksi.", 'success');
      } else {
        showAlert("Tidak ada data yang diperbarui. Pastikan ID cocok.", 'warning');
      }
    } catch (\Exception $e) {
      showAlert("Gagal: " . $e->getMessage(), 'danger');
    }
    fclose($file);
    Route::Referer('/Databases');
    exit;
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
