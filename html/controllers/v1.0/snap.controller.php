<?php

namespace App\Controllers;

use App\Controller;
use App\libs\additionalInfo;
use App\models\snapLog;
use App\libs\snapSecurity;
use App\models\partner;

class snap extends Controller
{
  protected $requiredField = [
    'header' => [
      // 'Authorization', /* conditional */
      'X-TIMESTAMP',
      'X-PARTNER-ID',
      'X-SIGNATURE',
      'X-EXTERNAL-ID',
      'CHANNEL-ID'
    ],
    'params' => [],
    'body' => [],
  ];
  protected $request = [];
  protected $verifyCred = true;
  public $serviceCode = '00';
  public $serviceName = '';
  public $snapLog;
  public $partner;
  public function __construct()
  {
    parent::__construct();
    $this->request['bodyRaw'] = file_get_contents('php://input');
    $this->request['header']  = getallheaders();
    $this->request['body']    = json_decode($this->request['bodyRaw'], true);
    $this->request['params']  = $_GET;
    $this->request['uri']     = $_SERVER["REQUEST_URI"];
    $this->request['http_method'] = $_SERVER["REQUEST_METHOD"];
    $this->checkRequest();
    if ($this->verifyCred) $this->verifySignature_Auth();
  }
  protected function checkRequest()
  {
    /* Check Required Header */
    $missingHeader = array_diff($this->requiredField['header'], array_keys($this->request['header']));
    if (!empty($missingHeader))
      return $this->snapResp("400", "02", implode(', ', $missingHeader), [], "Field " . implode(', ', $missingHeader) . " Not Found");

    if ($this->verifyCred) $this->checkExternalID();
    /* Check Required Body */
    if (!empty($this->requiredField['body'])) {
      if (!is_array($this->request['body']))
        return $this->snapResp("400", "02", '', [], "Body Not Found");
      # code...
      $missingBody = array_diff($this->requiredField['body'], array_keys($this->request['body']));
      if (!empty($missingBody))
        return $this->snapResp("400", "02", implode(', ', $missingBody), [], "Field " . implode(', ', $missingBody) . " Not Found");
    }
  }
  private function verifySignature_Auth()
  {
    $this->setSnapCred($this->header('X-PARTNER-ID'));

    if ($this->partner->dynamicSecret != null)
      $this->setClientSecret();

    if (!in_array($this->serviceCode, $this->partner->allowedService))
      return $this->snapResp('403', '01', '');

    $verifyType = $this->partner->signType == 'symmetric'; /* (symmetric|asymmetric) */

    if ($verifyType) {
      /* Verify Token */
      if (empty($this->header('Authorization')))
        return $this->snapResp("401", "03", "");

      $token = trim(str_replace('Bearer ', '', ($this->header('Authorization') ?? '')));
      if (empty($token)) return $this->snapResp("401", "03", "");

      if (!snapSecurity::verifyToken($token)) return $this->snapResp("401", "01", "");
    }

    /* Verify Signature */
    $stringToSignArr[] = $this->request['http_method'];
    $stringToSignArr[] = $this->request['uri'];
    if ($verifyType) $stringToSignArr[] = $token;
    $stringToSignArr[] = strtolower(bin2hex(hash('sha256', $this->request['bodyRaw'], true)));
    $stringToSignArr[] = $this->header('X-TIMESTAMP');

    $toCompared = implode(':', $stringToSignArr);

    if (!snapSecurity::verifySignature($this->header('X-SIGNATURE'), $toCompared, $this->partner, !$verifyType))
      return $this->snapResp('401', '00', 'Invalid Signature');
  }
  protected function setSnapCred(string $partnerId = '', string $clientId = '')
  {
    $partner = new partner();
    $partnerCred = (!empty($partnerId)) ?
      $partner->getCredByPartnerId($partnerId) :
      $partner->getCredByClientId($clientId);
    if (empty($partnerCred)) return $this->snapResp('404', '16', '');
    $this->partner = $partnerCred;
  }
  protected function snapResp(int|string $httpCode, int|string $caseCode, $reason = "", array $data = [], string $logMsg = '')
  {
    $response = [
      'responseCode' => $httpCode . $this->serviceCode . $caseCode,
      'responseMessage' => $this->getErrorDesc($httpCode, $caseCode) . ((!empty($reason)) ? ", $reason" : ''),
      ...$data
    ];
    $this->logResponse($response, $logMsg);
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Private-Network: true"); // Allow private network access
    header("Access-Control-Allow-Headers: Content-Type, Authorization, CHANNEL-ID, X-EXTERNAL-ID, X-TIMESTAMP, X-PARTNER-ID, X-SIGNATURE");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Content-type:application/json");
    header('Connection: close');
    header('X-TIMESTAMP: ' . getTimeStamp());
    http_response_code(intval($httpCode));
    echo json_encode($response);
    exit();
  }
  private function logResponse(array $response, string $logMsg)
  {
    if (empty($this->snapLog)) return;
    try {
      $data = array_merge(
        $this->snapLog,
        [
          "x_external_id" => $this->header('X-EXTERNAL-ID') . "|" . date('Ymd'),
          "partnerId" => $this->header('X-PARTNER-ID'),
          "responseCode" => $response['responseCode'],
          "response" => json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
          "request" => json_encode([
            'header' => $this->request['header'],
            'body' => $this->request['body']
          ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
          "errorMsg" => $logMsg
        ]
      );
      (new snapLog())->logSnapTransaction($data);
    } catch (\Throwable $th) {
    }
  }
  private function checkExternalID()
  {
    try {
      if (IS_PROD && !((new snapLog())->findExternalID($this->header('X-EXTERNAL-ID') . "|" . date('Ymd'))))
        return $this->snapResp("409", "00");
    } catch (\Throwable $th) {
      return $this->snapResp("500", "00", "", [], "SELECT-LOG: $th");
    }
  }
  private function getErrorDesc($httpCode, $caseCode)
  {
    return $this->responseCodeMap[$httpCode][$caseCode]
      ?? $this->responseCodeMap[$httpCode]['default']
      ?? '';
  }
  protected function setClientSecret()
  {
    $this->partner->clientSecret = $this->partner->dynamicSecret ?? $this->partner->clientSecret;
  }
  protected function generateAddInfo(array $data)
  {
    $data = array_merge(['controller' => $this], $data);
    return call_user_func_array([new additionalInfo, $this->partner->genAddInfoFunc], [$data]);
  }
  protected function processReqAddInfo(array $data)
  {
    $data = array_merge(['controller' => $this], $data);
    return call_user_func_array([new additionalInfo, $this->partner->reqAddInfoFunc], [$data]);
  }
  public function body(int|string $field)
  {
    return $this->request['body'][$field] ?? null;
  }
  public function header(int|string $field)
  {
    return $this->request['header'][$field] ?? null;
  }
  public function params(int|string $field)
  {
    return $this->request['params'][$field] ?? null;
  }

  protected  $responseCodeMap = [
    '200' => [
      '00' => "Successful",
      'default' => "200/NA"
    ],
    '202' => [
      '00' => "Request In Progress",
      'default' => "202/NA"
    ],
    '400' => [
      '01' => "Invalid Field Format",
      '02' => "Invalid Mandatory Field",
      'default' => "400/NA"
    ],
    '401' => [
      '00' => "Unauthorized",
      '01' => "Invalid Token (B2B)",
      '02' => "Invalid Customer Token",
      '03' => "Token Not Found (B2B)",
      '04' => "Customer Token Not Found",
      'default' => "401/NA"
    ],
    '403' => [
      '01' => "Feature Not Allowed",
      '18' => "Inactive Card/Account/Customer",
      'default' => "403/NA"
    ],
    '404' => [
      '02' => "Invalid Routing",
      '12' => "Invalid Bill/Virtual Account",
      '13' => "Invalid Amount",
      '16' => "Partner Not Found",
      '14' => "Paid Bill",
      '19' => "Invalid Bill/Virtual Account Expired",
      'default' => "404/NA"
    ],
    '409' => [
      '00' => "Conflict",
      '01' => "Duplicate partnerReferenceNo",
      'default' => "404/NA"
    ],
    '500' => [
      '00' => "General Error",
      '01' => "Internal Server Error",
      '02' => "External Server Error",
      'default' => "500/NA"
    ],
    '504' => [
      '00' => "Timeout",
      'default' => "504/NA"
    ]
  ];
}
