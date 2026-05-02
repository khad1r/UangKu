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
  protected function generateCSRFToken()
  {
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = [
      'value' => $token,
      'expires' => time() + 300 // Token valid for 5 minutes
    ];
    return $token;
  }
  protected function validateCSRFToken($token)
  {
    if (!isset($_SESSION['csrf_token'])) {
      return false;
    }
    $csrfToken = $_SESSION['csrf_token'];
    if ($csrfToken['expires'] < time()) {
      unset($_SESSION['csrf_token']);
      return false;
    }
    if (hash_equals($csrfToken['value'], $token)) {
      unset($_SESSION['csrf_token']);
      return true;
    }
    return false;
  }
}
