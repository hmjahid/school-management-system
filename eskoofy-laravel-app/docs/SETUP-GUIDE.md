# Eskoofy App — Setup Guide

> Product: `eskoofy-laravel-app` (Laravel 12, PHP 8.2+) · This guide covers install, database, configuration, deployment and maintenance.

- Day-to-day usage: [`USER-MANUAL.md`](./USER-MANUAL.md)
- Run all Eskoofy components locally: [`../../docs/guides/DEVELOPMENT.md`](../../docs/guides/DEVELOPMENT.md)

---

## 1. Prerequisites

| Requirement | Version | Why |
|---|---|---|
| PHP | **8.2+** with `mbstring`, `xml`, `ctype`, `json`, `bcmath`, `zip`, `gd` | Laravel runtime + DomPDF |
| Composer | 2.0+ | Dependency management |
| Node.js + npm | 18+ | Vite + Tailwind 4 asset build |
| Database | SQLite (default dev) **or** MySQL 8 / PostgreSQL 10+ | Application data |
| Redis | optional | Cache / queues in production |
| Web server | nginx or Apache (or `php artisan serve`) | Serving `public/` |

---

## 2. Quick start (local, SQLite — no DB server needed)

```bash
cd eskoofy-laravel-app
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed      # SQLite by default
composer dev                          # app :8000 + queue + pail + Vite HMR
```

Open **http://127.0.0.1:8000** → `/login` → `/dashboard`.

Run the pieces individually if you prefer:

```bash
php artisan serve          # http://127.0.0.1:8000
npm run dev                # Vite HMR for Blade/Tailwind assets
php artisan queue:listen   # queue worker
```

### MySQL / PostgreSQL variant

Set the `DB_*` keys in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eskoofy
DB_USERNAME=root
DB_PASSWORD=
```

Then:

```bash
php artisan migrate:fresh --seed
```

---

## 3. Database & seeding

- Migrations live in `database/migrations/`; seeders in `database/seeders/`.
- `php artisan migrate:fresh --seed` runs `DatabaseSeeder`, which calls
  `RolePermissionSeeder`, `AdminUserSeeder`, `DemoUsersSeeder` and the demo content seeders.
- **Demo data guard:** with `APP_ENV=production`, demo users are skipped unless
  `ALLOW_DEMO_DATA=true`, and the seeder refuses weak admin passwords.

### Seeded accounts

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@school.com` | `ADMIN_PASSWORD` in `.env` (`ChangeMe!2026$Tr0ng` in dev) |
| Principal (admin) | `principal@school.com` | `principal123` |
| Teacher | `teacher.john@school.com` | `teach1234` |
| Teacher | `teacher.sarah@school.com` | `teach5678` |
| Accountant | `accountant@school.com` | `accountant123` |
| Librarian | `librarian@school.com` | `librarian123` |
| Bulk teachers / students / parents | `teacher1..30@` / `student1..5@` / `parent1..10@` | `password` |

Canonical reference: [`../../docs/operations/DEMO-CREDENTIALS.md`](../../docs/operations/DEMO-CREDENTIALS.md).
**Change every default password before production use.**

---

## 4. Configuration (`.env`)

