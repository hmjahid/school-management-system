#!/bin/sh
# Eskoofy branding website dev entrypoint.
#
# Same contract as docker/php-dev/entrypoint.sh: the MariaDB init script runs
# only on a fresh volume, so schema changes on an existing volume are applied
# here on every boot — `./docker/dev.sh restart website` is what makes a
# database/schema.sql edit visible.
#
# The per-statement applier (docker/apply-schema.php) is used rather than a
# single multi-statement exec because a whole-file exec is not idempotent: any
# statement that is not `CREATE TABLE IF NOT EXISTS` runs again on every boot and
# aborts the file on the second. That is what crash-looped the php-dev container
# before this was shared. The website schema happens to be CREATE-TABLE-only
# today, but it will not stay that way.

set -eu

# Wait for the database to accept connections (first boot races db startup).
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
