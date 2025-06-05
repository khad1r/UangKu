-- PRAGMA foreign_keys = ON;

-- init.sql
CREATE TABLE AUTH (
  passkey_id INTEGER PRIMARY KEY AUTOINCREMENT,
  nickname TEXT UNIQUE,
  credential_id TEXT UNIQUE,
  public_key TEXT,
  created_at TEXT
);
INSERT INTO AUTH (nickname) VALUES ('Admin');
CREATE TABLE DB_INFO (
  version_date TEXT,
  fingerprint TEXT
);
INSERT INTO DB_INFO (version_date,fingerprint)
VALUES (
  DATETIME('now', 'localtime'),
  'v1-' || STRFTIME('%Y%m%d%H%M%S', 'now', 'localtime')
);

CREATE TABLE REKENING (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nama TEXT NOT NULL,
  no_asli TEXT,
  nominal_asing TEXT DEFAULT '',
  harta BOOLEAN NOT NULL DEFAULT FALSE,
  tgl_dibuat TEXT,
  tgl_ditutup TEXT,
  aktif BOOLEAN NOT NULL,
  keterangan TEXT
);

CREATE TABLE TRANSAKSI (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  jenis_transaksi TEXT,
  harta BOOLEAN NOT NULL DEFAULT FALSE,
  barang TEXT NOT NULL,
  rekening_sumber INTEGER,
  rekening_masuk INTEGER,
  nominal INTEGER,
  nominal_asing INTEGER,
  kuantitas INTEGER,
  penyusutan_bunga INTEGER,
  rutin BOOLEAN NOT NULL DEFAULT FALSE,
  kelompok TEXT,
  tanggal TEXT,
  relasi_transaksi INTEGER,
  attachment TEXT,
  keterangan TEXT,
  review TEXT
  created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_transaksi_masuk ON TRANSAKSI(rekening_masuk);
CREATE INDEX idx_transaksi_sumber ON TRANSAKSI(rekening_sumber);
CREATE INDEX idx_transaksi_jenis ON TRANSAKSI(jenis_transaksi);
CREATE INDEX idx_transaksi_kelompok ON TRANSAKSI(kelompok);
CREATE INDEX idx_transaksi_barang ON TRANSAKSI(barang);
CREATE INDEX idx_transaksi_keterangan ON TRANSAKSI(keterangan);
CREATE INDEX idx_transaksi_review ON TRANSAKSI(review);
CREATE INDEX idx_transaksi_rutin ON TRANSAKSI(rutin);

CREATE VIEW REKENING_SALDO AS
SELECT r.*,
      IFNULL(SUM(t.saldo), 0) AS saldo,
      IFNULL(SUM(t.saldo), 0) AS saldo_asing,
FROM REKENING r
LEFT JOIN (
  SELECT
    rekening_masuk AS rekening_id,
    (nominal * kuantitas) AS saldo,
    (nominal_asing * kuantitas) AS saldo_asing,
  FROM TRANSAKSI
  WHERE jenis_transaksi IN ('Pemasukan', 'Pindah Buku')
  UNION ALL
    rekening_masuk AS rekening_id,
    -(nominal * kuantitas) AS saldo,
    -(nominal_asing * kuantitas) AS saldo_asing,
  FROM TRANSAKSI
  WHERE jenis_transaksi IN ('Pengeluaran', 'Pindah Buku')
) t ON r.id = t.rekening_id
GROUP BY r.id, r.nama;