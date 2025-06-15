<?php

namespace App\Controllers;

use App\Controller;
use App\models\Rekening as ModelsRekening;
use App\Route;

class Rekening extends Controller
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
    $data['title'] = 'Daftar Rekening';
    $data['subTitle'] = '<i class="fas fa-wallet"></i> <strong><u>Rekening</u></strong> <i class="fas fa-money-bill-wave"></i>';
    setCacheControl(259200/* 3 Day Expired */);
    $data['view'] = 'rekening/list';
    $data['right-bottom-view'] = 'components/navbar';
    $this->view('templates/template', $data);
  }
  public function add()
  {
    $data['title'] = 'Tambah Rekening';
    $data['subTitle'] = '<i class="fas fa-wallet"></i> <strong><u>Rekening</u></strong> <i class="fas fa-money-bill-wave"></i>';
    if (!empty($_POST)) {
      try {
        // if (!csrf_security('rekening-form', validate: $_POST)) return;
        $_POST['harta'] = isset($_POST['harta']);
        $_POST['aktif'] = isset($_POST['aktif']);
        $required_field = ["nama_rekening", "tgl_dibuat"];
        if (!$_POST['aktif']) $required_field[] = 'tgl_ditutup';

        sanitize_input($_POST);
        validate_required_input($_POST, $required_field);
        $insert = [
          'nama'          => $_POST['nama_rekening'],
          'no_asli'       => $_POST['no_asli'] ?? null,
          'nominal_asing' => $_POST['nominal_asing'] ?? null,
          'tgl_dibuat'    => $_POST['tgl_dibuat'],
          'aktif'         => $_POST['aktif'] ?? true,
          'harta'         => $_POST['harta'] ?? true,
          'tgl_ditutup'   => $_POST['aktif'] ? $_POST['tgl_ditutup'] : null,
          'keterangan'    => $_POST['keterangan'] ?? null,
        ];
        if (new ModelsRekening()->insertRekening($insert) > 0) {
          showAlert("Penambahan Rekening Berhasil", 'success');
          Route::Redirect('/Rekening');
          return;
        } else {
          showAlert('Operasi Gagal', 'danger');
        }
      } catch (\Exception $e) {
        showAlert($e->getMessage(), 'danger');
      }
    }
    setCacheControl(259200/* 3 Day Expired */);
    $data['view'] = 'rekening/add';
    $data['right-bottom-view'] = 'components/navbar';
    $this->view('templates/template', $data);
  }
  public function edit($id_rekening = '')
  {
    $data['title'] = 'Edit Rekening';
    $data['subTitle'] = '<i class="fas fa-wallet"></i> <strong><u>Rekening</u></strong> <i class="fas fa-money-bill-wave"></i>';
    sanitize_input($id_rekening);
    $model = new ModelsRekening();
    $oldData = $model->getById($id_rekening);
    if (empty($oldData)) {
      showAlert('Data tidak ditemukan', 'danger');
      Route::Redirect('/Rekening');
      exit;
    }
    if (!empty($_POST)) {
      try {
        // if (!csrf_security('rekening-form', validate: $_POST)) return;
        if ($_POST['id'] === 'DELETE') {
          /* Might Not Needed so no Rekening being deleted */
          // if ($model->deleteRekening($id_rekening) > 0) {
          //   showAlert("Penghapusan Rekening Berhasil", 'warning');
          //   Route::Redirect('/Rekening');
          //   return;
          // }
        } else {
          $_POST['aktif'] = isset($_POST['aktif']);
          $_POST['harta'] = isset($_POST['harta']);
          $required_field = ["nama_rekening", "tgl_dibuat"];
          if (!$_POST['aktif']) $required_field[] = 'tgl_ditutup';
          sanitize_input($_POST);
          validate_required_input($_POST, $required_field);
          $update = [
            'nama'          => $_POST['nama_rekening'],
            'no_asli'       => $_POST['no_asli'] ?? null,
            'nominal_asing' => $_POST['nominal_asing'] ?? null,
            'tgl_dibuat'    => $_POST['tgl_dibuat'],
            'aktif'         => $_POST['aktif'] ?? true,
            'harta'         => $_POST['harta'] ?? true,
            'tgl_ditutup'   => $_POST['aktif'] ? $_POST['tgl_ditutup'] : null,
            'keterangan'    => $_POST['keterangan'] ?? null,
          ];
          if ($model->updateRekening($update, ['id' => $id_rekening]) > 0) {
            showAlert("Perubahan Rekening Berhasil", 'success');
            Route::Redirect('/Rekening');
            return;
          }
        }
        showAlert('Operasi Gagal', 'danger');
      } catch (\Exception $e) {
        showAlert($e->getMessage(), 'danger');
      }
    }
    $data['oldData'] = $oldData;
    setCacheControl(259200/* 3 Day Expired */);
    $data['view'] = 'rekening/edit';
    $data['right-bottom-view'] = 'components/navbar';
    $this->view('templates/template', $data);
  }
  public function args()
  {
    $rate_limit_interval = 60; // 15 detik
    $rate_limit_max_request = 30; // 10 request
    header("Access-Control-Allow-Methods: POST,GET");
    header("Access-Control-Allow-Headers: Content-Type");
    setCacheControl(0);

    validateApi($rate_limit_max_request, $rate_limit_interval);

    http_response_code(200);
    try {
      $model = new ModelsRekening();
      if (isset($_GET['datatable']))
        $resp = $model->datatable($_POST);
      else if (isset($_GET['graph'])) {
        foreach ($model->CashFlowGraph($_POST['startDate'], $_POST['endDate']) as $row)
          $resp[$row['rekening']][] = [
            new \DateTime($row['tanggal'], new \DateTimeZone('UTC'))->getTimestamp() * 1000,
            $row['saldo_asing'] > 0 ? $row['saldo_asing'] : $row['saldo']
          ];
        $resp['all'] = array_map(fn($row) => [
          new \DateTime($row['tanggal'], new \DateTimeZone('UTC'))->getTimestamp() * 1000,
          $row['saldo']
        ], $model->allCashFlowGraph($_POST['startDate'], $_POST['endDate']));
        $resp['saldo'] = $model->getSaldoEfektif()['saldo'];
      };
    } catch (\Throwable $th) {
      http_response_code(500);
      $resp = ['error' => $th->getMessage()];
    }
    header('Content-Type: application/json');
    echo json_encode($resp);
  }
}
