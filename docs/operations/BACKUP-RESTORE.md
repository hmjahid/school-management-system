# Database Backup & Restore

## What is covered

- **Automated backup**: `backup:database` runs daily at 02:00 (see `routes/console.php`).
  - SQLite → crash-consistent copy via `VACUUM INTO ...` (with `SQLite3`), stored as `storage/app/backups/*.sql.gz`.
  - MySQL/MariaDB → `mysqldump --single-transaction` piped through `gzip`.
  - PostgreSQL → `pg_dump` piped through `gzip`.
  - Retention: keeps the latest 7 backups by default (`--keep`).
- **Scheduled alerting**: `queue:monitor-failed` runs every 5 minutes and logs failed queue jobs.

## Creating a backup

```bash
# On-demand backup (keeps 7)
php artisan backup:database

# Keep 30 backups
php artisan backup:database --keep=30

# Verify the schedule is registered
php artisan schedule:list
```

Backups are written to `storage/app/backups/<connection>_<timestamp>.sql.gz`.

## Restoring

### SQLite

```bash
# 1. Stop the app / place in maintenance mode
php artisan down

# 2. Uncompress
gunzip -k storage/app/backups/sqlite_YYYY-MM-DD_HHmmss.sql.gz

# 3. Replace the database file
#    In SQLite, the backup *is* a self-contained .sqlite file (VACUUM INTO output).
mv production-backup.sql.gz database/database.sqlite   # only if the backup is a db file

#    NOTE: For MYSQL/PG format dumps, load via the appropriate client (below).
```

### MySQL / MariaDB

```bash
gunzip -k storage/app/backups/mysql_YYYY-MM-DD_HHmmss.sql.gz
mysql -u <user> -p school_db < mysql_backup.sql
```

### PostgreSQL

```bash
gunzip -k storage/app/backups/pgsql_YYYY-MM-DD_HHmmss.sql.gz
psql school_db < pgsql_backup.sql
```

## Portable backups (`backup:run` / `backup:restore`)

`backup:run` writes a **portable** archive (`MANIFEST.json` + `database/tables.json` +
`storage/app/public/**`) that every Eskoofy variant can read — including across variants
(`bd` ↔ `int`). See `docs/design/DATA-PORTABILITY.md` for the full contract.

```bash
php artisan backup:run                    # → storage/app/backups/backup_<ts>_<rand>.zip
php artisan backup:restore backup_….zip   # interactive confirm
php artisan backup:restore backup_….zip --force
```

Restore behavior worth knowing:

- **Encrypted credentials** (`payment_gateways.*`, `website_settings.bkash_*`/`twilio_*`/`mail_*`)
  are kept only when the backup was taken with the **same `APP_KEY`**. Restoring a backup
  whose `cipherFingerprint` differs clears those columns (the command warns per column) —
  re-enter the credentials after restore. Never restore a backup from another install and
  expect its secrets to work.
- **Cross-variant restores** (`eskoofyVariant` in the manifest differs from
  `ESKOOFY_VARIANT`) reconcile variant-owned config to the receiving profile: the receiving
  variant's gateways are activated (with its currency), `website_settings` currency /
  default payment method / locale are reset, and int restores strip Bengali-only content
  and the BD admission `payment_number`. Business data (students, staff, fees, results)
  always restores verbatim.
- Restoring an archive on the **same variant & same key** is byte-for-byte as before.

## Restore verification (required monthly)

1. Restore the latest backup into a scratch database (ideally staging).
2. Run migrations-facing sanity checks:

   ```bash
   php artisan migrate:status        # schema matches
   php artisan tinker --execute="echo \App\Models\User::count().' users';"
   php artisan db:table users        # sample rows readable
   ```

3. Attempt a login in staging and load the dashboard.
4. Confirm `failed_jobs`, `sessions`, and audit log rows exist.
5. **Sign off** in the runbook log: date, backup used, restored DB, result.

## Monitoring

- Queue failures are logged by `queue:monitor-failed`; forward them to Slack via `LOG_SLACK_WEBHOOK_URL`.
- Restore drills should use the **same** mechanism you rely on (`schedule:run` in cron/systemd).