-- PRAGMA foreign_keys = ON;

-- init.sql
CREATE TABLE AUTH (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user TEXT UNIQUE,
  passkey TEXT
);
INSERT INTO AUTH (user) VALUES (null);
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
  nominal_asing TEXT,
  tgl_dibuat TEXT,
  tgl_ditutup TEXT,
  aktif BOOLEAN,
  keterangan TEXT
);

CREATE TABLE TRANSAKSI (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  jenis_transaksi TEXT,
  harta BOOLEAN DEFAULT FALSE,
  barang TEXT NOT NULL,
  rekening_masuk INTEGER,
  rekening_sumber INTEGER,
  tanggal TEXT,
  rutin BOOLEAN DEFAULT FALSE,
  kelompok TEXT,
  nominal INTEGER,
  nominal_asing INTEGER,
  kuantitas INTEGER,
  total INTEGER,
  relasi_transaksi INTEGER,
  penyusutan_bunga INTEGER,
  amount INTEGER,
  attachment TEXT,
  keterangan TEXT,
  review TEXT
  created_at TEXT DEFAULT CURRENT_TIMESTAMP
);