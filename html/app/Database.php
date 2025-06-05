<?php

namespace App;

class Database
{

  protected $databases = DATABASES;

  protected $conn;
  protected $stmt;

  public function __construct($db_opt = 'default')
  {
    $database = $this->databases[$db_opt];
    $dsn = "sqlite:{$database->path}";

    $option = [
      \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
    ];

    $this->conn = new \PDO($dsn, null, null, $option);
    $this->conn->exec("PRAGMA foreign_keys = ON;");
  }
  protected function query($query)
  {
    $this->stmt = $this->conn->prepare($query);
    return $this;
  }
  protected function bind($param, $value, $type = null)
  {
    if (is_null($type)) {
      if (is_int($value))
        $type = \PDO::PARAM_INT;
      elseif (is_bool($value))
        $type = \PDO::PARAM_BOOL;
      elseif (is_null($value))
        $type = \PDO::PARAM_NULL;
      else
        $type = \PDO::PARAM_STR;
    }
    $this->stmt->bindValue($param, $value, $type);
    return $this;
  }
  protected function execute()
  {
    try {
      $this->stmt->execute();
    } catch (\PDOException $e) {
      if ($this->conn->inTransaction()) {
        $this->rollback();
      }
      throw $e;
    }
    return $this;
  }
  protected function beginTransaction()
  {
    $this->conn->beginTransaction();
  }
  protected function rollback()
  {
    $this->conn->rollback();
  }
  protected function commit()
  {
    $this->conn->commit();
  }
  protected function resultSet()
  {
    $this->stmt->execute();
    return $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
  protected function resultSingle()
  {
    $this->stmt->execute();
    return $this->stmt->fetch(\PDO::FETCH_ASSOC);
  }
  protected function affectedRows()
  {
    return $this->stmt->rowCount();
  }
  protected function insert(string $table, array $data)
  {
    /* loop throught key */
    $fields = implode(',', array_keys($data));
    $values = implode(',', array_map(fn($v) => ":$v", array_keys($data)));

    $this->query("INSERT INTO $table ($fields) VALUES ($values)");
    /* bind value to the query */
    array_walk($data, fn($value, $key) => $this->bind($key, $value));
    return $this->execute();
  }
  protected function update(string $table, array $data, array $wheres)
  {
    // SET clause
    $set = implode(',', array_map(fn($k) => "$k = :$k", array_keys($data)));

    // WHERE clause — handle conflicts with prefix
    $whereParams = [];
    $whereSql = implode(' AND ', array_map(function ($key) use (&$wheres, $data, &$whereParams) {
      $param = array_key_exists($key, $data) ? "w_$key" : $key;
      $whereParams[$param] = $wheres[$key];
      return "$key = :$param";
    }, array_keys($wheres)));

    // Build the query
    $this->query("UPDATE $table SET $set WHERE $whereSql");

    // Bind values for SET
    array_walk($data, fn($value, $key) => $this->bind($key, $value));

    // Bind values for WHERE
    array_walk($whereParams, fn($value, $key) => $this->bind($key, $value));

    return $this->execute();
  }
}
