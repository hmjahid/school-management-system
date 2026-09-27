#!/bin/sh
# Eskoofy raw-PHP / branding-website dev entrypoint.
#
# The MariaDB init script (`schema.sql` mounted at
# /docker-entrypoint-initdb.d/) runs ONLY on a fresh data volume. On an existing
# volume, schema changes you make on the host never reach the container DB — new
# tables (e.g. cloud_backup_runs) would 404 at runtime even though the code is
# bind-mounted and live.
#
# So before starting the PHP built-in server we apply the product's schema.sql
# to the DB. Every statement is `CREATE TABLE IF NOT EXISTS` / `INSERT IGNORE`,
# so re-applying is idempotent and self-healing for existing volumes.

set -eu

# Wait for MySQL/MariaDB to accept connections (first boot races db startup).
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
      sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST") ?: "db", getenv("DB_PORT") ?: "3306", getenv("DB_DATABASE") ?: "eskoofy_php"),
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