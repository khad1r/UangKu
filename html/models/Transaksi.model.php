<?php

namespace App\models;

use App\Database;
use App\libs\SSP;

class Transaksi extends Database
{
  //   SELECT printf('%04d', id) AS formatted_id, name
  // FROM users;
  private $table = "TRANSAKSI";
  public function __construct()
  {
    parent::__construct();
  }
  public function getById(string|int $id)
  {
    $data = $this
      ->query("SELECT * FROM {$this->table} WHERE printf('%04d', id)=:id")
      ->bind('id', $id)
      ->resultSingle();
    $data['rutin'] = $data['rutin'] === 1;
    $data['harta'] = $data['harta'] === 1;
    return $data;
  }
  public function getAll()
  {
    $rows = $this
      ->query("SELECT * FROM {$this->table}")
      ->resultSet();
    return array_map(
      fn($row) =>
      [
        ...$row,
        'aktif' => isset($row['rutin']) ? $row['rutin'] == 1 : true,
        'harta' => isset($row['harta']) ? $row['harta'] == 1 : false,
      ],
      $rows
    );
  }
  public function searchKelompok(string $search)
  {
    $search = '%' . strtolower($search) . '%';
    return $this
      ->query(<<<SQL
        SELECT kelompok FROM {$this->table}
        WHERE LOWER(kelompok) LIKE :search
        GROUP BY kelompok
        LIMIT 5;
      SQL)
      ->bind('search', $search)
      ->resultSet();
  }
  public function find(string $search)
  {
    $search = trim(strtolower($search));
    $terms = preg_split('/\s+/', $search);
    $conditions = [];
    $params = [];

    foreach ($terms as $index => $term) {
      $param = ":term{$index}";
      $like = '%' . $term . '%';
      $params[$param] = $like;

      $conditions[] = <<<SQL
          (
              LOWER(printf('%04d', {$this->table}.id)) LIKE $param OR
              LOWER(barang) LIKE $param OR
              LOWER(rs.nama) LIKE $param OR
              LOWER(rm.nama) LIKE $param OR
              LOWER(nominal) LIKE $param OR
              LOWER({$this->table}.keterangan) LIKE $param OR
              LOWER({$this->table}.review) LIKE $param
          )
          SQL;
    }

    $whereClause = implode(" OR ", $conditions); // Match all terms (more relevant)

    $sql = <<<SQL
          SELECT
              printf('%04d', {$this->table}.id) AS id,
              barang,
              nominal,
              kelompok,
              rs.nama AS rekening_sumber,
              rm.nama AS rekening_masuk
          FROM {$this->table}
          LEFT JOIN REKENING rs ON {$this->table}.rekening_sumber = rs.id
          LEFT JOIN REKENING rm ON {$this->table}.rekening_masuk = rm.id
          WHERE $whereClause
          ORDER BY {$this->table}.id DESC
          LIMIT 5
      SQL;

    $query = $this->query($sql);

    foreach ($params as $key => $value) {
      $query->bind($key, $value);
    }
    return $query->resultSet();
  }
  public function insertTransaksi($data)
  {
    return $this->insert($this->table, $data)->affectedRows();
  }
  public function updateTransaksi($data, $where)
  {
    return $this->update($this->table, $data, $where)->affectedRows();
  }
  public function deleteTransaksi(string|int $id)
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
    $table = "{$this->table} ";
    $table .= "LEFT JOIN (SELECT id rs_id, nama rs_nama, nominal_asing rs_mata_uang FROM REKENING) rs ON {$this->table}.rekening_sumber = rs_id ";
    $table .= "LEFT JOIN (SELECT id rm_id, nama rm_nama, nominal_asing rm_mata_uang FROM REKENING) rm ON {$this->table}.rekening_masuk = rm_id ";
    $columns = [
      [
        'db' => "printf('%04d', id)",
        // 'dbcol' => 'id',
        'dt' => 'id',
      ],
      [
        'db' => 'jenis_transaksi',
        'dt' => 'jenis_transaksi',
        'formatter' => fn($d, $row) => strtoupper($d)
      ],
      [
        'db' => "barang",
        'dt' => 'barang'
      ],
      [
        'db' => "harta",
        // 'dbcol' => 'harta',
        'dt' => 'harta',
        'formatter' => fn($d, $row) => $d === 1
      ],
      [
        'db' => "penyusutan_bunga",
        'dt' => 'penyusutan_bunga',
        'formatter' => fn($d, $row) => intval($d)
      ],
      [
        'db' => "rs_nama",
        // 'dbcol' => 'rekening_sumber',
        'dt' => 'rekening',
        'formatter' => function ($d, $row) {
          $html = '';
          if (!empty($row['rs_nama'])) $html .= "<div class=\"fw-bold text-danger\"><i class=\"fas fa-sign-out-alt\"></i>&nbsp;{$row['rs_nama']}</div>";
          if (!empty($row['rm_nama'])) $html .= "<div class=\"fw-bold text-success\"><i class=\"fas fa-sign-in-alt\"></i>&nbsp;{$row['rm_nama']}</div>";
          return $html;
        }
      ],
      [
        'db' => "rm_nama",
        // 'dbcol' => 'rm_nama',
        'dt' => 'rekening2',
        'formatter' => function ($d, $row) {
          $html = '';
          if (!empty($row['rs_nama'])) $html .= "<div class=\"fw-bold text-danger\"><i class=\"fas fa-sign-out-alt\"></i>&nbsp;{$row['rs_nama']}</div>";
          if (!empty($row['rm_nama'])) $html .= "<div class=\"fw-bold text-success\"><i class=\"fas fa-sign-in-alt\"></i>&nbsp;{$row['rm_nama']}</div>";
          return $html;
        }
      ],
      [
        'db' => "nominal",
        'dt' => 'nominal',
        'formatter' => fn($d, $row) => intval($d)
      ],
      [
        'db' => "nominal_asing",
        'dbcol' => 'nominal_asing',
        'dt' => 'nominal_asing',
        'formatter' => fn($d, $row) => intval($d)
      ],
      [
        'db' => "coalesce(rs_mata_uang,rm_mata_uang)",
        'dt' => 'mata_uang',
      ],
      [
        'db' => "rutin",
        'dt' => 'rutin',
        'formatter' => fn($d, $row) => $d === 1
      ],
      [
        'db' => "kuantitas",
        'dt' => 'kuantitas',
      ],
      [
        'db' => "kelompok",
        'dt' => 'kelompok',
      ],
      [
        'db' => "tanggal",
        'dt' => 'tanggal',
      ],
      [
        'db' => "keterangan",
        'dt' => 'keterangan',
      ],
      [
        'db' => "review",
        'dt' => 'review',
      ],
      [
        'db' => "relasi_transaksi",
        'dt' => 'relasi_transaksi',
        // 'formatter' => fn($d, $row) => !empty($d) ? BASEURL . "/uploads/$d" : null
        'formatter' => fn($d, $row) => !empty($d) ? sprintf("%04d", $d) : null
      ],
      [
        'db' => "attachment",
        'dt' => 'attachment',
        'formatter' => fn($d, $row) => !empty($d) ? BASEURL . "/uploads/$d" : null
      ],
    ];

    $return = SSP::complex(
      $data,
      $this->conn,
      $table,
      $primaryKey,
      $columns,
    );
    // $return['total'] = $this->totalTransaction($data);
    return $return;
  }
}
