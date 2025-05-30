<?php

namespace App\models;

use App\Database;

class kuitansi extends Database
{
  private $table = 'controlno';
  public function __construct()
  {
    parent::__construct('cbs');
  }
  public function updKuitansi(string $modul)
  {
    $query = <<<SQL
      UPDATE {$this->table} set counterno = (counterno + 1)
      WHERE modul = :modul
  SQL;
    $this->query($query)
      ->bind('modul', $modul);
    return $this->execute()->affectedRows() == 1;
  }
  public function getControlno(string $modul)
  {
    $query = <<<SQL
        SELECT *
        FROM {$this->table}
        WHERE modul = :modul
    SQL;
    $this->query($query)->bind('modul', $modul);
    return $this->resultSingle();
  }
  public function getKuitansi(string $modul)
  {
    $kuitansiOptions = $this->getControlno($modul);
    if (empty($kuitansiOptions)) throw new \Exception("GET-CTRL: CounterNo $modul Not Found", 1);

    $counter = str_pad(($kuitansiOptions['CounterNo'] + 1), 5, '0', STR_PAD_LEFT);
    $structure = explode(";", strtoupper(trim($kuitansiOptions['StructuredNo'])));

    $replacements = [
      'YYYY'    => date('Y'),
      'MM'      => date('m'),
      'DD'      => date('d'),
      'NO_URUT' => $counter
    ];
    return implode('', array_map(fn($c) => $replacements[$c] ?? $c, $structure));
  }
}
