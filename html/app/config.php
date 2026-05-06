<?php
date_default_timezone_set(getenv('TZ'));
// error_reporting(getenv('DOCKER_ENV') == 'development'); // Turn off error reporting
error_reporting(E_ALL);
// Ensure they aren't displayed to the browser
ini_set('display_errors', getenv('DOCKER_ENV') == 'development' ? '1' : '0');
// Ensure they ARE sent to the log stream (stderr)
ini_set('log_errors', '1');
// header("Content-type:text/plain");
/* set character encoding to utf-8*/
mb_http_output("UTF-8");
mb_internal_encoding("UTF-8");
setlocale(LC_TIME, 'id_ID.UTF-8');
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
  || $_SERVER['SERVER_PORT'] == 443;
$protocol = $is_https ? 'https://' : 'http://';
$hostname = getenv('HOSTNAME') ?: $_SERVER['HTTP_HOST']; // fallback jika getenv kosong
define('HOSTNAME', $hostname);
define('BASEURL', $protocol . $hostname);
unset($is_https, $protocol, $hostname);
// define('BASEURL', 'http://localhost');
define('WEB_TITLE', 'UangKu');
define('USE_SESSION', true);
define('TRANSFORM_RAW_TO_PHP_POST', true);
define('IS_PROD', getenv('ENV') !== 'development');
define('ENABLE_AUTH', (getenv('ENABLE_AUTH') ?: 'true') === 'true');
define('DEFAULT_CONTROLLER', ENABLE_AUTH ? App\Controllers\Auth::class : App\Controllers\Transaction::class);
define('JENIS_TRANSAKSI', ['Pengeluaran', 'Pemasukan', 'Pindah Buku']);
define('JENIS_UANG', ['Uang Hijau', 'Uang Biru', 'Uang Merah']);

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
