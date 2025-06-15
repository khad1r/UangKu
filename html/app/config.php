<?php
date_default_timezone_set(getenv('TZ'));
error_reporting(getenv('DOCKER_ENV') == 'development'); // Turn off error reporting
// header("Content-type:text/plain");
/* set character encoding to utf-8*/
mb_http_output("UTF-8");
mb_internal_encoding("UTF-8");
setlocale(LC_TIME, 'id_ID.UTF-8');
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
  || $_SERVER['SERVER_PORT'] == 443;

$hostname = getenv('HOSTNAME') ?: $_SERVER['HTTP_HOST']; // fallback jika getenv kosong
define('HOSTNAME', $hostname);
define('BASEURL', $protocol . $hostname);
unset($is_https, $protocol, $hostname);
// define('BASEURL', 'http://localhost');
define('WEB_TITLE', 'UangKu');
define('DEFAULT_CONTROLLER', App\Controllers\Auth::class);
define('USE_SESSION', true);
define('TRANSFORM_RAW_TO_PHP_POST', true);
define('IS_PROD', getenv('ENV') !== 'development');
define('JENIS_TRANSAKSI', ['Pengeluaran', 'Pemasukan', 'Pindah Buku']);

/** app/autoload.php depth for searching classes
 * Interpret MAX_AUTOLOAD_DEPTH as follows:
 * true/1 = 1 depth
 * false/0 = infinite depth
 * 2, 3, 4, 5 = specified depth */
define('MAX_FILE_DEPTH_RECURSIVE', 5);
define('ITERATE_CONTROLLER', true);

define('DATABASES', [
  'default' => (object)[
    'name' => getenv('SQLITE_DEFAULT_NAME'),
    'path' => dirname(__DIR__) . '/../.databases/' . getenv('SQLITE_DEFAULT_NAME'),
  ]
]);
