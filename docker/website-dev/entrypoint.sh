#!/bin/sh
# Eskoofy branding website dev entrypoint.
#
# Same contract as docker/php-dev/entrypoint.sh: the MariaDB init script runs
# only on a fresh volume, so schema changes on an existing volume are applied
# here on every boot (the schema is CREATE TABLE IF NOT EXISTS-safe).

set -eu

i=0
until php -r '
  try {
    new PDO(
      sprintf("mysql:host=%s;port=%s", getenv("DB_HOST") ?: "db", getenv("DB_PORT") ?: "3306"),
      getenv("DB_USERNAME") ?: "esk",
      getenv("DB_PASSWORD") ?: "eskpw"
    );
  } catch (Throwable $e) { exit(1); }
' 2>/dev/null; do
  i=$((i + 1)); [ "$i" -gt 30 ] && { echo "DB did not come up" >&2; exit 1; }
  sleep 2
done

SCHEMA="${ESK_SCHEMA_FILE:-/var/www/database/schema.sql}"
if [ -f "$SCHEMA" ]; then
  echo "==> Applying schema.sql (idempotent) to the container DB..."
  php -r '
    $pdo = new PDO(
      sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST") ?: "db", getenv("DB_PORT") ?: "3306", getenv("DB_DATABASE") ?: "eskoofy_website"),
      getenv("DB_USERNAME") ?: "esk",
      getenv("DB_PASSWORD") ?: "eskpw",
      [PDO::MYSQL_ATTR_MULTI_STATEMENTS => true]
    );
    $sql = file_get_contents(getenv("ESK_SCHEMA_FILE") ?: "/var/www/database/schema.sql");
    if ($sql !== false && trim($sql) !== "") { $pdo->exec($sql); }
    echo "schema.sql applied\n";
  '
else
  echo "==> No schema.sql found at $SCHEMA — skipping."
fi

echo "==> Starting: $*"
exec "$@"