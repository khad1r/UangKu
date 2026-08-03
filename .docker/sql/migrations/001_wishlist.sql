-- Idempotent, additive-only schema changes applied on EVERY container boot
-- (see entrypoint.sh) — unlike sql/init.sql, which only runs once against a
-- brand-new empty database. This is how schema changes reach an already
-- provisioned database (e.g. production on Cloud Run, where there is no
-- shell access to run DDL by hand). Every statement here must be safe to
-- run repeatedly, since there is no migration-version tracking.

CREATE TABLE IF NOT EXISTS WISHLIST (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nama TEXT NOT NULL,
  harga_estimasi NUMERIC,
  link TEXT,
  catatan TEXT,
  created_at TEXT DEFAULT (datetime('now', 'localtime')),
  updated_at TEXT
);
