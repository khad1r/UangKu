<?php

namespace App\Controllers;

class error extends snap
{
  public function __construct() {}
  public function options()
  {
    $this->snapResp('200', '00');
  }
  public function index()
  {
    return $this->not_found();
  }
  public function not_found()
  {
    return $this->snapResp('404', '02');
  }
}
