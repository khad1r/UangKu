<?php

namespace App\models;

use App\Database;

class partner extends Database
{
  private $table = 'partner';
  private function decodeJson(array|bool $array)
  {
    if (empty($array)) return $array;
    $partner = (object) $array;
    $partner->AddInfo = json_decode($partner->AddInfo, true);
    $partner->allowedService = json_decode($partner->allowedService, true);
    return $partner;
  }
  public function getCredByClientId(string $clientID)
  {
    $query = <<<SQL
      SELECT *
      FROM {$this->table}
      WHERE clientId = :clientId
    SQL;
    $this->query($query)
      ->bind('clientId', $clientID);
    return $this->decodeJson($this->resultSingle());
  }
  public function getCredByPartnerId(string $partnerId)
  {
    $query = <<<SQL
      SELECT *
      FROM {$this->table}
      WHERE partnerId = :partnerId
    SQL;
    $this->query($query)
      ->bind('partnerId', $partnerId);
    return $this->decodeJson($this->resultSingle());
  }
  public function setClientSecret(string $clientSecret, string $partnerId)
  {
    $query = <<<SQL
      UPDATE {$this->table}
      SET dynamicSecret = :clientSecret
      WHERE partnerId = :partnerId
    SQL;
    $this->query($query)
      ->bind('clientSecret', $clientSecret)
      ->bind('partnerId', $partnerId);
    return $this->execute()->affectedRows() > 0;
  }
}
