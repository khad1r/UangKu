<?php

namespace App\Controllers;

use App\models\payment as ModelsPayment;
use App\models\snapLog;
use App\models\virtualAccount;

class payment extends snap
{
  public function __construct()
  {
    $this->serviceCode = '25';
    $this->serviceName = 'PAYMENT';
    $this->requiredField['body'] = [
      'partnerServiceId',
      'customerNo',
      'virtualAccountNo',
      'virtualAccountName',
      'paidAmount',
      'paymentRequestId',
    ];
    parent::__construct();
  }
  public function index()
  {
    header("Access-Control-Allow-Methods: POST");

    $reqPrefix              = trim($this->body('partnerServiceId'));
    $reqCustomerNo          = $this->body('customerNo');
    $reqVirtualAccountNo    = trim($this->body('virtualAccountNo'));
    $reqVirtualAccountName  = $this->body('virtualAccountName');
    $reqPaidAmount          = $this->body('paidAmount');
    $reqPaymentRequestId    = $this->body('paymentRequestId');
    $uniqueKey              = "{$this->serviceCode}:$reqPaymentRequestId";

    $this->snapLog = [
      "uniqueKey" => $uniqueKey,
      "serviceName" => $this->serviceName,
      "transactionId" => $reqPaymentRequestId,
      "virtualAccount" => $reqVirtualAccountNo,
      "amount" => $reqPaidAmount['value'] ?? null,
    ];
    if (!$this->processReqAddInfo())
      return $this->snapResp("500", "02", "", [], "processReqAddInfo : {$this->partner->reqAddInfoFunc}");

    if (!isset($reqPaidAmount['value']))
      return $this->snapResp("400", "02", "paidAmount.Value", [], "Field paidAmount.Value Not Found");
    if (!isset($reqPaidAmount['currency']))
      return $this->snapResp("400", "02", "paidAmount.currency", [], "Field paidAmount.currency Not Found");
    if ($reqPrefix != $this->partner->vaPrefix)
      return $this->snapResp("400", "02", "partnerServiceId", [], "Prefix Error");
    if (strlen($reqPrefix) != strlen($this->partner->vaPrefix))
      return $this->snapResp("400", "02", "partnerServiceId", [], "Prefix Length Error:" . strlen($reqVirtualAccountNo) . " vs " . strlen($this->partner->vaLength));
    if ($reqPrefix . $reqCustomerNo != $reqVirtualAccountNo)
      return $this->snapResp("400", "02", "partnerServiceId,customerNo,virtualAccountNo", [], "VirtualAccNo Not Match with Prefix and CustomerNo");
    if ($reqPaidAmount['currency'] != $this->partner->currency)
      return $this->snapResp("400", "02", "paidAmount.currency", [], "Not Supported Currency");

    try {
      $duplicateProcess = (new snapLog())->GetDuplicateLog($this->snapLog);
      if (!empty($duplicateProcess)) {
        $this->snapLog['uniqueKey'] = "DUPLICATE!-" . date('YmdHis') . '-' . $this->snapLog['uniqueKey'];
        return $this->snapResp('409', '01', "paymentRequestId", [], "Duplicate {$this->serviceCode}:$reqPaymentRequestId");
      }
      $VaAccount = (new virtualAccount())->findVirtualAcc($reqCustomerNo, $this->partner);
      /* Chec Va to Database */
      if (empty($VaAccount) || empty($VaAccount["no_rekening"]))
        return $this->snapResp('404', '12', "Not Found", [], "VA $reqVirtualAccountNo Not Found");
      if ($VaAccount['status_aktif'] == '3')
        return $this->snapResp('403', '18', "", [], "VA status aktif=3");
      if ($VaAccount['paid'])
        return $this->snapResp('404', '14', "", [], "Tagihan terbayarkan");
      if ($VaAccount['type'] == 'bill' && $reqPaidAmount['value'] != $VaAccount['totalAmount']['value'])
        return $this->snapResp('404', '13', "", [], "Pembayaran tidak sesuai Tagihan");

      /* prepare transdata */
      $transData = [
        'virtualAccount' => $reqVirtualAccountNo,
        'no_rekening' => $VaAccount['no_rekening'],
        'amount' => (int) $reqPaidAmount['value'],
        'currency' => $reqPaidAmount['currency'],
        'serviceName' => $this->serviceName,
        'uniqueKey' => $uniqueKey,
        'partnerReferenceNo' => $reqPaymentRequestId,
        'VaInfo' => $VaAccount,
        'date' => date('Y-m-d'),
      ];

      /* do Transaction */
      $transaction = (new ModelsPayment)->pay($transData, $this->partner);
      // $transaction = true;
      if (!$transaction)
        throw new \Exception("Transaction Failed");

      $vaData = [
        "paymentFlagStatus" => "00",
        "paymentFlagReason" => [
          "indonesia" => "Sukses",
          "english" => "Success",
        ],
        "partnerServiceId" => $this->body('partnerServiceId'),
        "customerNo" => $this->body('customerNo'),
        "virtualAccountNo" => $this->body('virtualAccountNo'),
        "virtualAccountName" => $reqVirtualAccountName,
        "paymentRequestId" => $reqPaymentRequestId,
        "paidAmount" => $this->body('paidAmount'),
      ];
      if ($VaAccount['type'] == 'bill')
        $vaData['freeTexts'] = [
          [
            "indonesia" => "Tagihan akan di berhenti paling lama pada akhir hari (04.00 PM)",
            "english" => "The bill will be stopped no later than the end of the day (04.00 PM)",
          ]
        ];
      $data = [
        "virtualAccountData" => $vaData,
        "additionalInfo" => $this->generateAddInfo([
          'VaAccount' => $VaAccount,
        ])
      ];
      return $this->snapResp('200', '00', "", $data);
    } catch (\Throwable $th) {
      return $this->snapResp("500", "00", "", [], "PAYMENT-LOG: $th");
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
}
