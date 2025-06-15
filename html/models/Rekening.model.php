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
  public function getSaldoEfektif()
  {
    return $this->query(<<<SQL
      SELECT
        SUM(CASE WHEN jenis_transaksi = 'Pemasukan' THEN nominal * kuantitas ELSE 0 END) -
        SUM(CASE WHEN jenis_transaksi = 'Pengeluaran' THEN nominal * kuantitas ELSE 0 END) AS saldo
      FROM TRANSAKSI
      WHERE harta = 0
    SQL)->resultSingle();
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
  public function allCashFlowGraph($startDate, $endDate)
  {
    return $this->query(<<<SQL
      WITH saldo_dasar AS (
        SELECT
          tanggal,
          SUM(CASE
                WHEN jenis_transaksi = 'Pemasukan' THEN nominal * kuantitas
                WHEN jenis_transaksi = 'Pengeluaran' THEN -nominal * kuantitas
                ELSE 0
              END) AS nilai
        FROM TRANSAKSI
        WHERE tanggal <= :endDate
          AND jenis_transaksi in ('Pemasukan', 'Pengeluaran')
          AND harta = 0  -- optional: exclude non-cash asset movement
        GROUP BY tanggal
      ),
      saldo_berjalan AS (
        SELECT
          tanggal,
          SUM(nilai) OVER (
            ORDER BY tanggal
            ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
          ) AS saldo
        FROM saldo_dasar
      )
      SELECT *
      FROM saldo_berjalan
      WHERE tanggal BETWEEN :startDate AND :endDate
      ORDER BY tanggal;
    SQL)
      ->bind('startDate', $startDate)
      ->bind('endDate', $endDate)
      ->resultSet();
  }
  public function CashFlowGraph($startDate, $endDate)
  {
    return $this->query(<<<SQL
      WITH arus_kas AS (
        SELECT
          tanggal,
          rekening,
          SUM(perubahan) AS arus_kas,
          SUM(perubahan_asing) AS arus_kas_asing
        FROM (
          SELECT tanggal, rekening_masuk AS rekening, nominal * kuantitas AS perubahan, nominal_asing * kuantitas AS perubahan_asing
          FROM TRANSAKSI
          WHERE jenis_transaksi = 'Pemasukan' AND tanggal <= :endDate AND harta = 0

          UNION ALL

          SELECT tanggal, rekening_sumber AS rekening, -nominal * kuantitas AS perubahan, -nominal_asing * kuantitas AS perubahan_asing
          FROM TRANSAKSI
          WHERE jenis_transaksi = 'Pengeluaran' AND tanggal <= :endDate AND harta = 0

          UNION ALL

          SELECT tanggal, rekening_sumber AS rekening, -nominal * kuantitas AS perubahan, -nominal_asing * kuantitas AS perubahan_asing
          FROM TRANSAKSI
          WHERE jenis_transaksi = 'Pindah Buku' AND tanggal <= :endDate AND harta = 0

          UNION ALL

          SELECT tanggal, rekening_masuk AS rekening, nominal * kuantitas AS perubahan, nominal_asing * kuantitas AS perubahan_asing
          FROM TRANSAKSI
          WHERE jenis_transaksi = 'Pindah Buku' AND tanggal <= :endDate AND harta = 0
        )
        GROUP BY tanggal, rekening
      ),
      running_saldo AS (
        SELECT
          tanggal,
          rekening,
          SUM(arus_kas) OVER (
            PARTITION BY rekening
            ORDER BY tanggal
            ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
          ) AS saldo,
          SUM(arus_kas_asing) OVER (
            PARTITION BY rekening
            ORDER BY tanggal
            ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
          ) AS saldo_asing
        FROM arus_kas
      )
      SELECT *
      FROM {$this->view}
      LEFT JOIN running_saldo ON running_saldo.rekening = {$this->view}.id
      WHERE tanggal BETWEEN :startDate AND :endDate
      ORDER BY rekening, tanggal
    SQL)
      ->bind('startDate', $startDate)
      ->bind('endDate', $endDate)
      ->resultSet();
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
