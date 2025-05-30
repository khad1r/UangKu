<?php

namespace App\libs;

class additionalInfo
{
  static public function defaultResp(array $data)
  {
    return (object) [];
  }
  static public function defaultReq(array $data)
  {
    return true;
  }
  static public function brivaResp(array $data)
  {
    $controller = $data['controller'];
    $partner = &$controller->partner;
    $additionalInfo = [
      'idApp' => $partner->AddInfo["idApp"] ?? ''
    ];
    if ($controller->serviceName == "PAYMENT") {
      if ($data['VaAccount']['type'] == 'bill') {
        $additionalInfo['info1'] = $partner->AddInfo["info1"] ?? '';
      }
    }
    return $additionalInfo;
  }
  static public function brivaReq(array $data)
  {
    $controller = $data['controller'];
    $partner = &$controller->partner;
    /* ..... */
    return true;
  }
}
