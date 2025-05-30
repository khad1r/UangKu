<?php

namespace App\models;

use App\Database;

class mysysid extends Database
{
  private $table = 'mysysid';
  public function __construct()
  {
    parent::__construct('cbs');
  }
  public function getKey(string $keyname,)
  {
    $query = <<<SQL
      SELECT *
      FROM {$this->table}
      WHERE keyname = :keyname
    SQL;
    return $this->query($query)
      ->bind('keyname', $keyname)
      ->resultSingle()['value'];
  }
}
