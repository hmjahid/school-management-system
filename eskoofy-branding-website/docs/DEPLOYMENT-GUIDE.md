# Eskoofy Website — Deployment Guide

> Product: `eskoofy-branding-website` (marketing site + **license server**, raw PHP, no
> Composer at runtime) · Always deployed as the **single `int` profile** (English/USD/UTC with
> an en/bn language switcher). Install & config live in
> [`SETUP-GUIDE.md`](./SETUP-GUIDE.md); sizing in [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md).

Ship the **`eskoofy-branding-website-int.zip`** artifact (`build/export.sh website int`). It
is the only artifact for this component (the site has no `bd` profile — `bd` simply produces
the same `int` artifact).

---

## 1. Prerequisites

- A host meeting [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md) (PHP 8.2+, MySQL, rewrites).
- A database (`eskoofy_website`) and credentials.
- TLS — this host is the **license endpoint**; product installs call it over HTTPS, so a valid
  cert is not optional.
- SMTP access for transactional email (license reminders, package/document emails).

---

## 2. Upload & install

```bash
cd /var/www
unzip /path/to/eskoofy-branding-website-int.zip
mv eskoofy-branding-website-int website
cd website

# database
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS eskoofy_website CHARACTER SET utf8mb4;"
mysql -u root -p eskoofy_website < database/schema.sql

# permissions
sudo chown -R www-data:www-data storage
```

Shared hosting: upload via File Manager/FTP, point the domain's docroot at `public/`
(or rely on the root `.htaccess` forwarding), then import the schema through phpMyAdmin.

## 3. Configuration tuning (`.env`)

| Key | Production value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | your real `https://` URL — the license clients call this |
| `ESKOOFY_VARIANT` | `int` (always) |
| `DB_*` | production database credentials |
| `GATEWAY_DEFAULT` | `manual` or your primary gateway |
| `GATEWAY_BDT_RATE` | conversion rate used when a BD buyer is currency-affected |
| `LICENSE_PRODUCT_SECRET` | **strong shared secret** — product installs must send it in the `X-Product-Secret` header to activate/validate |
| `LICENSE_MAX_ACTIVATIONS` | activation cap per license (default 3) |
| `GEO_LANG_ENABLED` / `GEO_IP_API_URL` | location-based default language (optional) |

## 4. Web server

Docroot **`public/`** — front controller `public/index.php`.

nginx:

```nginx
server {
    listen 443 ssl;
    server_name license.eskoofy.com;
    root /var/www/website/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

Apache: enable `mod_rewrite` + the shipped `.htaccess` (it already blocks sensitive files).

## 5. License server operations

- The `/api/v1` contract (`activate/validate/deactivate/status/ping`) must stay reachable and
  stable — customers' installs depend on it (see [`../../docs/design/LICENSE/`](../../docs/design/) for API docs).
- If `LICENSE_PRODUCT_SECRET` is set, configure the **same secret** in each product's client.
- Monitor the **activity log** (`/admin/activity`) and **visitor log** (`/admin/visitors`)
  for abuse; `visitors.logging_enabled` toggles visitor capture.
- License reminders (`LicenseReminderService`) run from the admin dashboard — no cron required,
  but check the admin regularly or wire a periodic HTTP hit to the dashboard.

## 6. Backups, packages & documents

- **Backups:** Admin → Backup creates full (SQL + public assets), licenses and users backups as
  ZIPs without needing `mysqldump`; download them off-host.
- **Packages:** upload product ZIPs under `/admin/packages` and **client documents** (manuals,
  setup guides) under `/admin/client-documents`; email them to licensed clients.
- Off-host: copy `storage/backups/`, `storage/packages/` and `storage/documents/` regularly.

## 7. Update / rollback

**Update:**

```bash
cd /var/www
unzip -o /path/to/new/eskoofy-branding-website-int.zip
# re-export storage permissions if the archive reset ownership
sudo chown -R www-data:www-data website/storage
```

Schema changes ship inside `database/schema.sql` — apply additive upgrades carefully
(CREATE TABLE IF NOT EXISTS / ALTER); check release notes before overwriting.

**Rollback:** re-extract the previous `.zip`; restore the DB from the last admin backup if the
schema changed.

## 8. Verification

```bash
curl -I https://license.eskoofy.com/                      # 200
curl -s https://license.eskoofy.com/api/v1/ping           # {"success":true,...}
curl -I https://license.eskoofy.com/manifest.json         # 200 PWA route
# CSRF + dotfile protection:
curl -s -o /dev/null -w "%{http_code}" https://license.eskoofy.com/.env   # 403/404
```

Go-live checklist: [`../../docs/operations/PRODUCTION-CHECKLIST.md`](../../docs/operations/PRODUCTION-CHECKLIST.md).

## Related docs

- [`SETUP-GUIDE.md`](./SETUP-GUIDE.md) — install & configure
- [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md) — sizing
- [`USER-MANUAL.md`](./USER-MANUAL.md) — usage (site, API, admin)
- [`../../README.md`](../../README.md) — monorepo overview
- [`../../docs/operations/DEMO-CREDENTIALS.md`](../../docs/operations/DEMO-CREDENTIALS.md)