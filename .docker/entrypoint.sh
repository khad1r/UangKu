#!/bin/sh
set -e

# Define path to SQLite DB
DB_PATH=/var/www/.databases/${SQLITE_DEFAULT_NAME:-default.sqlite}

# Create DB if it doesn't exist
if [ ! -f "$DB_PATH" ]; then
  echo "Initializing SQLite database at $DB_PATH..."
  mkdir -p "$(dirname "$DB_PATH")"
  touch "$DB_PATH"
  chmod 777 "$DB_PATH"

  # Run all SQL files in the init directory
  for f in /docker-entrypoint-initdb.d/*.sql; do
    if [ -f "$f" ]; then
      echo "Running $f..."
      sqlite3 "$DB_PATH" < "$f"
    fi
  done

  echo "SQLite database initialized."
else
  echo "SQLite database already exists at $DB_PATH."
fi

# Apply idempotent schema migrations on every boot, whether the DB was just
# created above or already existed (e.g. a production volume provisioned
# before a new table/column was added). There is no version-tracking table —
# each file here is just re-run every single boot, so every statement MUST
# be one that's a no-op when already applied (CREATE TABLE IF NOT EXISTS,
# etc: SQLite itself checks sqlite_master and skips it, so this is cheap).
# Deliberately low-tech in place of a real migration runner, because
# production runs on Cloud Run where there's no shell access to apply DDL to
# the live database by hand. These live in sql/migrations/ (a subfolder, not
# *.sql directly under sql/) specifically so the glob below can't pick them
# up during the fresh-install loop above and run them twice on first boot.
if [ -d /docker-entrypoint-initdb.d/migrations ]; then
  for f in /docker-entrypoint-initdb.d/migrations/*.sql; do
    if [ -f "$f" ]; then
      echo "Applying migration $f..."
      sqlite3 "$DB_PATH" < "$f"
    fi
  done
fi

chown -R www-data:www-data /var/www
git config --global --add safe.directory /var/www
cd /var/www/html
# composer install
# Start Apache in the foreground
# exec apache2-foreground
exec "$@"
