<?php

namespace App\models;

use App\Database;

class snapLog extends Database
{
  private $table = 'log';
  public function logSnapTransaction(array $data)
  {
    /* loop throught key */
    $fields = implode(',', array_keys($data));
    $values = implode(',', array_map(fn($v) => ":$v", array_keys($data)));

    /* Filter out the fields you don't want to update */
    $updateFields = array_diff(array_keys($data), ['id', 'uniqueKey', 'transactionId', 'virtualAccount', 'created', 'updated']);
    $update = implode(', ', array_map(fn($field) => "$field = :$field", $updateFields));

    $this->query("INSERT INTO {$this->table} ($fields) VALUES ($values) ON DUPLICATE KEY UPDATE $update");
    /* bind value to the query */
    array_walk($data, fn($value, $key) => $this->bind($key, $value));
    return $this->execute()->affectedRows() > 0;
  }
  public function GetDuplicateLog(array $wheres)
  { // Only allow the specific keys: uniqueKey, transactionId, virtualAccount
    $allowedKeys = ['uniqueKey', 'transactionId', 'virtualAccount'];
    $filteredWheres = array_filter(
      $wheres,
      fn($key) => in_array($key, $allowedKeys),
      ARRAY_FILTER_USE_KEY
    );
    $where  = implode(' AND ', array_map(fn($field): string => "$field = :$field", array_keys($filteredWheres)));
    $this->query("SELECT * FROM {$this->table} WHERE $where AND errorMsg = ''");
    array_walk($filteredWheres, fn($value, $key) => $this->bind($key, $value));
    return $this->resultSingle();
  }
  public function findExternalID($externalID)
  {
    $this->query("SELECT x_external_id FROM {$this->table} WHERE x_external_id = :x_external_id")
      ->bind('x_external_id', $externalID);
    return count($this->resultSet()) === 0;
  }
}
