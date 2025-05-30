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
  public function is_registering()
  {
    $this->query("SELECT * FROM {$this->table} WHERE credential_id IS NULL");
    return $this->resultSingle();
  }
  public function get_passkey($credential_id)
  {
    return $this->query("SELECT * FROM {$this->table} WHERE credential_id=:credential_id")
      ->bind('credential_id', $credential_id)
      ->resultSingle();
  }
  public function regist($data)
  {
    $query = <<<SQL
      UPDATE {$this->table}
      SET
        credential_id=:credential_id,
        public_key=:pubkey,
        created_at=DATETIME('now', 'localtime')
      WHERE
        credential_id IS NULL
      SQL;
    $this->query($query);
    $this->bind('credential_id', bin2hex($data->credentialId));
    $this->bind('pubkey', $data->credentialPublicKey);
    $this->execute();
    return $this->affectedRows();
  }
}
