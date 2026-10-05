#!/bin/sh
# Eskoofy raw-PHP dev entrypoint.
#
# The MariaDB init script (`schema.sql` mounted at
# /docker-entrypoint-initdb.d/) runs ONLY on a fresh data volume. On an existing
# volume, schema changes you make on the host never reach the container DB — new
# tables (e.g. cloud_backup_runs) would 404 at runtime even though the code is
# bind-mounted and live.
#
# So before starting the PHP built-in server we apply the product's schema.sql to
# the DB, statement by statement, via the shared /docker/apply-schema.php. That
# runs on every boot, so `./docker/dev.sh restart php` is what makes a schema
# edit visible.
#
# The previous inline `php -r` with PDO::MYSQL_ATTR_MULTI_STATEMENTS was NOT
# idempotent: it ran the whole file in one exec, and eskoofy-php-app's
# `ALTER TABLE batches ADD CONSTRAINT batches_course_id_foreign` (deferred,
# because `courses` is created later) then failed with ER_DUP_CONSTRAINT_NAME on
# every boot after the first — the container crash-looped and served nothing.
# apply-schema.php applies per statement and tolerates "already exists".

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
echo "==> Applying schema.sql to the container DB (idempotent)..."
php /docker/apply-schema.php "$SCHEMA"

echo "==> Starting: $*"
exec "$@"
