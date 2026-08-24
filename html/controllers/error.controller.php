<?php

namespace App\Controllers;

use App\Controller;

class error extends Controller
{
  public function __construct() {}
  public function options() {}
  public function index()
  {
    http_response_code(500);
    // setCacheControl(31536000 /* 1 Year Expired */, 'public');
    $data['view'] = 'error/error';
    $this->view('templates/template', $data);
  }
  public function enableJs()
  {
    http_response_code(406);
    // setCacheControl(31536000 /* 1 Year Expired */, 'public');
    $this->view('error/enableJS');
  }
  public function notFound()
  {
    http_response_code(404);
    // setCacheControl(31536000 /* 1 Year Expired */, 'public');
    $data['view'] = 'error/404';
    $this->view('templates/template', $data);
  }
  public function notConnected()
  {
    // http_response_code(410);
    setCacheControl(31536000 /* 1 Year Expired */, 'public');
    $data['view'] = 'error/Network';
    $this->view('templates/template', $data);
  }
  /**
   * Serves the service worker with its cache-version placeholder replaced by
   * APP_VERSION, so a new deploy (new GIT_SHA) automatically gets its own
   * cache name — the SW's own activate handler then deletes the old-named
   * cache. No more remembering to bump a version string by hand.
   */
  public function serviceWorker()
  {
    $templatePath = dirname(__DIR__) . '/public/sw.template.js';
    if (!file_exists($templatePath)) {
      http_response_code(404);
      exit;
    }
    header('Content-Type: application/javascript; charset=utf-8');
    // Never let the browser cache the SW file itself past this response,
    // otherwise it won't notice new versions in a timely manner.
    setCacheControl(0);
    echo str_replace('__APP_VERSION__', APP_VERSION, file_get_contents($templatePath));
  }
  public function preCache()
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
        BASEURL . "/Transaction",
        BASEURL . "/Wishlist",
        BASEURL . "/Transaction/Qris",
        BASEURL . "/Transaction/Graph",
        BASEURL . "/Transaction/Setting",
        $_SESSION['user']['image_url']
      ],
      "public" => [
        BASEURL . "/assets/js/notification-helper.js",
        BASEURL . "/assets/js/wishlist-share.js",
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
        "/ShowError/notConnected",
        // "https://code.highcharts.com/stock/highstock.js"
      ]
    ];
    echo json_encode($to_pre_cache);
  }
}
