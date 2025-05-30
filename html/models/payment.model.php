<?php

namespace App\models;

use App\Database;

class payment extends Database
{
  public function pay(array $trans, object $client)
  {
    $transStatus = false;
    try {
      /**  EXECUTING */
      /* Insert transaction */
      $transaksi = [
        'account_number' => $trans['no_rekening'],
        'virtualAccount' => $trans['virtualAccount'],
        'amount' => $trans['amount'],
        'currency' => $trans['currency'],
        'partnerReferenceNo' => $trans['partnerReferenceNo'],
        'serviceName' => $trans['serviceName'],
        'date' => $trans['date'],
        'partnerId' => $client->partnerId,
      ];
      $this->insert('trans', $transaksi);
      $transStatus = true;
      /* do the Rest */
    } catch (\Exception $e) {
      if (!$transStatus) {
        throw new \Exception("Failed Before Insert Transaction: " . $e->getMessage());
      }
      error_log('Error: ' . $e->getMessage());
    }
    return $transStatus;
  }
}
