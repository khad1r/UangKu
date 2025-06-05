<?php

namespace App\Controllers;

use App\Controller;
use App\models\Transaksi;
use App\Route;

class Main extends Controller
{
  public function __construct()
  {
    parent::__construct();
    if (!CheckUser()) {
      $_SESSION['alert'] = array('warning', 'Akses Ditolak');
      Route::Redirect('/Auth/Logout');
      exit;
    }
  }
  public function index()
  {
    setCacheControl(259200/* 3 Day Expired */);
    $data['view'] = 'transaction/dashboard';
    $data['right-bottom-view'] = 'components/navbar';
    $this->view('templates/template', $data);
  }
  public function list()
  {
    setCacheControl(259200/* 3 Day Expired */);
    $data['view'] = 'transaction/list';
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
      $resp = new Transaksi()->datatable($_POST);
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
