<?php

namespace App;

class Route
{
  private static $router = [
    'GET:/uploads/.*' => [\App\Controllers\Record::class, 'c9184f37cff01bcdc32dc486ec36961'],
    // 'POST:/v1.0/transfer-va/inquiry' => [\App\Controllers\inquiry::class, 'index'],
    // 'POST:/v1.0/transfer-va/payment' => [\App\Controllers\payment::class, 'index'],
    'OPTIONS:/precache' => [\App\Controllers\error::class, 'preCache'], // Catch-all OPTIONS route
    'OPTIONS:/.*' => [\App\Controllers\error::class, 'options'], // Catch-all OPTIONS route
  ];

  public static function Referer($path = '')
  {
    if (isset($_SERVER["HTTP_REFERER"])) {
      header("Location: " . $_SERVER["HTTP_REFERER"]);
    } else {
      header('Location: ' . BASEURL . $path);
    }
  }

  public static function Redirect($path = '')
  {
    header('Location: ' . BASEURL . $path);
  }
  // Cache for dynamic route patterns
  private static $cachedPatterns = [];

  // Convert route to a regex pattern and cache it
  private static function getRoutePattern(string $route)
  {
    if (!isset(self::$cachedPatterns[$route])) {
      // Replace $1, $2 with regex for dynamic parameters
      $pattern = preg_replace('/\$(\d+)/', '([^/]+)', $route);
      self::$cachedPatterns[$route] = '/^' . str_replace('/', '\/', $pattern) . '$/';
    }
    return self::$cachedPatterns[$route];
  }

  public static function DefinedRoute(string $httpMethod, string $path)
  {
    foreach (self::$router as $route => $handler) {
      if (strpos($route, $httpMethod) === 0) {
        $routePath = substr($route, strlen($httpMethod . ':'));
        $pattern = self::getRoutePattern($routePath);

        if (preg_match($pattern, $path, $matches)) {
          array_shift($matches); // Remove the full match
          return [$handler[0], $handler[1], $matches]; // Return handler and parameters
        }
      }
    }
    return [null, false, []]; // Return false if no match found
  }
}
