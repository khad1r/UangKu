#!/bin/bash
set -e

# Define path to SQLite DB
DB_PATH=/var/www/.databases/$SQLITE_DEFAULT_NAME

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
chown -R www-data:www-data /var/www
cd /var/www/html
composer install
# Start Apache in the foreground
exec apache2-foreground
