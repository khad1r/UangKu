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
  public function lastInsertId()
  {
    return $this->conn->lastInsertId();
  }
  /**
   * Reinitializes the database state without dropping tables.
   * Preserves AUTH, REKENING, and handles Foreign Currency.
   */
  public function reinitialize()
  {
    try {
      $this->beginTransaction();

      // 1. Hitung saldo akhir (IDR & Asing) untuk setiap rekening sebagai Saldo Awal
      $this->query(<<<SQL
        SELECT
            r.id,
            -- Total Saldo dalam IDR[cite: 1, 2]
            (IFNULL(SUM(CASE WHEN t.jenis_transaksi IN ('Pemasukan', 'Pindah Buku') AND t.rekening_masuk = r.id THEN (t.nominal * t.kuantitas) ELSE 0 END), 0) -
            IFNULL(SUM(CASE WHEN t.jenis_transaksi IN ('Pengeluaran', 'Pindah Buku') AND t.rekening_sumber = r.id THEN (t.nominal * t.kuantitas) ELSE 0 END), 0)) as total,

            -- Total Saldo Asing: Hanya dihitung jika rekening memang memiliki label nominal_asing[cite: 1, 2]
            CASE
                WHEN r.nominal_asing IS NULL OR r.nominal_asing = '' THEN 0
                ELSE (
                    IFNULL(SUM(CASE WHEN t.jenis_transaksi IN ('Pemasukan', 'Pindah Buku') AND t.rekening_masuk = r.id THEN (t.nominal_asing * t.kuantitas) ELSE 0 END), 0) -
                    IFNULL(SUM(CASE WHEN t.jenis_transaksi IN ('Pengeluaran', 'Pindah Buku') AND t.rekening_sumber = r.id THEN (t.nominal_asing * t.kuantitas) ELSE 0 END), 0)
                )
            END as total_asing
        FROM REKENING r
        LEFT JOIN TRANSAKSI t ON r.id = t.rekening_masuk OR r.id = t.rekening_sumber
        WHERE r.harta = 0
        GROUP BY r.id, r.nominal_asing; -- Tambahkan r.nominal_asing di sini untuk keamanan standar SQL
      SQL);
      $balances = $this->resultSet();

      // 2. Ambil Transaksi Harta Pemasukan yang belum terealisasi (tanpa relasi pengeluaran)[cite: 1]
      $this->query(<<<SQL
        SELECT * FROM TRANSAKSI
        WHERE harta = 1 AND jenis_transaksi = 'Pemasukan'
        AND id NOT IN (SELECT relasi_transaksi FROM TRANSAKSI WHERE harta = 1 AND jenis_transaksi = 'Pengeluaran' AND relasi_transaksi IS NOT NULL)
      SQL);
      $hartaToKeep = $this->resultSet();

      // 3. Bersihkan data transaksi dan reset auto-increment[cite: 1]
      $this->query("DELETE FROM TRANSAKSI")->execute();
      $this->query("DELETE FROM DB_INFO")->execute();
      $this->query("DELETE FROM sqlite_sequence WHERE name='TRANSAKSI'")->execute();

      // 4. Perbarui info database dengan fingerprint baru[cite: 1]
      $this->insert('DB_INFO', [
        'version_date' => date('Y-m-d H:i:s'),
        'fingerprint'  => 'v1-' . date('YmdHis')
      ]);

      // 5. Masukkan Saldo Awal (termasuk Nominal Asing)[cite: 1, 2]
      foreach ($balances as $b) {
        if ($b['total'] != 0 || $b['total_asing'] != 0) {
          $this->insert('TRANSAKSI', [
            'jenis_transaksi' => 'Pemasukan',
            'barang'          => 'Saldo Awal',
            'rekening_masuk'  => $b['id'],
            'nominal'         => $b['total'],
            'nominal_asing'   => $b['total_asing'], // Tetap tercatat untuk akun valas
            'kuantitas'       => 1,
            'tanggal'         => date('Y-m-d'),
            'keterangan'      => 'Reinitialization Saldo Awal'
          ]);
        }
      }

      // 6. Kembalikan data Harta yang utuh[cite: 1]
      foreach ($hartaToKeep as $h) {
        unset($h['id']);
        $this->insert('TRANSAKSI', $h);
      }

      $this->commit();
      return true;
    } catch (\Exception $e) {
      $this->rollback();
      throw $e;
    }
  }
}
