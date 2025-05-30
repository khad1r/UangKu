<?php

namespace App\models;

use App\Database;

class Auth extends Database
{
  private $table = "AUTH";
  public function __construct()
  {
    parent::__construct();
  }
  public function is_registering_device()
  {
    $this->query("SELECT * FROM {$this->table} WHERE user IS NULL");
    return $this->resultSingle();
  }
  public function login($data)
  {
    $error = [];
    foreach ($data as $key => $val) {
      if (empty($val)) $error[$key] = 'Input Ini Tidak Boleh Kosong!!!';
    }
    foreach ($data as &$input) $input = htmlspecialchars($input);

    if (!empty($error)) {
      $_SESSION['InputError'] = $error;
      throw new \Exception("Error Data!!");
    }
    return $this->query("SELECT * FROM {$this->table} WHERE user=:user")
      ->bind('user', $data['user'])
      ->resultSingle();
  }
  public function regist($data)
  {
    $error = [];
    foreach ($data as $key => $val) {
      if (empty($val)) $error[$key] = 'Input Ini Tidak Boleh Kosong!!!';
    }
    foreach ($data as &$input) $input = htmlspecialchars($input);

    if (!empty($error)) {
      $_SESSION['InputError'] = $error;
      throw new \Exception("Error Data!!");
    }
    $query = <<<SQL
      UPDATE {$this->table}
      SET
        user=:user,
        passkey=:passkey
      WHERE
        user IS NULL
      SQL;
    $this->query($query);
    $this->bind('user', $data['user']);
    $this->bind('passkey', $data['passkey']);
    $this->execute();
    return $this->affectedRows();
  }
}
