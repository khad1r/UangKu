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
  public function pencarian()
  {
    $id_transaksi = $_GET['id'] ?? '';
    sanitize_input($id_transaksi);
    $model = new Transaksi();
    $transaksi = $model->getById($id_transaksi);
    if (empty($transaksi)) {
      showAlert('Data tidak ditemukan', 'danger');
      Route::Redirect('/Transaction');
      exit;
    }
    $data['title'] = "Pencarian Transaksi";
    $data['subTitle'] = "<i class='fas fa-wallet'></i> <strong><u>Pencarian Transaksi</u></strong> <i class='fas fa-magnifying-glass'></i>";
    $data['transaksi'] = $transaksi;
    setCacheControl(259200/* 3 Day Expired */);
    $data['view'] = 'transaction/pencarian';
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
          : $rekeningModel->rangeFlowGraph($_POST['startDate'], $_POST['endDate']);
        // : $rekeningModel->allCashFlowGraph($_POST['startDate'], $_POST['endDate']);

        $resp['cashFlow'][4] = array_map(fn($row) => [
          new \DateTime($row['tanggal'], new \DateTimeZone('UTC'))->getTimestamp() * 1000,
          $row['saldo']
        ], $cashFlow);
        if (isset($_POST['id_rekening'])) {
          if ($_POST['rekening_is_harta'] == 1) {
            $resp['saldo'] = $rekeningModel->getSaldoHarta($_POST['id_rekening'])['saldo'];
          } else {
            $resp['saldo'] = $rekeningModel->getSaldoRekening($_POST['id_rekening'])['saldo'];
          }
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
}
