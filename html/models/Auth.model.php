<?php

namespace App\models;

use App\Database;
use App\libs\SSP;

class Auth extends Database
{
  private $table = "AUTH";
  public function __construct()
  {
    parent::__construct();
  }
  public function is_registering()
  {
    $this->query("SELECT * FROM {$this->table} WHERE credential_id IS NULL AND deleted_at IS NULL");
    return $this->resultSingle();
  }
  public function get_passkey($credential_id)
  {
    return $this->query("SELECT * FROM {$this->table} WHERE credential_id=:credential_id AND deleted_at IS NULL")
      ->bind('credential_id', $credential_id)
      ->resultSingle();
  }
  public function create_empty($data)
  {
    return $this->insert($this->table, $data)->affectedRows();
  }
  public function delete(string $id)
  {
    return $this->update(
      $this->table,
      ['deleted_at' => "DATETIME('now', 'localtime')"],
      ['passkey_id' => $id]
    )
      ->execute()
      ->affectedRows();
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
        AND deleted_at IS NULL
      SQL;
    $this->query($query);
    $this->bind('credential_id', bin2hex($data->credentialId));
    $this->bind('pubkey', $data->credentialPublicKey);
    $this->execute();
    return $this->affectedRows();
  }
  public function datatable($data)
  {
    //Get Post Data
    $primaryKey = "passkey_id";
    $where = " deleted_at IS NULL ";
    // $where .= "AND DATE_FORMAT({$this->table}.created, '%e/%c/%Y') = {$this->conn->quote($data['selectedDate'])} ";
    // $where .= "AND {$this->table}.status IN (0,1) ";
    $columns = [
      [
        'db' => 'passkey_id',
        'dt' => 'passkey_id',
      ],
      [
        'db' => 'nickname',
        'dt' => 'nickname',
      ],
      [
        'db' => "credential_id",
        'dt' => 'credential_id',
        'formatter' => fn($d, $row) => !empty($d)
      ],
      [
        'db' => "credential_id",
        'dt' => 'action',
        'formatter' => fn($d, $row) => $_SESSION['user']['credential_id'] !== $d
      ],
      [
        'db' => "created_at",
        'dt' => 'created_at'
      ],
    ];

    $return = SSP::complex(
      $data,
      $this->conn,
      $this->table,
      $primaryKey,
      $columns,
      $where,
    );
    // $return['total'] = $this->totalTransaction($data);
    return $return;
  }
}
