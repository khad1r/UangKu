<?php

namespace App\Controllers;

use App\Controller;
use App\Route;

class Main extends Controller
{
  public function index($request = null)
  {
    if (isset($request['data']) && in_array($request['data'], ['week', 'month', 'year'])) {
      return $this->graphData($request['data']);
    }
    if (!CheckUser()) {
      // $_SESSION['alert'] = array('warning', 'Akses Ditolak');
      Route::Redirect('/Auth/Logout');
      exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS')
      return $this->preCache();
    $data['title'] = 'Qris Bank Gresik';
    $data['view'] = 'main/graph';
    $data['navPage'] = 'graph';
    $data['store_name'] = $_SESSION['user']['store_name'];
    $data['nmid'] = $_SESSION['user']['nmid'];
    setCacheControl(259200/* 3 Day Expired */);
    $this->view('templates/template', $data);
  }

  public function Qris()
  {
    if (!CheckUser()) {
      // $_SESSION['alert'] = array('warning', 'Akses Ditolak');
      Route::Redirect('/Auth/Logout');
      exit;
    }

    $data['title'] = 'Qris Bank Gresik';
    $data['qris_url'] = $_SESSION['user']['image_url'];
    $data['view'] = 'main/showQris';
    $data['navPage'] = 'qris';
    setCacheControl(259200/* 3 Day Expired */);
    $this->view('templates/template', $data);
  }
  public function Setting()
  {
    if (!CheckUser()) {
      // $_SESSION['alert'] = array('warning', 'Akses Ditolak');
      Route::Redirect('/Auth/Logout');
      exit;
    }
    if (isset($_POST['update'])) {
      try {
        if ($this->model('sibg/Qris')->updateCredentials($_POST) > 0) {
          $_SESSION['alert'] = ['success', "Operasi Berhasil, Silahkan Login Kembali"];
          Route::Redirect('/Auth/Logout');
          return;
        } else {
          $_SESSION['alert'] = ['danger', 'Operasi Gagal'];
        }
      } catch (\Exception $e) {
        $_SESSION['alert'] = ['danger', $e->getMessage()];
      }
    }
    $data['post'] = $_POST;
    $data['title'] = 'Qris Bank Gresik';

    $data['expandInput'] = isset($_SESSION['alert']) || (isset($_SESSION['InputError']) && !empty($_SESSION['InputError']));
    $data['username'] = isset($data['post']['username']) ? $data['post']['username'] : $_SESSION['user']['login_user'];

    $data['view'] = 'main/setting';
    $data['navPage'] = 'setting';
    setCacheControl(0/* 3 Day Expired */);
    // setCacheControl(259200/* 3 Day Expired */);
    $this->view('templates/template', $data);
  }
  public function queryTransaction()
  {
    $rate_limit_interval = 15; // 15 detik
    $rate_limit_max_request = 10; // 10 request
    header("Access-Control-Allow-Methods: POST,GET");
    header("Access-Control-Allow-Headers: Content-Type");
    setCacheControl(0);

    validateApi($rate_limit_max_request, $rate_limit_interval);

    http_response_code(200);
    try {
      $resp = $this->model('sibg/Transaction')->datatable($_POST);
    } catch (\Throwable $th) {
      http_response_code(500);
      $resp = ['error' => $th->getMessage()];
    }
    header('Content-Type: application/json');
    echo json_encode($resp);
  }
  private function graphData($request)
  {
    $rate_limit_interval = 10; // 15 detik
    $rate_limit_max_request = 5; // 10 request
    header("Access-Control-Allow-Methods: GET");
    header("Access-Control-Allow-Headers: Content-Type");

    validateApi($rate_limit_max_request, $rate_limit_interval);

    http_response_code(200);
    try {
      setCacheControl(86400);
      $resp = $this->model('sibg/Transaction')->graphData($request);
    } catch (\Throwable $th) {
      http_response_code(500);
      $resp = ['error' => $th->getMessage()];
    }
    header('Content-Type: application/json');
    echo json_encode($resp);
  }
  private function preCache()
  {
    $rate_limit_interval = 10; // 15 detik
    $rate_limit_max_request = 5; // 10 request
    header("Access-Control-Allow-Methods: GET");
    header("Access-Control-Allow-Headers: Content-Type");
    validateApi($rate_limit_max_request, $rate_limit_interval);
    header('Content-Type: application/json');
    setCacheControl(259200 /* 3 Day Expired */);
    $to_pre_cache = [
      "private" => [
        BASEURL . "/Main",
        BASEURL . "/Main/Qris",
        BASEURL . "/Main/Graph",
        BASEURL . "/Main/Setting",
        $_SESSION['user']['image_url']
      ],
      "public" => [
        BASEURL . "/assets/js/notification-helper.js",
        BASEURL . "/assets/js/script.js",
        BASEURL . "/assets/css/style.css",
        "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css",
        "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js",
        // "https://cdn.datatables.net/v/bs5/jq-3.6.0/dt-1.13.1/datatables.min.css",
        // "https://cdn.datatables.net/v/bs5/jq-3.6.0/dt-1.13.1/datatables.min.js",
        "https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459Wlhyw.woff2",
        "https://fonts.gstatic.com/s/varelaround/v20/w8gdH283Tvk__Lua32TysjIfp8uP.woff2",
        "https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Varela+Round&display=swap",
        "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css",
        "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/webfonts/fa-regular-400.woff2",
        "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/webfonts/fa-solid-900.woff2",
        BASEURL . "/assets/img/icon.ico",
        BASEURL . "/assets/img/logo_512.png",
        BASEURL . "/assets/img/qris_contoh.png",
        BASEURL . "/manifest.json",
        "/ShowError/notConnected",
        // "https://code.highcharts.com/stock/highstock.js"
      ]
    ];
    echo json_encode($to_pre_cache);
  }
}
