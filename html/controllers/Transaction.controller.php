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
        $cashFlow = $model->cashFlowGraph($_POST['startDate'], $_POST['endDate']);
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
        $rekeningModel = new Rekening();
        $cashFlow = $rekeningModel->allCashFlowGraph($_POST['startDate'], $_POST['endDate']);
        $resp['cashFlow'][4] = array_map(fn($row) => [
          new \DateTime($row['tanggal'], new \DateTimeZone('UTC'))->getTimestamp() * 1000,
          $row['saldo']
        ], $cashFlow);
        $resp['comps'] = $model->compsGraph($_POST['startDate'], $_POST['endDate']);
        $resp['saldo'] = $rekeningModel->getSaldoEfektif()['saldo'];
      }
    } catch (\Throwable $th) {
      http_response_code(500);
      $resp = ['error' => $th->getMessage()];
    }
    header('Content-Type: application/json');
    echo json_encode($resp);
  }
}
