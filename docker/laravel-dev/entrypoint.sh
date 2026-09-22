#!/bin/sh
# Eskoofy Laravel dev entrypoint.
#
# Runs inside docker/laravel-dev (code bind-mounted at /var/www):
#   1. composer install on first boot (reuses the bind-mounted vendor/ after)
#   2. an APP_KEY is always available (session/cookies) without touching .env
#   3. wait for MySQL to accept connections (first boot races db startup)
#   4. migrations applied (idempotent), self-healing the "tables exist but
#      migrations bookkeeping empty" half-migrated-volume case
#   5. an empty database is auto-seeded with the documented demo accounts
# Then it execs the command passed by Compose (`php artisan serve ...`).

set -eu

cd /var/www

if [ ! -f vendor/autoload.php ]; then
  echo "==> vendor/ not found — running composer install..."
  composer install --no-interaction --no-progress --prefer-dist
fi

export APP_KEY="${APP_KEY:-$(php artisan key:generate --show)}"

echo "==> Waiting for database connection..."
i=0
until php -r 'try{new PDO(sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST")?: "db", getenv("DB_PORT")?: "3306", getenv("DB_DATABASE")?: "eskoofy_dev"), getenv("DB_USERNAME")?: "esk", getenv("DB_PASSWORD")?: "eskpw");}catch(Throwable $e){exit(1);}' 2>/dev/null; do
  i=$((i + 1)); [ "$i" -gt 30 ] && { echo "DB did not come up" >&2; exit 1; }
  sleep 2
done

# Some migrations add FKs to tables created later by the app's migration set
# (SQLite tolerates this at DDL time; MySQL does not). Relax FK checks for the
# duration of migrate so the schema orders correctly, then restore enforcement.
# `FOREIGN_KEY_CHECKS` is a session var whose GLOBAL default new connections
# inherit, so it must be toggled via the root connection.
php -r '$p = new PDO(sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST")?: "db", getenv("DB_PORT")?: "3306", getenv("DB_DATABASE")?: "eskoofy_dev"), "root", getenv("MYSQL_ROOT_PASSWORD") ?: "rootpw"); $p->exec("SET GLOBAL FOREIGN_KEY_CHECKS=0");'
restore_fk() { php -r '$p = new PDO(sprintf("mysql:host=%s;port=%s", getenv("DB_HOST")?: "db", getenv("DB_PORT")?: "3306"), "root", getenv("MYSQL_ROOT_PASSWORD") ?: "rootpw"); $p->exec("SET GLOBAL FOREIGN_KEY_CHECKS=1");' 2>/dev/null || true; }
trap restore_fk EXIT

db_check() {
  # tinker/PsySH can't write its config as UID 1000 ($HOME unwritable), so probe
  # with PDO directly instead. `SELECT EXISTS(...)` returns a definite 0/1 row so
  # an empty but existing table is correctly reported as absent.
  php -r '
    $pdo = new PDO(sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST")?: "db", getenv("DB_PORT")?: "3306", getenv("DB_DATABASE")?: "eskoofy_dev"), getenv("DB_USERNAME")?: "esk", getenv("DB_PASSWORD")?: "eskpw");
    try { echo $pdo->query($argv[1])->fetchColumn() ?: "0"; } catch (Throwable $e) { echo "0"; }
  ' "$1" 2>/dev/null || echo "0"
}

# If the migrations bookkeeping table is empty, the DB is either brand-new OR a
# half-migrated leftover (killed first boot → tables exist with no records →
# `migrate --force` would crash on "users already exists"). `migrate:fresh`
# handles both: it wipes any stray tables, then reapplies the schema.
if [ "$(db_check 'SELECT EXISTS(SELECT 1 FROM migrations)')" != "1" ]; then
  echo "==> No migration history — dropping any stray tables and re-migrating..."
  php artisan migrate:fresh --force
else
  echo "==> Applying pending migrations..."
  php artisan migrate --force
fi

# Migrate affects the GLOBAL flag; reset per-session connections back to strict.
php -r '$p = new PDO(sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST")?: "db", getenv("DB_PORT")?: "3306", getenv("DB_DATABASE")?: "eskoofy_dev"), "root", getenv("MYSQL_ROOT_PASSWORD") ?: "rootpw"); $p->exec("SET GLOBAL FOREIGN_KEY_CHECKS=1");'

if [ "$(db_check 'SELECT EXISTS(SELECT 1 FROM users)')" != "1" ]; then
  echo "==> No users yet — seeding demo data (docs/operations/DEMO-CREDENTIALS.md)..."
  php artisan db:seed --force
fi

trap - EXIT

echo "==> Starting: $*"
exec "$@"