<?php

namespace App\Controllers;

use App\Controller;
use App\models\Rekening;
use App\models\Transaksi;
use App\Route;
use Http\Discovery\Psr17Factory;
use Mcp\Server;
use Mcp\Server\Transport\StreamableHttpTransport;
use Mcp\Server\Transport\Http\Middleware\DnsRebindingProtectionMiddleware;
use Mcp\Server\Session\FileSessionStore;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Mcp\Schema\Icon;



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
          'attachment'        => $this->hasUploadedFile('attachment') ? $this->handleFileUpload('attachment') : null,
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
    $data['top-left-view'] = 'components/header';
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

  public function attachment($id = null)
  {
    header("Access-Control-Allow-Methods: POST,GET");
    header("Access-Control-Allow-Headers: Content-Type");
    setCacheControl(0);

    $error = null;
    $trans = null;
    $formattedId = '';

    if (empty($id)) {
      $error = "ID Transaksi tidak valid.";
    } else {
      $formattedId = sprintf('%04d', $id);
      $trans = (new Transaksi())->getById($formattedId);

      if (!$trans) {
        $error = "Transaksi #{$formattedId} tidak ditemukan.";
      } else if (($trans['attachment'] ?? '') !== 'uploading') {
        $error = "Lampiran untuk Transaksi #{$formattedId} sudah diunggah atau tidak memerlukan upload.";
      }
    }

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
      || (isset($_POST['ajax']) && $_POST['ajax'] == '1')
      || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));

    if ($isAjax && !empty($error) && !empty($_POST)) {
      http_response_code(400);
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'error' => $error]);
      exit;
    }

    $success = false;
    if (empty($error) && !empty($_POST)) {
      try {
        if (!$this->hasUploadedFile('attachment')) {
          throw new \Exception('File wajib diunggah atau file bermasalah.');
        }

        $fileName = $this->handleFileUpload('attachment');
        if ((new Transaksi())->updateTransaksi(['attachment' => $fileName], ['id' => intval($id)]) > 0) {
          showAlert("Upload Lampiran Berhasil", 'success');
          $success = true;
          if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Upload Lampiran Berhasil']);
            exit;
          }
        } else {
          throw new \Exception("Gagal update data transaksi di database.");
        }
      } catch (\Exception $e) {
        showAlert($e->getMessage(), 'danger');
        $error = $e->getMessage();
        if ($isAjax) {
          http_response_code(400);
          header('Content-Type: application/json');
          echo json_encode(['success' => false, 'error' => $e->getMessage()]);
          exit;
        }
      }
    }

    $this->view('templates/template', [
      'title' => $success ? 'Upload Berhasil' : 'Unggah Lampiran',
      'formattedId' => $formattedId,
      'trans' => $trans,
      'success' => $success,
      'error' => $error,
      'view' => 'transaction/attachment'
    ]);
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
    $resp = [];
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
  private function hasUploadedFile($field): bool
  {
    if (!isset($_FILES[$field])) return false;
    if (is_array($_FILES[$field]['name'])) {
      foreach ($_FILES[$field]['error'] as $i => $err) {
        if ($err === UPLOAD_ERR_OK && ($_FILES[$field]['size'][$i] ?? 0) > 0) {
          return true;
        }
      }
      return false;
    }
    return ($_FILES[$field]['error'] ?? 1) === UPLOAD_ERR_OK && ($_FILES[$field]['size'] ?? 0) > 0;
  }

  private function handleFileUpload($field, $delete_old = null)
  {
    $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    if (!$this->hasUploadedFile($field)) {
      throw new \Exception('Upload gagal atau tidak ada file valid.');
    }

    $uploadDir = dirname(__DIR__) . '/uploads';
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0755, true);
    }

    $files = $_FILES[$field];
    $uploadedNames = [];

    try {
      if (is_array($files['name'])) {
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
          if (($files['error'][$i] ?? 1) === UPLOAD_ERR_NO_FILE || ($files['size'][$i] ?? 0) === 0) {
            continue;
          }
          if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            throw new \Exception('Salah satu file gagal diunggah (Error: ' . $files['error'][$i] . ').');
          }
          $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
          if (!in_array($ext, $allowed)) {
            throw new \Exception("Tipe file .{$ext} tidak didukung. Gunakan PDF, JPG, JPEG, PNG, atau WEBP.");
          }
          $name = uniqid('file_') . '.' . $ext;
          $path = $uploadDir . '/' . $name;
          if (!move_uploaded_file($files['tmp_name'][$i], $path)) {
            throw new \Exception('Gagal menyimpan file upload.');
          }
          $uploadedNames[] = $name;
        }
      } else {
        $ext = strtolower(pathinfo($files['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
          throw new \Exception("Tipe file .{$ext} tidak didukung. Gunakan PDF, JPG, JPEG, PNG, atau WEBP.");
        }
        $name = uniqid('file_') . '.' . $ext;
        $path = $uploadDir . '/' . $name;
        if (!move_uploaded_file($files['tmp_name'], $path)) {
          throw new \Exception('Gagal menyimpan file upload.');
        }
        $uploadedNames[] = $name;
      }
    } catch (\Throwable $th) {
      // Rollback: Hapus file yang sudah sempat ter-upload dalam batch ini jika terjadi kegagalan
      foreach ($uploadedNames as $partialFile) {
        $partialPath = $uploadDir . '/' . $partialFile;
        if (file_exists($partialPath)) {
          @unlink($partialPath);
        }
      }
      throw $th;
    }

    if (empty($uploadedNames)) {
      throw new \Exception('Tidak ada file valid yang berhasil diunggah.');
    }

    // Delete old attachment files if replacement requested
    if (!empty($delete_old)) {
      $oldFiles = json_decode($delete_old, true);
      if (!is_array($oldFiles)) {
        $oldFiles = [$delete_old];
      }
      foreach ($oldFiles as $old) {
        $oldFileClean = basename($old);
        $oldPath = $uploadDir . '/' . $oldFileClean;
        if (file_exists($oldPath)) {
          @unlink($oldPath);
        }
      }
    }

    return count($uploadedNames) > 1 ? json_encode($uploadedNames) : $uploadedNames[0];
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
  public function mcp()
  {

    $psr17Factory = new Psr17Factory();
    $request = $psr17Factory->createServerRequestFromGlobals();
    $cache = IS_PROD ? new Psr16Cache(new FilesystemAdapter('mcp-discovery')) : null;
    $server = Server::builder()
      ->setServerInfo(
        name: 'MCP UangKu',
        version: MCP_VERSION,
        description: 'MCP Server untuk integrasi AI dalam pencatatan transaksi keuangan pribadi di Uangku. Dengan MCP, AI dapat berinteraksi langsung dengan data keuangan pengguna, memahami konteks transaksi, dan memberikan rekomendasi atau pencatatan yang lebih akurat sesuai dengan aturan bisnis Uangku.',
        icons: [new Icon(
          src: 'https://raw.githubusercontent.com/abdka/UangKu/main/html/assets/logo.png',
          mimeType: 'image/png',
          sizes: ['64x64', '128x128']
        )]
      )
      ->setInstructions('Use uangku://system_prompt to get the system prompt for recording transactions in Uangku.')
      ->setDiscovery(
        basePath: dirname(__DIR__) . '/libs/mcp',
        scanDirs: ['.'],
        cache: $cache,
      )  // Auto-discover attributes
      ->setSession(new FileSessionStore('/tmp/mcp_sessions')) // HTTP needs persistent sessions

      ->build();

    $transport = new StreamableHttpTransport(
      $request,
      middleware: [
        new DnsRebindingProtectionMiddleware(allowedHosts: ALLOWED_HOSTS),
      ]
    );


    $response = $server->run($transport);

    (new SapiEmitter())->emit($response);
  }
}
