<?php

namespace App\Controllers;

use App\Controller;
use App\models\Auth as ModelsAuth;
use App\Route;

class Auth extends Controller
{
  public function index()
  {
    if (!CheckUser()) {
      Route::Redirect('/Auth/login');
      exit;
    }
    Route::Redirect('/Main');
  }
  public function login()
  {
    if (CheckUser()) {
      Route::Redirect('/Main');
      exit;
    }
    if (isset($_POST['login'])) {
      try {
        $user = new ModelsAuth()->login($_POST);
        if (!empty($user)) {
          $_SESSION['user'] = $user;
          Route::Redirect('/Main');
          return;
        } else {
          throw new \Exception("Username Atau Password Salah");
        }
      } catch (\Exception $e) {
        $_SESSION['alert'] = ['danger', $e->getMessage()];
      }
    }

    // $this->view('templates/header', array('title' => 'Login'));

    $data['view'] = 'auth/login';
    setCacheControl(0);
    $this->view('templates/template', $data);
    // $this->view('templates/footer');
  }
  public function logout()
  {
    if (isset($_SESSION['alert'])) $err = $_SESSION['alert'];
    session_destroy();
    if (isset($err)) {
      session_cache_expire(5);
      session_start();
      $_SESSION['alert'] = $err;
    }
    unset($_SESSION['user']);
    setCacheControl(0);
    header('Location: ' . BASEURL . '/');
  }
  public function regist()
  {
    $registering = new ModelsAuth()->is_registering_device();
    if (empty($registering)) {
      $_SESSION['alert'] = ['danger', 'Dilarang!!'];
      Route::Redirect('/Auth/login');
      exit;
    }
    if (isset($_POST['regist'])) {
      try {
        new ModelsAuth()->regist($_POST);
        Route::Redirect('/Auth/login');
        exit;
      } catch (\Exception $e) {
        $_SESSION['alert'] = ['danger', $e->getMessage()];
      }
    }
    $data['view'] = 'auth/regist';
    setCacheControl(0);
    $this->view('templates/template', $data);
  }
}
