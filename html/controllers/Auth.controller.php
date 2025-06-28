<?php

namespace App\Controllers;

use App\Controller;
use App\models\Auth as ModelsAuth;
use App\Route;
use lbuchs\WebAuthn\WebAuthn;

class Auth extends Controller
{
  public function index()
  {
    if (!CheckUser()) {
      Route::Redirect('/Auth/login');
      exit;
    }
    Route::Redirect('/Record');
  }
  public function login()
  {
    if (CheckUser()) {
      Route::Redirect('/Record');
      exit;
    }
    $webAuthn = new WebAuthn(WEB_TITLE, (explode(':', HOSTNAME))[0]);
    if (isset($_POST['login'])) {
      try {
        $crendential_data = json_decode($_POST['login'], true);
        $credential_id = bin2hex(base64_decode($crendential_data['id']));
        $unique_id = bin2hex(base64_decode($crendential_data['userHandle']));
        $passkey = new ModelsAuth()->get_passkey($credential_id);
        if (empty($passkey)) {
          throw new \Exception("Kredensial tidak dikenali");
        }
        $isValid = $webAuthn->processGet(
          base64_decode($crendential_data['clientDataJSON']),
          base64_decode($crendential_data['authenticatorData']),
          base64_decode($crendential_data['signature']),
          $passkey['public_key'],
          $_SESSION['challenge']
        );
        if ($isValid) {
          unset($_SESSION['challenge']);
          // Let's save the user id to the session, logging them in.
          $_SESSION['user'] = $passkey;
          Route::Redirect('/Transaction');
          return;
        } else {
          throw new \Exception("Kredensial invalid");
        }
      } catch (\Exception $e) {
        showAlert($e->getMessage(), 'danger');
      }
    }

    // $this->view('templates/header', array('title' => 'Login'));
    $data['webAuthnArgs'] = $webAuthn->getGetArgs();
    // $data['webAuthnArgs']->mediation = 'conditional';
    $_SESSION['challenge'] = ($webAuthn->getChallenge())->getBinaryString();
    $data['view'] = 'auth/login';
    setCacheControl(0);
    $this->view('templates/template', $data);
    // $this->view('templates/footer');
  }
  public function logout()
  {
    if (isset($_SESSION['showToastNotification'])) $err = $_SESSION['showToastNotification'];
    session_destroy();
    if (isset($err)) {
      session_cache_expire(5);
      session_start();
      $_SESSION['showToastNotification'] = $err;
    }
    unset($_SESSION['user']);
    setCacheControl(0);
    header('Location: ' . BASEURL . '/');
  }
  public function regist()
  {
    $data['title'] = 'Registrasi';
    $registering = new ModelsAuth()->is_registering();
    if (empty($registering)) {
      showAlert('Dilarang!!', 'danger');
      Route::Redirect('/Auth/login');
      exit;
    }
    $webAuthn = new WebAuthn(WEB_TITLE, (explode(':', HOSTNAME))[0]);
    if (isset($_POST['regist'])) {
      try {
        $data = json_decode($_POST['regist'], true);
        $clientData = base64_decode($data['response']['clientDataJSON']);
        $attestation = base64_decode($data['response']['attestationObject']);
        $cred = $webAuthn->processCreate($clientData, $attestation, $_SESSION['challenge']);
        new ModelsAuth()->regist($cred);
        showAlert('Registrasi Berhasil', 'success');
        Route::Redirect('/Auth/login');
        exit;
      } catch (\Exception $e) {
        showAlert($e->getMessage(), 'danger');
        Route::Redirect('');
        exit;
      }
    }
    $data['webAuthnArgs'] = $webAuthn->getCreateArgs(
      $registering['passkey_id'],
      $registering['nickname'],
      $registering['nickname'],
      requireResidentKey: true,
      crossPlatformAttachment: false
    );
    // $data['webAuthnArgs']->publicKey->authenticatorSelection->authenticatorAttachment =
    // $data['webAuthnArgs']->authenticatorSelection = [
    //   'authenticatorAttachment' => 'platform',
    //   'residentKey' => 'required',
    //   'requireResidentKey' => true,
    //   'userVerification' => 'required'
    // ];
    // $data['webAuthnArgs']->attestation = 'none'; // Optional: skip attestation
    $_SESSION['challenge'] = ($webAuthn->getChallenge())->getBinaryString();
    // $_SESSION['challenge'] = $webAuthn->getChallenge();
    // $data['view'] = 'auth/regist';
    setCacheControl(0);
    // $this->view('templates/template', $data);
    // $this->view('auth/regist', $data);
    $data['view'] = 'auth/regist';
    $this->view('templates/template', $data);
  }
}
