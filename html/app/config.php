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
define('ALLOWED_HOSTS', array_values(array_filter(array_unique([
  'localhost',
  '127.0.0.1',
  '::1',
  $hostname,
  ...explode(',', getenv('ALLOWED_HOSTS') ?: '')
]))));
unset($is_https, $protocol, $hostname);
// define('BASEURL', 'http://localhost');
define('WEB_TITLE', 'UangKu');
define('USE_SESSION', true);
define('TRANSFORM_RAW_TO_PHP_POST', true);
define('IS_PROD', getenv('ENV') !== 'development');
// Changes on every real deploy: GIT_SHA is baked in at Docker build time from
// the commit that triggered the build (see .github/workflows/docker-build.yml
// and .docker/Dockerfile.production). Used to auto-bust the service worker's
// cache (see error::serviceWorker()) so users never get stuck on a stale
// build without needing to remember to bump a version string by hand.
// Falls back to a hash of key asset mtimes for local dev, where GIT_SHA isn't set.
$gitSha = getenv('GIT_SHA');
define('APP_VERSION', $gitSha ? substr($gitSha, 0, 12) : substr(md5(
  (@filemtime(__DIR__ . '/../public/assets/js/script.js') ?: 0) . '-' .
  (@filemtime(__DIR__ . '/../public/assets/css/style.css') ?: 0)
), 0, 12));
// Versi Semantic Otomatis berbasis Conventional Commits:
// 1. Prioritas dari environment APP_SEMVER (di-inject saat Docker build)
// 2. Fallback analisis riwayat Git lokal sejak tag rilis terakhir
$appSemver = getenv('APP_SEMVER');
if (empty($appSemver)) {
  $latestTag = @exec('git describe --tags --abbrev=0 2>/dev/null') ?: 'v1.17.9';
  if (preg_match('/^v?(\d+)\.(\d+)\.(\d+)$/', trim($latestTag), $m)) {
    $major = (int)$m[1];
    $minor = (int)$m[2];
    $patch = (int)$m[3];

    // Cek jenis commit sejak tag terakhir (Conventional Commits)
    $hasMajor = @exec("git log {$latestTag}..HEAD --grep=\"^.*!:\" --grep=\"BREAKING CHANGE\" -n 1 --oneline 2>/dev/null");
    $hasFeat  = @exec("git log {$latestTag}..HEAD --grep=\"^feat\" -n 1 --oneline 2>/dev/null");
    $hasAny   = @exec("git log {$latestTag}..HEAD -n 1 --oneline 2>/dev/null");

    if (!empty($hasMajor)) {
      $appSemver = sprintf('%d.0.0', $major + 1);
    } elseif (!empty($hasFeat)) {
      $appSemver = sprintf('%d.%d.0', $major, $minor + 1);
    } elseif (!empty($hasAny)) {
      $appSemver = sprintf('%d.%d.%d', $major, $minor, $patch + 1);
    } else {
      $appSemver = sprintf('%d.%d.%d', $major, $minor, $patch);
    }
  }
}

define('APP_SEMVER', ltrim(trim($appSemver ?: '1.18.0'), 'v'));
define('MCP_VERSION', getenv('MCP_VERSION') ?: APP_SEMVER);
unset($appSemver);

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
