<?php

namespace App\Controllers;

use App\Controller;

class error extends Controller
{
  public function __construct() {}

  public function index()
  {
    http_response_code(500);
    setCacheControl(31536000 /* 1 Year Expired */, 'public');
    $data['view'] = 'error/error';
    $this->view('templates/template', $data);
  }
  public function enableJs()
  {
    http_response_code(406);
    setCacheControl(31536000 /* 1 Year Expired */, 'public');
    $this->view('error/enableJS');
  }
  public function notFound()
  {
    http_response_code(404);
    setCacheControl(31536000 /* 1 Year Expired */, 'public');
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
}
