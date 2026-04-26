<?php

namespace App\Controllers;

use App\Controller;
use App\models\Rekening;
use App\models\Transaksi;
use App\Route;

class Transaction extends Controller
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
    $data['title'] = 'Daftar Transaksi';
    $data['subTitle'] = '<i class="fas fa-book"></i> Buku Transaksi <i class="fas fa-hand-holding-usd"></i>';
    setCacheControl(259200/* 3 Day Expired */);
    $data['view'] = 'transaction/transaction';
    $data['top-left-view'] = 'components/header';
    $data['right-bottom-view'] = 'components/navbar';
    $this->view('templates/template', $data);
  }
  public function rekening($id_rekening = '')
  {
    sanitize_input($id_rekening);
    $model = new Rekening();
    $rekening = $model->getById($id_rekening);
    if (empty($rekening)) {
      showAlert('Data tidak ditemukan', 'danger');
      Route::Redirect('/Rekening');
      exit;
    }
    $data['title'] = "Mutasi Rekening {$rekening['nama']}";
    $data['subTitle'] = "<i class='fas fa-wallet'></i> <strong><u>Mutasi Rekening {$rekening['nama']}</u></strong> <i class='fas fa-money-bill-wave'></i>";
    $data['rekening'] = $rekening;
    setCacheControl(259200/* 3 Day Expired */);
    $data['view'] = 'transaction/rekening';
    $data['top-left-view'] = 'components/header';
    $data['right-bottom-view'] = 'components/navbar';
    $this->view('templates/template', $data);
  }

  public function database()
  {
    if (isset($_GET['export'])) {
      return $this->exportCSV();
    } else if (!empty($_FILES['attachment']['name'])) {
      return $this->importCSV();
    }
    $data['title'] = 'Ekspor & Impor CSV';
    $data['subTitle'] = '<i class="fas fa-book"></i> Ekspor & Impor CSV <i class="fas fa-file-csv"></i>';
    setCacheControl(259200/* 3 Day Expired */);
    $data['view'] = 'transaction/database';
    $data['top-left-view'] = 'components/header';
    $data['right-bottom-view'] = 'components/navbar';
    $this->view('templates/template', $data);
  }
  public function datatable()
  {
    $rate_limit_interval = 60; // 15 detik
    $rate_limit_max_request = 30; // 10 request
    header("Access-Control-Allow-Methods: POST,GET");
    header("Access-Control-Allow-Headers: Content-Type");
    setCacheControl(0);

    validateApi($rate_limit_max_request, $rate_limit_interval);

    http_response_code(200);
    try {
      $model = new Transaksi();
      $resp = $model->datatable($_POST);
      if ($_POST['queryGraph'] == 'true') {
        $cashFlow = (isset($_POST['id_rekening']))
          ? $model->rekeningCashFlowGraph($_POST['startDate'], $_POST['endDate'], $_POST['id_rekening'])
          : $model->cashFlowGraph($_POST['startDate'], $_POST['endDate']);
        $ref = [
          "Pemasukan-Rutin" => 0,
          "Pemasukan-NonRutin" => 1,
          "Pengeluaran-Rutin" => 2,
          "Pengeluaran-NonRutin" => 3,
        ];
        foreach ($cashFlow as $row) {
          $jenis = $row['jenis_transaksi']; // 'Pemasukan' or 'Pengeluaran'
          $rutin = $row['rutin'] == 1 ? 'Rutin' : 'NonRutin'; // Convert to readable string
          $key = "{$jenis}-{$rutin}";
          $resp['cashFlow'][$ref[$key]][] = [
            new \DateTime($row['tanggal'], new \DateTimeZone('UTC'))->getTimestamp() * 1000,
            $row['trans']
          ];
          if ($row['jenis_transaksi'] == 'Pemasukan') $resp['cashIn'] += $row['trans'];
          elseif ($row['jenis_transaksi'] == 'Pengeluaran') $resp['cashOut'] += $row['trans'];
        }
        $resp['comps'] = (isset($_POST['id_rekening']))
          ? $model->rekeningCompsGraph($_POST['startDate'], $_POST['endDate'], $_POST['id_rekening'])
          : $model->compsGraph($_POST['startDate'], $_POST['endDate']);
        $rekeningModel = new Rekening();
        $cashFlow = (isset($_POST['id_rekening']))
          ? $rekeningModel->rekeningAllCashFlowGraph($_POST['startDate'], $_POST['endDate'], $_POST['id_rekening'])
          : $rekeningModel->allCashFlowGraph($_POST['startDate'], $_POST['endDate']);

        $resp['cashFlow'][4] = array_map(fn($row) => [
          new \DateTime($row['tanggal'], new \DateTimeZone('UTC'))->getTimestamp() * 1000,
          $row['saldo']
        ], $cashFlow);
        if (isset($_POST['id_rekening'])) {
          $resp['saldo'] = $rekeningModel->getSaldoRekening($_POST['id_rekening'])['saldo'];
        } else {
          $resp['saldo'] = $rekeningModel->getSaldoEfektif()['saldo'];
        }
      }
    } catch (\Throwable $th) {
      http_response_code(500);
      $resp = ['error' => $th->getMessage()];
    }
    header('Content-Type: application/json');
    echo json_encode($resp);
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
        Route::Referer('/Transaction/database');
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
    Route::Referer('/Transaction/database');
    exit;
  }
}
