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
    /* loop throught key */
    // !Warning when Key in $data and $where have same
    $update = implode(',', array_map(fn($field) => "$field = :$field", array_keys($data)));
    $where  = implode(' AND ', array_map(fn($field) => "$field = :$field", array_keys($wheres)));

    $this->query("UPDATE $table SET $update WHERE $where");
    array_walk($data, fn($value, $key) => $this->bind($key, $value));
    array_walk($wheres, fn($value, $key) => $this->bind($key, $value));

    return $this->execute();
  }
}
