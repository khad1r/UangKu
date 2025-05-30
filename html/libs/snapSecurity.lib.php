<?php

namespace App\libs;

class snapSecurity
{
  static public function generateToken(string $clientID)
  {
    $expirationTime = time() + OATH_TOKEN['expires'];
    $payload = $clientID . $expirationTime;
    $signature = hash_hmac('sha512', $payload, OATH_TOKEN['key']);
    return [
      'accessToken'    => base64_encode("{$clientID}|$expirationTime|$signature"),
      'tokenType'      => "Bearer",
      'expiresIn'      => OATH_TOKEN['expires'],
    ];
  }
  static public function getTokenClientID(string $token)
  {
    return explode('|', base64_decode($token))[0];
  }
  static public function verifyToken(string $token)
  {
    [$clientId, $expirationTime, $signature] = explode('|', base64_decode($token));
    $payload = $clientId .  $expirationTime;
    $expectedSignature = hash_hmac('sha512', $payload, OATH_TOKEN['key']);
    return (($signature === $expectedSignature) and ($expirationTime > time()));
  }
  static public function generateSignature(string $payload, bool $Asymmetric = false)
  {
    if ($Asymmetric) {
      // Load the private key (with passphrase if provided)
      $privateKey = openssl_pkey_get_private(SNAP_ENV['privateKey'], SNAP_ENV['privateKeyPass']);
      if ($privateKey === false)
        throw new \Exception('Unable to load private key. Please check the passphrase or the key format.');
      // Generate signature using the private key
      openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
      $result = base64_encode($signature);  // Return base64-encoded signature
    } else {
      // Generate HMAC signature using symmetric key (clientSecret)
      $signature = hash_hmac('sha512', $payload, SNAP_ENV['clientSecret'], true);
      $result = base64_encode($signature);  // Return base64-encoded signature
    }
    return $result;
  }

  static public function verifySignature(string $base64signature, string $toCompared, object $cred, bool $Asymmetric = false)
  {
    $signature = base64_decode($base64signature);
    if ($Asymmetric) {
      $verificationResult = openssl_verify($toCompared, $signature, $cred->publicKey, OPENSSL_ALGO_SHA256);
      $result = (bool) $verificationResult;
    } else {
      $verify = hash_hmac('sha512', $toCompared, $cred->clientSecret, true);
      $result = ($verify == $signature);
    }
    return $result;
  }

  static public function snapEncrypt($data, $key)
  {
    // Set cipher method
    $cipher = "aes-256-cbc";
    // Generate an initialization vector (IV)
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
    // Encrypt the data
    $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);
    // Return the encrypted data along with the IV, both base64-encoded
    return base64_encode($encrypted . '::' . $iv);
  }
  function decryptData($encryptedData, $key)
  {
    // Separate encrypted data and IV
    list($encrypted, $iv) = explode('::', base64_decode($encryptedData), 2);
    // Set cipher method
    $cipher = "aes-256-cbc";
    // Decrypt the data
    return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
  }
}
