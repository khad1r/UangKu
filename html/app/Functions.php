<?php
function checkUser(...$level)
{
  if (empty($level)) return isset($_SESSION['user']);
  return isset($_SESSION['user']) && in_array($_SESSION['user']['level_user'], $level);
}
function generateAlert()
{
  if (!empty($_SESSION['showToastNotification'])) foreach ($_SESSION['showToastNotification'] as $key => &$toast) {
    echo <<<JS
      showAlert(
        "{$toast['msg']}",
        "{$toast['type']}",
        "{$toast['title']}",
      );
    JS;
    unset($_SESSION['showToastNotification'][$key]);
  }
}
function showAlert($msg = '', $type = 'primary', $title = 'Pemberitahuan')
{
  $_SESSION['showToastNotification'][] = [
    'type' => $type,
    'msg' => $msg,
    'title' => $title,
  ];
}
function inputValidator($input)
{
  if (isset($_SESSION['InputError'][$input])) {
?>
    <i class="notice" style="color: red"><?= $_SESSION['InputError'][$input] ?> </i>
  <?php
    unset($_SESSION['InputError'][$input]);
  }
}
function isRateLimit($limit, $interval)
{
  $currentTime = time();
  $requestData = isset($_SESSION['rate_limit']) ? $_SESSION['rate_limit'] : [];
  $requestData = array_filter($requestData, function ($timestamp) use ($currentTime, $interval) {
    return ($currentTime - $timestamp) < $interval;
  });
  if (count($requestData) >= $limit) return false; // Rate limit exceeded
  // Add current request
  $requestData[] = $currentTime;
  $_SESSION['rate_limit'] = $requestData;
  return true; // Rate limit not exceeded
}
function getTimeStamp()
{
  $timestamp = new \DateTime();
  $timestamp->setTimeZone(new \DateTimeZone('Asia/Jakarta'));
  $x_timestamp = $timestamp->format('c');
  return $x_timestamp;
}
function setCacheControl(int $age, $policy = 'private')
{
  if (0 >= $age) {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Expires: 0'); // Expired immediately
  } else {
    header("Cache-Control: $policy, max-age=$age");
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $age) . ' GMT');
  }
}
function setCookieToken(
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
function validateApi($limit, $interval)
{
  if (!CheckUser()) {
    // showAlert('Akses Ditolak', 'warning');
    http_response_code(401);
    exit;
  }
  header('Content-Type: application/json');
  if (!isRateLimit($limit, $interval)) {
    http_response_code(429); // Too Many Requests
    echo json_encode(['error' => 'Rate limit exceeded. Please try again later.']);
    exit;
  }
}
function csrf_security(string $form, array|object $validate = [])
{

  $token_key = 'csrf_tokens';
  $token_name = 'csrf_security_token';
  $generate = fn() => $_SESSION[$token_key][$form] = bin2hex(random_bytes(32));
  // Validate incoming CSRF token
  if (!empty($validate)) {
    $validate = (object) $validate;
    $isvalid = isset($validate->$token_name, $_SESSION[$token_key][$form]) &&
      hash_equals($_SESSION[$token_key][$form], $validate->$token_name);
    unset($_SESSION[$token_key][$form]);
    if (!$isvalid) {
      showAlert('CSRF Token Invalid Detected!!!', 'warning');
      App\Route::Referer('');
      exit();
    }
    return $isvalid;
  }
  // Render the token as a hidden field
  ?>
  <input type="hidden" name="<?= $token_name ?>" value="<?= htmlspecialchars($_SESSION[$token_key][$form] ?? ($generate()), ENT_QUOTES) ?>">
<?php
  return null;
}
function validate_required_input(array &$toValidated, array $required)
{
  $error = [];
  foreach ($required as $requiredField)
    if (!isset($toValidated[$requiredField]) || trim($toValidated[$requiredField]) === '')
      $error[$requiredField] = 'Input Ini Tidak Boleh Kosong!!!';
  if (!empty($error)) {
    $_SESSION['InputError'] = $error;
    throw new Exception("Error Data!!");
  }
}
function sanitize_input(&$toValidated)
{
  if (is_array($toValidated))
    foreach ($toValidated as &$input) $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
  else $toValidated = htmlspecialchars($toValidated, ENT_QUOTES, 'UTF-8');
  return $toValidated;
}
// function isKreditOrTabungan(string $custNo): string|bool
// {
//   // Replace 'KODE_PRODUK' with the KODE_TABUNGAN values
//   $regexTabungan = str_replace('KODE_PRODUK', implode('|', KODE_TABUNGAN), REGEX_NO_REK);
//   if (preg_match($regexTabungan, $custNo)) return 'tabungan';

//   // Replace 'KODE_PRODUK' with the KODE_KREDIT values
//   $regexKredit = str_replace('KODE_PRODUK', implode('|', KODE_KREDIT), REGEX_NO_REK);
//   if (preg_match($regexKredit, $custNo)) return 'kredit';
//   return false;
// }
// function snapFormatAmount(int|string $amount, string $currency): array
// {
//   return [
//     'value' => number_format((float)$amount, 2, '.', ''),
//     'currency' => $currency
//   ];
// }
// function isTimestampIsoValid($timestamp)
// {
//   if (preg_match('/^' .
//     '(\d{4})-(\d{2})-(\d{2})T' . // YYYY-MM-DDT ex: 2014-01-01T
//     '(\d{2}):(\d{2}):(\d{2})' .  // HH-MM-SS  ex: 17:00:00
//     '(((\+)\d{2}:\d{2}))' .  // +01:00
//     '$/', $timestamp, $parts) == true) {
//     try {
//       new \DateTime($timestamp);
//       return true;
//     } catch (\Exception $e) {
//       return false;
//     }
//   } else {
//     return false;
//   }
// }

// function getValueByKey(array $array, string $key)
// {
//   $keys = explode('.', $key);
//   $value = $array;
//   foreach ($keys as $key) {
//     if (isset($value[$key])) $value = $value[$key];
//     else return null; // Return null if the key doesn't exist
//   }
//   return $value;
// }

// function getKodeTrans($cab, $listkode)
// {
//   // disiapkan kodetrans kalau semua data tidak ketemu
//   $tmpKodeTrans = "";
//   if (strpos($listkode, '#')) {
//     $kode = explode(";", $listkode);
//     for ($i = 0; $i < count($kode); $i++) {
//       $kodetrans = explode("#", $kode[$i]);
//       if (count($kodetrans) == 2) {
//         // untuk jaga2 jika ta ada data cab yg ketemu
//         $tmpKodeTrans = $kodetrans[1];
//         if ($kodetrans[0] == $cab) {
//           return $kodetrans[1];
//         }
//       }
//     }
//   } else {
//     return $listkode;
//   }
//   // jika tidak ada cabang yang sesuai, pakai
//   return $tmpKodeTrans;
// }
// function mergeSMSTextTemplate($template, $open, $close, $data)
// {
//   // Escape the open and close tags
//   $open = preg_quote($open, '/');
//   $close = preg_quote($close, '/');

//   // Find the template strings and replace with data from our
//   // $data array where the key exists.
//   return preg_replace_callback(
//     "/{$open}(.*?){$close}/",
//     function ($matches) use ($data) {
//       // If the key exists in $data, return that as replacement
//       if (array_key_exists(strtolower($matches[1]), $data)) {
//         return $data[strtolower($matches[1])];
//       } else {
//         // If the key doesn't exist, return the tag back so no replacement.
//         return $matches[0];
//       }
//     },
//     $template
//   );
// }
