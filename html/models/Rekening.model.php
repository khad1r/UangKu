<?php

namespace App\models;

use App\Database;
use App\libs\SSP;

class Rekening extends Database
{
  //   SELECT printf('%04d', id) AS formatted_id, name
  // FROM users;
  private $table = "REKENING";
  private $view = "REKENING_SALDO";
  public function __construct()
  {
    parent::__construct();
  }
  public function getById(string|int $id)
  {
    $data = $this
      ->query("SELECT * FROM {$this->view} WHERE id=:id")
      ->bind('id', $id)
      ->resultSingle();
    $data['aktif'] = $data['aktif'] === 1;
    $data['harta'] = $data['harta'] === 1;
    $data['isAsing'] = !empty($row['nominal_asing']);
    return $data;
  }
  public function getAll()
  {
    $rows = $this
      ->query("SELECT * FROM {$this->view}")
      ->resultSet();
    return array_map(
      fn($row) =>
      [
        ...$row,
        'aktif' => isset($row['aktif']) ? $row['aktif'] == 1 : true,
        'harta' => isset($row['harta']) ? $row['harta'] == 1 : false,
        'isAsing' => isset($row['nominal_asing']) && !empty($row['nominal_asing'])
      ],
      $rows
    );
  }
  public function insertRekening($data)
  {
    return $this->insert($this->table, $data)->affectedRows();
  }
  public function updateRekening($data, $where)
  {
    return $this->update($this->table, $data, $where)->affectedRows();
  }
  public function deleteRekening(string|int $id)
  {
    return $this
      ->query("DELETE FROM {$this->table} WHERE id=:id")
      ->bind('id', $id)
      ->execute()->affectedRows();
  }
  public function datatable($data)
  {
    //Get Post Data
    $primaryKey = "id";
    // $where = " {$this->table}.no_reff = '{$_SESSION['user']['merchant_transaction_reff']}' ";
    // $where .= "AND DATE_FORMAT({$this->table}.created, '%e/%c/%Y') = {$this->conn->quote($data['selectedDate'])} ";
    // $where .= "AND {$this->table}.status IN (0,1) ";
    $columns = [
      [
        'db' => 'id',
        'dt' => 'id',
      ],
      [
        'db' => 'nama',
        'dt' => 'nama',
        'formatter' => fn($d, $row) => strtoupper($d)
      ],
      [
        'db' => "no_asli",
        'dt' => 'no_asli'
      ],
      [
        'db' => "nominal_asing",
        'dt' => 'nominal_asing'
      ],
      [
        'db' => "tgl_dibuat",
        'dt' => 'tgl_dibuat'
      ],
      [
        'db' => "aktif",
        'dt' => 'aktif',
        'formatter' => fn($d, $row) => $d === 1
      ],
      [
        'db' => "harta",
        'dt' => 'harta',
        'formatter' => fn($d, $row) => $d === 1
      ],
      [
        'db' => "tgl_ditutup",
        'dt' => 'tgl_ditutup'
      ],
      [
        'db' => "keterangan",
        'dt' => 'keterangan'
      ],
      [
        'db' => "saldo",
        'dt' => 'saldo'
      ],
      [
        'db' => "saldo_asing",
        'dt' => 'saldo_asing'
      ],
    ];

    $return = SSP::complex(
      $data,
      $this->conn,
      $this->view,
      $primaryKey,
      $columns,
    );
    // $return['total'] = $this->totalTransaction($data);
    return $return;
  }
}
