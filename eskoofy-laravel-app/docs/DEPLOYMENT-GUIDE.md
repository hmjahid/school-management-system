# Eskoofy App — Deployment Guide

> Product: `eskoofy-laravel-app` (Laravel 12) · Production deployment for the `bd` (Bangladesh)
> or `int` (international) variant. For install & configuration see
> [`SETUP-GUIDE.md`](./SETUP-GUIDE.md); for hardware see
> [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md).

Deploying Eskoofy means shipping the **variant artifact** (`bd` or `int`) produced by the
build box (`build/export.sh app bd|int`), then running the standard Laravel go-live steps.
The artifact already contains the correct `.env` profile, locale packs and branding.

---

## 1. Prerequisites on the server

- A server meeting [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md) (PHP 8.2+, nginx/Apache,
  MySQL/SQLite, Composer, Node for the asset build).
- The variant artifact for your region: `eskoofy-laravel-app-bd.zip` or `eskoofy-laravel-app-int.zip`.
- DNS + TLS ready.
- A database created for the app (e.g. `CREATE DATABASE eskoofy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`).

---

## 2. Upload & install

```bash
# example: /var/www/eskoofy
sudo mkdir -p /var/www/eskoofy && cd /var/www/eskoofy
sudo unzip /path/to/eskoofy-laravel-app-bd.zip
sudo mv eskoofy-laravel-app-bd app

cd app
sudo chown -R www-data:www-data storage bootstrap/cache
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build        # compile Vite/Tailwind assets
```

> The artifact ships `.env` already shaped for the variant — **verify it** before going live
> (edit `APP_URL`, `APP_ENV`, database credentials, secrets). Never ship a real `.env` that
> contains secrets; the artifact's `.env` is `.env.example` + profile overrides.

## 3. Configuration tuning

| Key | Production value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` — **never** `true` in production |
| `APP_URL` | your real `https://` URL (this is the canonical site URL) |
| `APP_KEY` | fresh app key (keep it secret) |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | the first admin account (created on seed) |
| `ALLOW_DEMO_DATA` | `false` unless you explicitly want the demo accounts |
| `DB_*` | production database credentials |
| `SESSION_SECURE_COOKIE` | `true` (HTTPS only) |
| `LOG_LEVEL` | `error` (keeps logs small) |
| `QUEUE_CONNECTION` | `database` or `redis` — never `sync` unless shared hosting |
| `TRUSTED_PROXIES` | your load balancer / Cloudflare CIDRs |

Payment secrets (bKash/Nagad/Rocket or Stripe/PayPal/Paddle keys) are managed in the
dashboard `payment_gateways` table **or** `config/payment.php` env fallbacks — set them in
`.env`, never commit them.

## 4. Database & seed

```bash
php artisan key:generate
php artisan migrate --force              # apply schema (no destructive reset in prod)
php artisan db:seed --force              # roles/permissions + admin (guarded in production)
```

`db:seed` runs `RolePermissionSeeder` / `AdminUserSeeder`. With `APP_ENV=production` demo
users are skipped unless `ALLOW_DEMO_DATA=true`; weak admin passwords are refused.

## 5. Cache & optimize

```bash
php artisan optimize                     # config, route, view caches
php artisan storage:link                 # public file symlink for uploads/media
```

Run these **after every deploy** so the new code/config is cached.

## 6. Web server (nginx)

Point the document root at `public/`:

```nginx
server {
    listen 443 ssl;
    server_name eskoofy.example.com;

    root /var/www/eskoofy/app/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known).* { deny all; }

    # security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
}
```

Apache alternative: `AllowOverride All` with the shipped `.htaccess`, docroot `public/`,
`mod_rewrite` enabled.

## 7. Scheduled jobs & queue workers

Eskoofy ships a Laravel scheduler. Declare one cron line:

```cron
* * * * * cd /var/www/eskoofy/app && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled jobs include the daily database backup (`backup:database`, 02:00) and the
failed-queue monitor (`queue:monitor-failed`, every 5 min).

Queue worker as a systemd unit (or supervisor):

```ini
# /etc/systemd/system/eskoofy-queue.service
[Unit]
Description=Eskoofy queue worker
After=network.target mysql.service

[Service]
User=www-data
WorkingDirectory=/var/www/eskoofy/app
ExecStart=/usr/bin/php /var/www/eskoofy/app/artisan queue:work --sleep=3 --tries=3
Restart=always

[Install]
WantedBy=multi-user.target
```

## 8. Backups

- Automatic: the scheduler runs `php artisan backup:database` daily at 02:00; dumps land in
  `storage/app/backups` (SQLite/MySQL/PostgreSQL compatible) and are pruned to 7.
- On demand: `php artisan backup:database --keep=30` or via Dashboard → System → Backups.
- Off-host: rsync / object-store `storage/app/backups` + `public/uploads` off the server.
- Restore procedure: [`../../docs/operations/BACKUP-RESTORE.md`](../../docs/operations/BACKUP-RESTORE.md).

## 9. TLS

Use Let's Encrypt (certbot) or Cloudflare edge certificates; set `SESSION_SECURE_COOKIE=true`
and force HTTPS (redirect HTTP → HTTPS at the server or CDN).

## 10. Update / rollback

**Update:**

```bash
cd /var/www/eskoofy
sudo unzip -o /path/to/new/eskoofy-laravel-app-<variant>.zip
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --force
php artisan optimize
sudo systemctl reload nginx && sudo systemctl restart eskoofy-queue
```

**Rollback:** the previous `.zip` (or a `git tag`) is the rollback unit — re-extract it,
re-run migrate to the schema it ships (or restore the DB from the last backup), re-optimize.

## 11. Verification after deploy

```bash
php artisan about                         # Laravel env sanity
php artisan route:list | wc -l            # routes registered
php artisan schedule:test                 # scheduler fires correctly
curl -I https://eskoofy.example.com/      # 200 + security headers
```

Go-live checklist: [`../../docs/operations/PRODUCTION-CHECKLIST.md`](../../docs/operations/PRODUCTION-CHECKLIST.md).
Runbooks (deploy/rollback/rotation): [`../../docs/operations/RUNBOOKS.md`](../../docs/operations/RUNBOOKS.md).

## Related docs

- [`SETUP-GUIDE.md`](./SETUP-GUIDE.md) — install & configure
- [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md) — sizing the server
- [`USER-MANUAL.md`](./USER-MANUAL.md) — usage
- [`../../docs/operations/PRODUCTION-CHECKLIST.md`](../../docs/operations/PRODUCTION-CHECKLIST.md)
- [`../../docs/operations/RUNBOOKS.md`](../../docs/operations/RUNBOOKS.md)
- [`../../docs/operations/BACKUP-RESTORE.md`](../../docs/operations/BACKUP-RESTORE.md)
- [`../../docs/operations/PAYMENT-DEPLOYMENT.md`](../../docs/operations/PAYMENT-DEPLOYMENT.md)