# Eskoofy Website (Branding + License Server) — Setup Guide

> Component: `eskoofy-website` (raw PHP 8.2+, front-controller app, no framework at runtime) · This guide covers install, database, configuration, license-server settings, web server and maintenance.

- Feature usage (frontend, license API, dashboards): [`USER-MANUAL.md`](./USER-MANUAL.md)
- Run all Eskoofy components locally: [`../../docs/guides/DEVELOPMENT.md`](../../docs/guides/DEVELOPMENT.md)

---

## 1. Prerequisites

| Requirement | Version | Why |
|---|---|---|
| PHP | **8.2+** with `pdo_mysql`, `curl`, `openssl`, `zip`, stream sockets | DB, gateways, Paddle webhook verification, backups/package uploads, SMTP |
| MySQL | 5.7/8 (utf8mb4) via PDO | 15-table licensing schema |
| Web server | Apache (`.htaccess`) or nginx / `php -S` | Document root **must** be `public/` |
| Composer | 2.0+ | **Dev tooling only** (PHPUnit) — not needed at runtime |

Writable directories: `storage/{backups,documents,packages,logs,cache,framework}` (write access
for the web user, e.g. `chmod -R 0775` / `setfacl`).

---

## 2. Quick start

```bash
cd eskoofy-website
cp .env.example .env                        # set APP_URL + DB_* (database: eskoofy_website)
mysql -u root -p eskoofy_website < database/schema.sql
php -S 127.0.0.1:8011 -t public             # canonical dev port is 8011
```

Open **http://127.0.0.1:8011**.

Seeded admin: **`admin@eskoofy.com` / `admin123`** — log in at `/login` and you are redirected
to `/admin`. **Change this password before any production use.**

> The checked-in `.env` currently points at `APP_URL=http://localhost:8001` while the canonical
> dev port (per `docs/guides/DEVELOPMENT.md`) is **8011** — keep `APP_URL` in sync with the port
> you actually serve on.

---

## 3. Database

`database/schema.sql` creates the 15 tables and seeds initial data:

| Table group | Tables |
|---|---|
| Sales & licensing | `plans`, `licenses`, `license_activations`, `payments`, `subscriptions`, `customers` |
| Content | `posts`, `post_categories` |
| Ops | `contact_messages`, `activity_logs`, `settings`, `packages`, `email_templates`, `client_documents` |
| Notifications | `push_notifications`, `push_notification_reads` |

**Seeded data:** the admin customer (`admin@eskoofy.com` / `admin123`, role `admin`), 6 plans
(app/theme/php × monthly/yearly, USD), site settings, 2 post categories and 3 blog posts
(2 published, 1 draft).

Conventions worth knowing: `licenses.product ∈ app|theme|php`,
`plans.period ∈ monthly|yearly`, `payments.variant ∈ int|bd`; `customers`, `licenses` and
`posts` are soft-deleted; several columns are JSON (`plans.features`, `licenses.metadata`,
`payments.raw`, `activity_logs.details`).

---

## 4. Configuration (`.env`)

