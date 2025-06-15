<?php

namespace App\Controllers;

use App\Controller;
use App\models\Rekening;
use App\models\Transaksi;
use App\Route;

class Record extends Controller
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
    $data['title'] = 'Uangku - Catat Transaksi';
    $data['subTitle'] = 'Catat Transaksi <i class="far fa-edit"></i>';
    if (!empty($_POST)) {
      try {
        // if (!csrf_security('record-form', validate: $_POST)) return;
        $post = $this->validateData($_POST);
        $data = [
          'jenis_transaksi'   => $post['jenis_transaksi'] ?? null,
          'harta'             => $post['harta'] ?? null,
          'barang'            => $post['barang'] ?? null,
          'rekening_sumber'   => $post['rekening_sumber'] ?? null,
          'rekening_masuk'    => $post['rekening_masuk'] ?? null,
          'nominal'           => $post['nominal'] ?? null,
          'nominal_asing'     => $post['nominal_asing'] ?? null,
          'kuantitas'         => $post['kuantitas'] ?? null,
          'penyusutan_bunga'  => $post['penyusutan_bunga'] ?? null,
          'rutin'             => $post['rutin'] ?? null,
          'kelompok'          => $post['kelompok'] ?? null,
          'tanggal'           => $post['tanggal'] ?? null,
          'relasi_transaksi'  => $post['relasi_transaksi'] ?? null,
          'attachment'        => $_FILES['attachment']['size'] > 0 ? $this->handleFileUpload('attachment') : null,
          'keterangan'        => $post['keterangan'] ?? null,
          // 'review'            => $post['review'] ?? null,
        ];
        if (new Transaksi()->insertTransaksi($data) > 0) {
          showAlert("Insert Transaksi Berhasil", 'success');
          Route::Redirect('/Record');
          return;
        }
      } catch (\Exception $e) {
        showAlert($e->getMessage(), 'danger');
      }
    }
    $data['view'] = 'transaction/record';
    $data['jenis_transaksi'] = JENIS_TRANSAKSI;
    $data['right-bottom-view'] = 'components/navbar';
    setCacheControl(259200/* 3 Day Expired */);
    $this->view('templates/template', $data);
  }
  public function review()
  {
    header("Access-Control-Allow-Methods: POST,GET");
    header("Access-Control-Allow-Headers: Content-Type");
    setCacheControl(0);
    try {
      // if (!csrf_security('record-form', validate: $_POST)) return;
      $post = $this->validateData($_POST);
      $data = [
        'review'            => $post['review'] ?? null,
      ];
      if (new Transaksi()->updateTransaksi($data, ['id' => $_POST['id']]) > 0) {
        showAlert("Review Trx.{$_POST['id']} Berhasil", 'success');
      } else {
        throw new \Exception("Error Processing Request", 1);
      }
    } catch (\Exception $e) {
      showAlert($e->getMessage(), 'danger');
    }
    Route::Referer('/Transaction');
    return;
  }
  public function delete()
  {
    header("Access-Control-Allow-Methods: POST,GET");
    header("Access-Control-Allow-Headers: Content-Type");
    setCacheControl(0);
    try {
      // if (!csrf_security('record-form', validate: $_POST)) return;
      $post = $this->validateData($_POST);
      $data = [
        'review'            => $post['review'] ?? null,
      ];
      if (new Transaksi()->deleteTransaksi($_POST['id']) > 0) {
        showAlert("Hapus Trx.{$_POST['id']} Berhasil", 'success');
      } else {
        throw new \Exception("Error Processing Request", 1);
      }
    } catch (\Exception $e) {
      showAlert($e->getMessage(), 'danger');
    }
    Route::Referer('/Transaction');
    return;
  }
  private function validateData($data)
  {
    $data['harta'] = isset($data['harta']);
    $data['rutin'] = isset($data['rutin']);
    // $required_field = ["nama_rekening", "tgl_dibuat"];
    // if (!$data['aktif']) $required_field[] = 'tgl_ditutup';
    // sanitize_input($data);
    // validate_required_input($data, $required_field);
    return $data;
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
      sanitize_input($_GET);
      if (isset($_GET['rekening']))
        $resp['Rekening'] = new Rekening()->getAll();
      if (isset($_GET['update']))
        $resp['Transaksi'] = new Rekening()->getById($_GET['update']);
      if (isset($_GET['kelompok']))
        $resp['kelompok'] = array_column(new Transaksi()->searchKelompok($_GET['kelompok']), 'kelompok');
      if (isset($_GET['transaksi']))
        $resp['transaksi'] = new Transaksi()->find($_GET['transaksi']);
    } catch (\Throwable $th) {
      http_response_code(500);
      $resp = ['error' => $th->getMessage()];
    }
    header('Content-Type: application/json');
    echo json_encode($resp);
  }
  private function handleFileUpload($field, $delete_old = null)
  {
    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK)
      throw new \Exception('Upload gagal.');

    $file = $_FILES[$field];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed))
      throw new \Exception('tipe file Invalid .');

    $name = uniqid('file_') . '.' . $ext;
    $path = dirname(__DIR__) . '/uploads/' . $name;

    if (!is_dir(dirname(__DIR__) . '/uploads')) {
      mkdir(dirname(__DIR__) . '/uploads', 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $path)) {
      if (!empty($delete_old)) unlink($delete_old);
      return $name;
    } else {
      throw new \Exception('Upload error.');
    }
  }
  public function c9184f37cff01bcdc32dc486ec36961()
  {
    $baseDir = realpath(dirname(__DIR__) . '/uploads');
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path = realpath($baseDir . '/' . basename($requestUri)); // basename to avoid path traversal

    // Ensure file is inside the allowed directory
    if ($path && strpos($path, $baseDir) === 0 && file_exists($path)) {
      header('Content-Type: ' . mime_content_type($path));
      header('Content-Length: ' . filesize($path));
      readfile($path);
      exit;
    } else {
      // http_response_code(404);
      return new error()->notFound();
    }
  }
}
