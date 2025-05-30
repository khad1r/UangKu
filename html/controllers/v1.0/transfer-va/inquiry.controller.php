<?php

namespace App\Controllers;

use App\models\partner;
use App\models\snapLog;
use App\models\virtualAccount;

class inquiry extends snap
{
  public function __construct()
  {
    $this->serviceCode = '24';
    $this->serviceName = 'INQUIRY';
    $this->requiredField['body'] = [
      'partnerServiceId',
      'customerNo',
      'virtualAccountNo',
      'inquiryRequestId'
    ];
    parent::__construct();
  }
  public function index()
  {
    header("Access-Control-Allow-Methods: POST");
    $reqPrefix             = trim($this->body('partnerServiceId'));
    $reqCustomerNo         = trim($this->body('customerNo'));
    $reqVirtualAccountNo   = trim($this->body('virtualAccountNo'));
    $reqInquiryRequestId   = trim($this->body('inquiryRequestId'));
    $virtualAccountTrxType = [
      'bill' => 'r',
      'transfer' => 'o',
    ];

    // masukkan dulu ke log snap
    $this->snapLog = [
      "uniqueKey" => "{$this->serviceCode}:$reqInquiryRequestId",
      "serviceName" => $this->serviceName,
      "transactionId" => $reqInquiryRequestId,
      "virtualAccount" => $reqVirtualAccountNo,
    ];
    $this->processReqAddInfo();
    // $client_binno = str_pad($this->partner->binno, 8, ' ', STR_PAD_LEFT)
    if ($reqPrefix != $this->partner->vaPrefix)
      return $this->snapResp("400", "02", "partnerServiceId", [], "Prefix Error");
    if (strlen($reqPrefix) != strlen($this->partner->vaPrefix))
      return $this->snapResp("400", "02", "partnerServiceId", [], "Prefix Length Error:" . strlen($reqVirtualAccountNo) . " vs " . strlen($this->partner->vaLength));
    if ($reqPrefix . $reqCustomerNo != $reqVirtualAccountNo)
      return $this->snapResp("400", "02", "partnerServiceId,customerNo,virtualAccountNo", [], "VirtualAccNo Not Match with Prefix and CustomerNo");
    try {
      $duplicateProcess = (new snapLog())->GetDuplicateLog($this->snapLog);
      if (!empty($duplicateProcess)) {
        $this->snapLog['uniqueKey'] = "DUPLICATE!-" . date('YmdHis') . '-' . $this->snapLog['uniqueKey'];
        return $this->snapResp('409', '01', "inquiryRequestId", [], "Duplicate {$this->serviceCode}:$reqInquiryRequestId");
      }
      $VaAccount = (new virtualAccount())->findVirtualAcc($reqCustomerNo, $this->partner);
      if (empty($VaAccount) || empty($VaAccount["no_rekening"]))
        return $this->snapResp('404', '12', "", [], "VA $reqVirtualAccountNo Not Found");
      if ($VaAccount['status_aktif'] == '3')
        return $this->snapResp('403', '18', "", [], "VA status aktif=3");
      if ($VaAccount['paid'])
        return $this->snapResp('404', '14', "", [], "Tagihan terbayarkan");
      $vaData = [
        "inquiryStatus" => "00",
        "inquiryReason" => [
          "indonesia" => "Sukses",
          "english" => "Success",
        ],
        "partnerServiceId" => $this->body('partnerServiceId'),
        "customerNo" => $this->body('customerNo'),
        "virtualAccountNo" => $this->body('virtualAccountNo'),
        "virtualAccountName" => $VaAccount['virtualAccountName'],
        "inquiryRequestId" => $this->body('inquiryRequestId'),
        "virtualAccountTrxType" => $virtualAccountTrxType[$VaAccount['type']]
      ];
      if (isset($VaAccount['totalAmount'])) $vaData['totalAmount'] = $VaAccount['totalAmount'];
      $vaData["additionalInfo"] = $this->generateAddInfo([
        'VaAccount' => $VaAccount,
      ]);
      $data['virtualAccountData'] = $vaData;
      return $this->snapResp('200', '00', "", $data);
    } catch (\Throwable $th) {
      return $this->snapResp("500", "00", "", [], "SELECT-LOG: $th");
    }
  }
  protected function processReqAddInfo($data = [])
  {
    return parent::processReqAddInfo($data);
  }
  protected function generateAddInfo($data = [])
  {
    return parent::generateAddInfo($data);
  }
  protected function setClientSecret()
  {
    $clientSecret = getValueByKey($this->request['body'], 'passApp');
    (new partner())->setClientSecret($clientSecret, $this->partner->clientID);
    $this->partner->clientSecret = $clientSecret;
  }
}
