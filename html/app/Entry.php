<?php

namespace App;

class Entry
{
  protected $controller = DEFAULT_CONTROLLER;
  protected $method = 'index';
  protected $params = [];

  public function __construct()
  {
    // Parse the URL and get controller, method, and parameters
    $url = $this->parseURL();

    // Call the controller's method with the parameters
    call_user_func_array([new $url[0], $url[1]], $url[2]);
  }

  private function parseURL()
  {
    // Get the request URI
    $uri = $_SERVER["REQUEST_URI"];

    // If the URI is empty or just "/", use default controller and method
    if (empty($uri) || $uri === '/') {
      return [$this->controller, $this->method, $this->params];
    }

    // Split the URI into path and query parameters
    list($path, $_get) = explode('?', $uri, 2) + [1 => ''];

    // Attempt to get a defined route, or fall back to finding the controller method
    list($controller, $method, $params) = Route::DefinedRoute($_SERVER["REQUEST_METHOD"], $path);
    if (empty($controller) || !$method) {
      list($controller, $method, $params) = (ITERATE_CONTROLLER)
        ? $this->findControllerMethod(array_filter(explode('/', trim($path, '/'))))
        : [$this->controller, $this->method, $this->params];
    }

    // Return the controller, method, and parameters
    return [$controller, $method, $params];
  }

  private function findControllerMethod(
    $segments,
    $path = '',
    $currentDepth = 0,
    // $validController = [$this->controller, $this->method, $this->params]
    $fallbackController = [\App\Controllers\error::class, 'notFound', []]
  ) {
    $maxDepth = intval(MAX_FILE_DEPTH_RECURSIVE);
    if ($maxDepth === 0) $maxDepth = PHP_INT_MAX;

    // If no segments are found, return the error controller
    // Stop searching if we reach the max depth
    if (empty($segments) || $currentDepth > $maxDepth) {
      // return [\App\Controllers\error::class, 'notFound', []];
      return $fallbackController;
    }

    // Get the last segment and remaining path
    // $lastSegment = array_pop($segments);
    // $remainingPath = implode('/', $segments);
    // $controllerPath = "./controllers/" . ($remainingPath ? $remainingPath . '/' : '') . "$lastSegment.controller.php";
    $firstSegment = array_shift($segments);
    $controllerPath = dirname(__DIR__) . "/controllers/{$path}{$firstSegment}.controller.php";


    // Check if the controller file exists
    if (file_exists($controllerPath)) {
      // Generate the controller class name based on the remaining path and last segment
      // $controllerClass = "App\\Controllers\\$firstSegment";
      $controllerClass = "App\\Controllers\\" . str_replace('/', '\\', "{$path}{$firstSegment}");

      if (method_exists($controllerClass, reset($segments))) {
        $method = array_shift($segments);
        // Return the controller, method (default or from the class), and parameters
        return [$controllerClass, $method, $segments];
      }
      $fallbackController = [$controllerClass, $this->method, $segments];
      // $method = !empty($segments) && method_exists($controllerClass, end($segments))
      //   ? (empty($params) ? $this->method : array_pop($params))
      //   : $this->method;

      // Return the controller, method (default or from the class), and parameters
      // return [$controllerClass, $method, $params];
    }

    // If the controller file is not found, treat the last segment as a parameter and recurse
    // array_unshift($params, $lastSegment);
    return $this->findControllerMethod($segments, "{$path}{$firstSegment}\\", $currentDepth + 1, $fallbackController);
  }
}
