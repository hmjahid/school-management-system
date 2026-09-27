# Cloud backup (automatic, scheduled, multi-provider)

**Status:** Implemented across all four products (Laravel app, raw-PHP port, WordPress theme,
Node.js variant) + marketed on the branding website.

Cloud backup uploads a fresh portable backup (the same `eskoofy-portable-backup` zip the
`dashboard/backup` page creates) to Google Drive, Dropbox, 4shared, Amazon S3 (or any
S3-compatible endpoint), or a local mirror folder — then prunes to a configured retention
count. It is the off-site copy half of the backup story; the portable format half is
documented in `DATA-PORTABILITY.md`.

## The contract (one source of truth)

| Product | Settings + history tables | Dispatcher | Run entry |
|---|---|---|---|
| Laravel app | `cloud_backup_settings` / `cloud_backup_runs` (migrations `2026_09_27_000001/2`) | `backup:cloud:dispatch` every 5 min (`routes/console.php`) | `backup:cloud` |
| Raw-PHP port | same table names in `database/schema.sql` | `public/cron.php --cloud-backup` every 5 min | `scripts/backup-cloud.php` |
| WP theme | `esk_cloud_backup_settings` / `esk_cloud_backup_runs` | `esk_cloud_backup_dispatch` WP-cron every 5 min | `esk_cloud_backup_run()` |
| Node variant | `cloud_backup_settings` / `cloud_backup_runs` (Prisma) | `dispatchCloudBackup()` | `runCloudBackup()` |

All four share the same behaviour so an install can be audited identically regardless of host:

1. **Settings** live in one row: `provider`, encrypted `credentials`, `folder`,
   `is_enabled`, `auto_enabled`, `interval_minutes`, `keep`.
2. **Credentials are encrypted at rest** and never rendered back. A blank input means
   "unchanged"; a provider switch drops foreign secrets (field names collide across
   providers — `google_drive` and `dropbox` both use `refresh_token`).
3. **Interval dispatch**: the dispatcher fires every 5 minutes (fixed cadence); the install's
   own `interval_minutes` decides whether to upload. A "not due" tick writes no history row
   and does not move the clock.
4. **The archive uploaded is always the one just created** — never "the newest file in the
   backups folder", which could be a stale archive from another source.
5. **Retention**: keep the newest `keep` remote files, delete the rest. Local archives are
   pruned by modification time, not name.
6. **Every attempt appends to `cloud_backup_runs`** with `success` / `failed` / `skipped`,
   and updates the settings row (`last_run_at`, `last_status`, `last_error`).

## Providers

| Provider | Credential modes (any one is enough) |
|---|---|
| `local` | none — mirrors the zip into `storage/app/backups/cloud/<folder>/` (or `uploads/eskoofy-cloud-backups/` in WP) |
| `google_drive` | OAuth2 refresh token **or** service account (RS256 JWT assertion, private key never leaves the server) |
| `dropbox` | app key + refresh token **or** a short-lived access token |
| `4shared` | API key (+ username/password for the authenticated variant) |
| `s3` | bucket + access key + secret (+ optional endpoint/region — any S3-compatible service) |

Every remote call is HTTPS-only, TLS-verified, time-bounded and never follows redirects.

## Restore

A cloud copy is downloaded into `storage/app/backups/`, then fed through the same portable
restore path as a local backup: tables are replaced, `storage/app/public/**` is restored, and
a cross-variant restore (`bd` ↔ `int`) reconciles variant-owned config (gateways, currency,
Bengali content). In the app and Node variant restoring a cloud copy additionally requires
the `restore_database` permission alongside `manage_cloud_backup`.

## Admin surfaces

Cloud backup is **inside the Backups page** (a Local / Cloud tab switcher), not a
separate admin page, in every product.

- **Laravel app**: `dashboard/backup?tab=cloud` — the cloud panel is rendered by
  `partials/dashboard/cloud-backup-panel.blade.php` inside the backup page; the old
  `dashboard/cloud-backup` GET route redirects to the Cloud tab.
- **Raw-PHP port**: `/dashboard/backups?tab=cloud` (byte-identical Blade to the app).
- **WP theme**: `views/admin/backup.php` at `/dashboard/backup` includes
  `views/admin/cloud-backup.php` for the Cloud tab.
- **Node variant**: `/dashboard/backup?tab=cloud` (Next.js page renders
  `components/dashboard/CloudBackupPanel.tsx`).
- **Branding website**: `/admin/backup?tab=cloud` (settings in the `settings` table under
  `cloud_backup.*`, run log in `cloud_backup_runs`, CLI
  `scripts/backup-cloud.php` for cron).

## Permissions

`manage_cloud_backup` for every action; restore additionally needs `restore_database`
(app seeder `RolePermissionSeeder`, raw-PHP `config/access.php` + `seed_demo.php`, WP
`esk_can`, Node `lib/permissions.ts`).

## Ops notes

- Generate a strong `APP_KEY` in the raw-PHP port (it encrypts provider credentials at
  rest): `php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"`.
- The dispatcher holds a lock so a slow provider call never overlaps the next tick.
- Failed runs keep their staged archive for inspection, capped at 3.
- Environment fallbacks (`CLOUD_BACKUP_*`, `GOOGLE_DRIVE_*`, `DROPBOX_*`, `FOURSHARED_*`,
  `S3_*`) merge **under** stored credentials, so a host can override without touching the
  admin UI.

## References

- Implementation prompt: `docs/prompts/cloud-backup-watermark-document-designs-prompt.md`
- Portable format: `docs/design/DATA-PORTABILITY.md`
- Ops runbook: `docs/operations/BACKUP-RESTORE.md`