| Group | Keys | Purpose |
|---|---|---|
| App | `APP_NAME`, `APP_URL`, `APP_ENV`, `APP_DEBUG`, `APP_KEY`, `APP_LOCALE`, `APP_TIMEZONE` | Core identity; keep `APP_URL` in sync with the served port |
| Variant / auth | `ESKOOFY_VARIANT=int`, `AUTH_TABLE=customers` | The website is always `int`; customers authenticate against the `customers` table |
| Database | `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Licensing DB (e.g. `eskoofy_website`) |
| Licensing | `LICENSE_CURRENCY=USD`, `LICENSE_KEY_PREFIX=ESK`, `LICENSE_KEY_CHUNKS=4`, `LICENSE_KEY_LENGTH=4`, `LICENSE_MAX_ACTIVATIONS=3`, `LICENSE_ACTIVITY_LOG=true`, `LICENSE_PRODUCT_SECRET` | Key format, activation cap, and the optional product-secret header |
| Payments | `GATEWAY_DEFAULT=manual`, `GATEWAY_CURRENCY=USD`, `GATEWAY_BDT_RATE=110` | Default gateway + BDT conversion |
| Gateways | `BKASH_*`, `ROCKET_*`, `NAGAD_*`, `STRIPE_*`, `PAYPAL_*`, `PADDLE_*` (each with `*_TEST_MODE`) | Per-gateway credentials |
| Geo | `GEO_LANG_ENABLED=true`, `GEO_IP_API_URL` | Location-based default language |

Config files: `config/app.php`, `config/licensing.php` (key format, currency, grace days,
activity log), `config/gateways.php` (all gateway credential blocks).

> **`LICENSE_PRODUCT_SECRET`:** when set, the activate/validate/deactivate endpoints require a
> matching `X-Product-Secret` header. Set the same secret in each product's client
> configuration. When unset, the check is skipped.

---

## 5. Web server

Point the document root at `public/`. `public/index.php` is the single front controller;
`public/.htaccess` rewrites non-file/non-dir requests to it, blocks dotfiles and sensitive
extensions (`.env`, `.json`, `.md`, `.log`, `.sql`) and sets security headers.

**nginx example:**

```nginx
root /path/to/eskoofy-website/public;
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ \.php$ { include fastcgi_params; fastcgi_pass unix:/run/php/php8.2-fpm.sock; }
```

CSRF is enforced for all state-changing POST/PUT/PATCH/DELETE requests **except** paths under
`/api/` and `/webhooks/` (webhooks are signature-verified instead).

---

## 6. Seeded accounts & access

| Who | Email | Password | Where |
|---|---|---|---|
| Admin | `admin@eskoofy.com` | `admin123` | `/login` → `/admin` |
| Customers | self-register | — | `/register` → `/account` |

Canonical reference: [`../../docs/operations/DEMO-CREDENTIALS.md`](../../docs/operations/DEMO-CREDENTIALS.md).

---

## 7. Testing

```bash
composer test      # DB-free PHPUnit (uses tests/FakeDatabase.php)
```

The suite covers license-API security (domain validation, machine-id sanitisation, throttle
parsing), route registration/view existence, the license manager, i18n/geo, gateways, models
and the PWA. It currently passes **95 tests / 270 assertions**.

---

## 8. Maintenance

- **Backups:** Admin → Backup creates full (SQL + public assets), licenses and users backups
  as ZIPs without requiring `mysqldump`; download from the same page.
- **Cache:** Admin → Cache clears storage caches, opcache/APCu and bumps the cache version.
- **License reminders:** `LicenseReminderService` sends 14-day expiring reminders (idempotent
  per license/day), triggered from the admin dashboard.
- **Packages & documents:** upload product ZIPs (`/admin/packages`) and manuals/setup guides
  (`/admin/client-documents`) and email them to licensed clients.

---

## 9. Troubleshooting

| Symptom | Fix |
|---|---|
| Pages 404 but `/index.php` works | Enable `mod_rewrite` / fix the nginx `try_files`; confirm docroot is `public/` |
| Blank checkout status page | Fixed in this pass (view renamed to `payment_status.php`) — ensure you are on the current tree |
| BD prices show as USD (or vice versa) | Check `GATEWAY_BDT_RATE` and the visitor's detected country |
| Gateways not appearing | A gateway must be **enabled in settings AND configured** (`isConfigured()`) — check `/admin/gateways` |
| Webhook rejected | Verify the Stripe/Paddle signature secret matches and the endpoint is reachable |
| Admin login fails | Re-import `database/schema.sql` (or reset the admin password); the seed hash is in the schema |
| Locale surprises | The geo/language cookie decides the default; a manual `/language/{en|bn}` switch always wins |

---

## Related docs

- [`USER-MANUAL.md`](./USER-MANUAL.md)
- [`../README.md`](../README.md) · [`../AGENTS.md`](../AGENTS.md)
- [`../../docs/design/PAYMENT-MODEL.md`](../../docs/design/PAYMENT-MODEL.md)
- [`../../docs/operations/DEMO-CREDENTIALS.md`](../../docs/operations/DEMO-CREDENTIALS.md)
- [`../../docs/operations/PRODUCTION-CHECKLIST.md`](../../docs/operations/PRODUCTION-CHECKLIST.md)
- [`../../docs/guides/DEVELOPMENT.md`](../../docs/guides/DEVELOPMENT.md)
