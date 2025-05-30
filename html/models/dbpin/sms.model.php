<?php

namespace App\models;

use App\Database;

class sms extends Database
{
  private $table = 'text_sms';
  public function __construct()
  {
    parent::__construct('cbs');
  }

  public function sendSMS(array $data, string $phone, int $CreatorID)
  {
    $textSms = $this->getTextSetoran();
    $message = mergeSMSTextTemplate($textSms, '<<', '>>', $data);
    $query = <<<SQL
      INSERT INTO gammu.outbox (DestinationNumber,TextDecoded,CreatorID)
      VALUES (:DestinationNumber,:TextDecoded,:CreatorID)
    SQL;

    return $this->query($query)
      ->bind('DestinationNumber', $phone)
      ->bind('TextDecoded', $message)
      ->bind('CreatorID', $CreatorID)
      ->execute();
  }
  private function getTextSetoran()
  {
    return $this->query("SELECT tab_setoran FROM {$this->table}")->resultSingle()['tab_setoran'];
  }
}
