<?php

namespace App\Controllers;

use App\Controller;
use App\models\Auth as ModelsAuth;
use App\Route;
use lbuchs\WebAuthn\WebAuthn;

class Users extends Controller
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

    $data['title'] = 'Keamanan';
    $data['subTitle'] = '<strong>Authentikasi</strong> <i class="fas fa-fingerprint"></i>';
    if (!empty($_POST)) {
      try {
        // if (!csrf_security('rekening-form', validate: $_POST)) return;
        $required_field = ["nickname"];
        sanitize_input($_POST);
        validate_required_input($_POST, $required_field);
        $insert = [
          'nickname'          => $_POST['nickname'],
        ];
        if (new ModelsAuth()->create_empty($insert) > 0) {
          showAlert("Penambahan Berhasil", 'success');
          Route::Referer('/Users');
          return;
        } else {
          showAlert('Operasi Gagal', 'danger');
        }
      } catch (\Exception $e) {
        showAlert($e->getMessage(), 'danger');
      }
    }
    $data['title'] = 'Daftar Rekening';
    setCacheControl(259200/* 3 Day Expired */);
    $data['view'] = 'users/list';
    $data['right-bottom-view'] = 'components/navbar';
    $this->view('templates/template', $data);
  }
  public function delete($id_passkey = '')
  {
    showAlert('Something Happen ?!', 'warning');
    sanitize_input($id_passkey);
    if (!empty($_POST)) {
      try {
        $model = new ModelsAuth();
        $crendential_data = json_decode($_POST['challenge'], true);
        $credential_id = bin2hex(base64_decode($crendential_data['id']));
        $unique_id = bin2hex(base64_decode($crendential_data['userHandle']));
        $passkey = $model->get_passkey($credential_id);
        if (empty($passkey)) throw new \Exception("Kredensial tidak dikenali");
        $webAuthn = new WebAuthn(WEB_TITLE, (explode(':', HOSTNAME))[0]);
        $isValid = $webAuthn->processGet(
          base64_decode($crendential_data['clientDataJSON']),
          base64_decode($crendential_data['authenticatorData']),
          base64_decode($crendential_data['signature']),
          $passkey['public_key'],
          $_SESSION['credDeleteChallenge']
        );
        if (!$isValid) throw new \Exception("Kredensial invalid");
        unset($_SESSION['credDeleteChallenge']);
        if (!($model->delete($id_passkey) > 0)) throw new \Exception("Error Processing Request", 1);
        showAlert("Penghapusan Kredensial Berhasil", 'success');
        Route::Redirect('/Users');
        return;
      } catch (\Exception $e) {
        showAlert($e->getMessage(), 'danger');
      }
    }
    Route::Referer('/Users');
    return;
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
      $resp = new ModelsAuth()->datatable($_POST);

      unset($_SESSION['credDeleteChallenge']);
      $webAuthn = new WebAuthn(WEB_TITLE, (explode(':', HOSTNAME))[0]);
      $resp['webAuthnArgs'] = $webAuthn->getGetArgs([hex2bin($_SESSION['user']['credential_id'])]);
      // $resp['webAuthnArgs'] = $webAuthn->getGetArgs();
      $_SESSION['credDeleteChallenge'] = ($webAuthn->getChallenge())->getBinaryString();
    } catch (\Throwable $th) {
      http_response_code(500);
      $resp = ['error' => $th->getMessage()];
    }
    header('Content-Type: application/json');
    echo json_encode($resp);
  }
}
