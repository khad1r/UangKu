<?php

namespace App;

class Controller
{
  public function   __construct()
  {
    if (defined('USE_SESSION') && USE_SESSION && session_status() === PHP_SESSION_NONE) {
      session_cache_expire(5);
      session_start();
    }
    if (defined('TRANSFORM_RAW_TO_PHP_POST') && TRANSFORM_RAW_TO_PHP_POST) {
      $rawInput = file_get_contents('php://input');
      $jsonData = json_decode($rawInput, true);
      if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
        $_POST += $jsonData;
      }
    }
  }
  protected function view($renderView, $data = [], $return = false)
  {
    extract($data);
    $Controller = $this;
    if ($return) {
      ob_start();
      require_once './views/' . $renderView . '.view.php';
      return ob_get_clean();
    } else {
      require_once './views/' . $renderView . '.view.php';
    }
  }
  // protected function model($model)
  // {
  //   require_once './models/' . $model . '.model.php';
  //   $parts = explode("/", $model);
  //   return new (end($parts));
  // }
  protected function startJSEndScript()
  {
    ob_start();
  }
  protected function closeJSEndScript()
  {
    $this->endScript = ob_get_clean();
  }
  protected function showJSEndScript()
  {
    echo $this->endScript;
  }
  protected function setCookieToken(
    $cookieName,
    $cookieValue,
    $httpOnly = true,
    $secure = false,
    $expire = 0
  ) {
    // if (empty($expire)) $expire = strtotime("+1 day", time());
    // See: http://stackoverflow.com/a/1459794/59087
    // See: http://shiflett.org/blog/2006/mar/server-name-versus-http-host
    // See: http://stackoverflow.com/a/3290474/59087
    setcookie(
      $cookieName,
      $cookieValue,
      $expire,                // NextYear
      "/",                   // your path
      // $_SERVER["HTTP_HOST"], // your domain
      $secure,               // Use true over HTTPS
      $httpOnly              // Set true for $AUTH_COOKIE_NAME
    );
  }
}
