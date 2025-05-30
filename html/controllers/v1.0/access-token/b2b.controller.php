<?php

namespace App\Controllers;

use App\libs\snapSecurity;

class b2b extends snap
{
  public function __construct()
  {
    $this->serviceCode = '73';
    $this->serviceName = 'B2B Token';
    $this->verifyCred = 'letMeVerify' === false; // its just false
    $this->requiredField['header'] = [
      "X-TIMESTAMP",
      "X-CLIENT-KEY",
      "X-SIGNATURE"
    ];
    parent::__construct();
  }
  public function index()
  {
    header("Access-Control-Allow-Methods: POST");
    header('X-CLIENT-KEY: ' . $this->header('X-CLIENT-KEY'));
    $timestamp = $this->header('X-TIMESTAMP');
    $clientID = $this->header('X-CLIENT-KEY');
    $signature = $this->header('X-SIGNATURE');

    $this->snapLog = [
      "uniqueKey" => "{$this->serviceCode}:[$clientID]|$timestamp",
      "serviceName" => $this->serviceName,
    ];
    // Check timestamp
    if (!isTimestampIsoValid($timestamp))
      return $this->snapResp('400', '01', "Timestamp Invalid");

    // get the snap information
    $this->setSnapCred(clientId: $clientID);

    /* Check Signature */
    $verifyType = $this->partner->signType == 'symmetric'; /* (symmetric|asymmetric) */
    $toCompared = "{$clientID}|{$timestamp}";
    if (!snapSecurity::verifySignature($signature, $toCompared, $this->partner, !$verifyType))
      return $this->snapResp('401', '00', ' Invalid Signature');

    // check body
    if ($this->body('grantType') != "client_credentials")
      return $this->snapResp('400', '02');

    // generate token
    $tokenData = snapSecurity::generateToken($clientID);
    return $this->snapResp('200', '00', "", $tokenData);
  }
}