| Key | Purpose |
|---|---|
| `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY` | Core app identity; `APP_URL` must match the URL you serve on |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` | UI locale (English/Bengali) |
| `ESKOOFY_VARIANT` | `bd` (Bangladesh) or `int` (international) — controls feature/branding profile |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | Admin account created by `AdminUserSeeder` |
| `ALLOW_DEMO_DATA` | Allow demo accounts when `APP_ENV=production` |
| `DB_*` | Database connection |
| `SESSION_*`, `CACHE_*`, `QUEUE_CONNECTION` | Session/cache/queue drivers (`database` by default; Redis optional) |
| `MAIL_*` | Outgoing mail |
| Payment gateway, SMS and FCM keys | See `config/payment.php`, gateway records, and `.env.example` |

> **Payment secrets:** runtime gateway credentials live in the `payment_gateways` table and
> are managed in the dashboard; `config/payment.php` holds only offline/bank-transfer
> defaults. Never commit real secrets — set them in `.env`.

### Variants (BD / INT)

`bd` and `int` are **build-time profiles of one codebase**, driven by
`config/eskoolfy.php` + `.env` (`ESKOOFY_VARIANT`) — never code forks. The `bd` profile
enables Bengali, ministry links and local gateways (bKash/Rocket/Nagad); `int` is
English-only with Stripe/PayPal/Paddle.

---

## 5. Assets & production build

```bash
npm run build        # compiles Blade/Tailwind assets into public/build
php artisan optimize # cache config/routes/views (production)
```

---

## 6. Web server & deployment

### Docker (production-like stack)

`docker-compose.yml` provides nginx + PHP-FPM + MySQL 8 + Redis:

```bash
docker compose up -d
docker compose exec php php artisan migrate --seed --force
```

Access at **http://localhost:8080** (nginx → Laravel `public/`). MySQL is exposed on
`33061` for inspection.

### Manual (nginx)

- Point the document root at `eskoofy-laravel-app/public`.
- Ensure `storage/` and `bootstrap/cache/` are writable by the web user.
- Run `php artisan migrate --force` and `php artisan optimize` on deploy.
- Declare the scheduler (`* * * * * php /path/artisan schedule:run`) and a queue worker.

Go-live checklist: [`../../docs/operations/PRODUCTION-CHECKLIST.md`](../../docs/operations/PRODUCTION-CHECKLIST.md).
Deploy/rollback/rotation runbooks: [`../../docs/operations/RUNBOOKS.md`](../../docs/operations/RUNBOOKS.md).

---

## 7. Scheduled jobs & operations

| Command | Schedule | Purpose |
|---|---|---|
| `php artisan backup:database` | daily 02:00 | Self-contained DB backup (sqlite/mysql/pgsql) → `storage/app/backups`, prunes to 7 (`--keep` to change) |
| `php artisan queue:monitor-failed` | every 5 min | Report failed queue jobs to log/Slack (`LOG_SLACK_WEBHOOK_URL`) |

Backup & restore: [`../../docs/operations/BACKUP-RESTORE.md`](../../docs/operations/BACKUP-RESTORE.md).

---

## 8. Testing & verification

```bash
composer test                 # php artisan config:clear && php artisan test (SQLite :memory:, no DB needed)
./vendor/bin/pint --test      # code style check (this is a CI gate)
./vendor/bin/pint             # auto-fix style
```

A single test: `php artisan test --testsuite=Feature --filter=SpecificTest`.

---

## 9. Troubleshooting

| Symptom | Fix |
|---|---|
| Port already in use | The app defaults to **8000**; pick another port and keep `APP_URL` in sync |
| Seeder refuses weak password | Keep `APP_ENV=local` for dev, or set a strong `ADMIN_PASSWORD` |
| Vite assets missing | `npm install` then `npm run dev` (HMR) or `npm run build` |
| Style check failing locally | Run `./vendor/bin/pint` |
| Queues not processing | Start `php artisan queue:listen` (or a worker in production) |
| Permission/role weirdness | Re-seed roles/permissions (`php artisan db:seed --class=RolePermissionSeeder`) |

Further: [`../../docs/operations/DASHBOARD_TROUBLESHOOTING.md`](../../docs/operations/DASHBOARD_TROUBLESHOOTING.md).

---

## Related docs

- [`USER-MANUAL.md`](./USER-MANUAL.md)
- [`../README.md`](../README.md) · [`../AGENTS.md`](../AGENTS.md)
- [`../../docs/operations/PRODUCTION-CHECKLIST.md`](../../docs/operations/PRODUCTION-CHECKLIST.md)
- [`../../docs/operations/RUNBOOKS.md`](../../docs/operations/RUNBOOKS.md)
- [`../../docs/operations/BACKUP-RESTORE.md`](../../docs/operations/BACKUP-RESTORE.md)
- [`../../docs/operations/PAYMENT-DEPLOYMENT.md`](../../docs/operations/PAYMENT-DEPLOYMENT.md)
