# Eskoofy PHP — Deployment Guide

> Product: `eskoofy-php-app` (raw PHP 8.2, MySQL, no Composer at runtime) · Production
> deployment for the `bd` (Bangladesh) or `int` (international) variant. Intended target:
> **shared hosting** and low-cost VPS. Install & config live in
> [`SETUP-GUIDE.md`](./SETUP-GUIDE.md); sizing in [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md).

Ship the **variant artifact** `eskoofy-php-app-bd.zip` / `eskoofy-php-app-int.zip`
(`build/export.sh php bd|int`) — it already carries the matching `.env`, locale packs and
branding.

---

## 1. Prerequisites

- A host meeting [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md) (PHP 8.2+, MySQL,
  `mod_rewrite` or nginx, cron).
- A **database created** (e.g. `eskoofy`) and DB credentials.
- TLS for the domain (Let's Encrypt/Cloudflare; many cPanel hosts include AutoSSL).

---

## 2. Upload & install

**Shared hosting (cPanel/Plesk)** — typical flow:

1. Zip the artifact contents and use File Manager or FTP to upload to your public root
   (or a folder like `public_html/eskoofy`).
2. Unzip so `public/` is reachable.
3. Create a MySQL database + user and grant privileges.
4. Import the schema:
   ```bash
   mysql -u USER -p eskoofy < database/schema.sql
   ```
5. Edit `.env` → set `DB_*`, `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false`.
6. Create the super admin (a fresh schema seeds **no users**):
   ```bash
   php database/seed_admin.php
   ```
7. Make `storage/` writable (chmod / FTP permissions).

**VPS (nginx)**:

```bash
cd /var/www
unzip /path/to/eskoofy-php-app-int.zip
mv eskoofy-php-app-int eskoofy
cd eskoofy
mysql -u root -p < database/schema.sql
php database/seed_admin.php
sudo chown -R www-data:www-data storage
```

## 3. Configuration tuning (`.env`)

| Key | Production value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | your real `https://` URL |
| `ESKOOFY_VARIANT` | `bd` or `int` (already set by the artifact) |
| `DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD` | production DB credentials |
| `APP_TIMEZONE` | `Asia/Dhaka` for `bd`, `UTC` for `int` |
| Gateway keys | bKash/Nagad/Rocket (bd) or Stripe/PayPal/Paddle (int) per `config/payment.php` |

## 4. Web server

**Document root must be `public/`.**

nginx:

```nginx
root /var/www/eskoofy/public;
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ \.php$ { include fastcgi_params; fastcgi_pass unix:/run/php/php8.2-fpm.sock; }
```

Apache: the shipped `public/.htaccess` (and root `.htaccess` if the host docroot is the
product root) routes everything into `index.php` and blocks `.env`/`.sql`/dotfiles.

## 5. Cron jobs

The app exposes a single entry point: `php public/cron.php`.

```cron
* * * * * cd /var/www/eskoofy && php public/cron.php >> /dev/null 2>&1
```

Handles the scheduled work — recurring payments (daily 01:00) and scheduled notifications
(every 5 min). Without this line those jobs silently don't run.

## 6. Assets (optional)

```bash
npm install && npm run build   # local build
```

If no `public/build/manifest.json` ships, the app falls back to the Tailwind CDN — the build
is optional and usually unnecessary on shared hosting.

## 7. Backups

- On demand: Dashboard → System → Backups (self-contained dumps, `storage/app/backups`).
- Scheduled: the `<cron.php>` schedule includes the daily backup job — confirm it in
  `cron.php` for the variant you run.
- Off-host: copy `storage/app/backups` + `public/uploads` to external storage.
- Restore: re-import the dumped SQL into a fresh database and point `.env` at it.

## 8. TLS

Let's Encrypt (certbot) or host-provided AutoSSL/Cloudflare. Force HTTPS at the server/CDN
and set `SESSION_SECURE_COOKIE`-equivalent behaviour in `.env` if available.

## 9. Update / rollback

**Update:**

```bash
unzip -o /path/to/new/eskoofy-php-app-<variant>.zip   # overwrite files
mysql -u root -p eskoofy < database/schema.sql        # only if the schema changed (safe add)
php database/seed_admin.php                           # idempotent
```

> Do **not** re-run `database/schema.sql` if it drops/alters tables on this install — check
> the release notes. The schema import is destructive on name/type changes.

**Rollback:** the previous `.zip` is the rollback unit — re-extract it and, if schema changed,
restore the DB from your last backup.

## 10. Verification

```bash
curl -I https://eskoofy.example.com/          # 200 + security headers
php -l app/Core/Blade.php                      # quick PHP syntax check
php -r 'echo ini_get("max_execution_time");'   # sanity
```

Go-live checklist: [`../../docs/operations/PRODUCTION-CHECKLIST.md`](../../docs/operations/PRODUCTION-CHECKLIST.md).

## Related docs

- [`SETUP-GUIDE.md`](./SETUP-GUIDE.md) — install & configure
- [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md) — sizing the server
- [`USER-MANUAL.md`](./USER-MANUAL.md) — usage
- [`../../docs/operations/PRODUCTION-CHECKLIST.md`](../../docs/operations/PRODUCTION-CHECKLIST.md)
- [`../../docs/operations/DEMO-CREDENTIALS.md`](../../docs/operations/DEMO-CREDENTIALS.md)