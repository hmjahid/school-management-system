# Eskoofy PHP — Setup Guide

> Product: `eskoofy-php` (raw PHP 8.2+, PDO/MySQL, no framework at runtime) · This guide covers install, database, configuration, web server, cron and maintenance.

- Day-to-day usage: [`USER-MANUAL.md`](./USER-MANUAL.md)
- Run all Eskoofy components locally: [`../../docs/guides/DEVELOPMENT.md`](../../docs/guides/DEVELOPMENT.md)

---

## 1. Prerequisites

| Requirement | Version | Why |
|---|---|---|
| PHP | **8.2+** (8.3 tested) with `pdo_mysql`, `mbstring`, `json`, `session`, `ctype`, `openssl`, `fileinfo`, `curl` | Runtime; cURL for payment/SMS gateways |
| MySQL / MariaDB | 8.x / 10.x | 99-table schema (InnoDB, utf8mb4) |
| Web server | Apache (`mod_rewrite`) or nginx | Routes through `public/index.php` |
| Composer | 2.0+ | **Dev tooling only** (PHPUnit) — not needed at runtime |
| Node.js + npm | 18+ | Optional asset build (Vite) |

No framework, no Composer autoload, and no Laravel at runtime: `.env` is parsed manually and
a hand-written autoloader loads `App\` classes from `app/`.

---

## 2. Quick start

```bash
cd eskoofy-php
composer install                      # dev tooling only (PHPUnit)
cp .env.example .env                  # set DB_HOST / DB_DATABASE / DB_USERNAME / DB_PASSWORD
mysql -u root -p < database/schema.sql
php database/seed_admin.php           # creates the super admin; prints a random password
php database/seed_demo.php            # optional demo accounts (idempotent)
php database/seed_demo_content.php    # optional demo school content (idempotent)
php -S localhost:8051 -t public
```

Open **http://127.0.0.1:8051** → `/login`.

> The PHP port uses port **8051** so it does not clash with the Laravel app (8000). Keep
> `APP_URL` in `.env` in sync with the port you run on.

---

## 3. Database setup

1. Create the database and import the schema:

   ```bash
   mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS eskoofy CHARACTER SET utf8mb4;"
   mysql -u root -p eskoofy < database/schema.sql
   ```

2. **Create the super admin (required on a fresh install):**

   ```bash
   php database/seed_admin.php
   ```

   `database/schema.sql` seeds **no users** — a fresh database has zero accounts until this
   runs. It is idempotent and prints the generated password once. Override with
   `ADMIN_EMAIL` / `ADMIN_PASSWORD`.

3. **Optional demo data:**

   ```bash
   php database/seed_demo.php           # roles + named + bulk accounts
   php database/seed_demo_content.php   # website settings, sessions, classes, students, fees…
   ```

   `seed_demo.php` **refuses to run when `APP_ENV=production`** unless `--i-am-sure` is passed.

### Tables of interest

Schema is 99 tables: people (`users`, `students`, `teachers`, `guardians`, `roles`,
`permissions`), academics (`school_classes`, `sections`, `subjects`, `batches`,
`attendances`, `exams`, `exam_results`), finance (`fees`, `fee_payments`, `payments`,
`refunds`, `expenses`, `budgets`, `ledger_entries`, `chart_of_accounts`), operations
(`books`/`book_issues`, `vehicles`/`transport_routes`, `hostels`, `salary_structures`,
`payslips`), CMS and system tables.

---

## 4. Configuration (`.env`)

| Key | Purpose |
|---|---|
| `APP_NAME`, `APP_URL`, `APP_ENV`, `APP_DEBUG`, `APP_KEY` | Core identity; `APP_URL` must match the served URL |
| `APP_LOCALE`, `APP_TIMEZONE` | Locale/timezone |
| `ESKOOFY_VARIANT` | `bd` or `int` profile |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL connection (the checked-in `.env` points at the dev MariaDB on port 3307; `.env.example` uses 3306) |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD` | Used by `database/seed_admin.php` |
| Payment gateway / SMS keys | Read by `config/payment.php` and `config/sms.php` |

Config files: `config/app.php`, `config/school.php`, `config/eskoolfy.php` (variant +
feature flags), `config/payment.php` (7 gateways + offline), `config/sms.php` (6 drivers),
`config/access.php` (dashboard authorization map), `config/routes.php` (route-name → URI map).

### Variants (BD / INT)

Driven by `config/eskoolfy.php` + `.env` — same codebase, no `if (bd)` branching. `bd` enables
Bengali, ministry links and bKash/Rocket/Nagad; `int` is English-only with Stripe/PayPal/Paddle.

---

## 5. Web server configuration

**Document root must be `public/`.** `public/index.php` is the single front controller.

- **Apache:** `public/.htaccess` rewrites non-file/non-dir requests to `index.php`, sets
  security headers and denies `.env`/`.json`/`.md`/`.log`/`.sql`/dotfiles. If your host's
  docroot is the product root instead of `public/`, the root `.htaccess` rewrites everything
  into `public/`.
- **nginx:**

  ```nginx
  root /path/to/eskoofy-php/public;
  location / { try_files $uri $uri/ /index.php?$query_string; }
  location ~ \.php$ { include fastcgi_params; fastcgi_pass unix:/run/php/php8.2-fpm.sock; }
  ```

---

## 6. Assets (optional Vite build)

```bash
npm install
npm run build        # → public/build/manifest.json (Blade::vite reads it)
```

If no manifest is present, the app falls back to the Tailwind CDN, so the build is optional.

---

## 7. Cron jobs

```bash
php public/cron.php
```

- **Recurring payments:** daily at 01:00.
- **Scheduled notifications:** every 5 minutes.

Add a single crontab entry that runs `cron.php` each minute, or schedule these two jobs
individually.

---

## 8. Seeded accounts

| Role | Email | Password |
|---|---|---|
| Super Admin | `admin@eskoofy.com` | generated by `database/seed_admin.php` |
| Administrator | `admin@school.com` | `ChangeMe!2026$Tr0ng` (demo only) |
| School Principal | `principal@school.com` | `principal123` |
| Teachers | `teacher.john@school.com` / `teacher.sarah@school.com` | `teach1234` / `teach5678` |
| Accountant | `accountant@school.com` | `accountant123` |
| Librarian | `librarian@school.com` | `librarian123` |
| Bulk teachers / students / parents | `teacher1..30@` / `student1..5@` / `parent1..10@` | `password` |

Logins: staff `/login`, student `/student/login`, guardian `/guardian/login`. Canonical
reference: [`../../docs/operations/DEMO-CREDENTIALS.md`](../../docs/operations/DEMO-CREDENTIALS.md).
**Never deploy the demo accounts to production.**

---

## 9. Testing

```bash
composer test                    # PHPUnit 11 (dev-only)
php -l app/Core/Blade.php        # quick syntax check of any file
```

The suite is DB-free (uses `tests/Fakes/FakeDatabase.php`). `composer test` currently passes
**314 tests / 807 assertions**.

### Blade cache

Compiled templates live in `storage/framework/views/`. After changing the Blade *compiler*
(don't do this for a normal template edit), delete that directory and re-touch the templates,
since opcache can serve stale bytecode.

---

## 10. Troubleshooting

| Symptom | Fix |
|---|---|
| Login fails on a fresh install | Run `php database/seed_admin.php` — the schema seeds no users |
| Blank/500s after moving hosts | Point docroot at `public/`; make `storage/` writable; check `APP_URL` matches the port |
| Blade renders stale output | Clear `storage/framework/views/` and re-touch templates |
| `APP_URL` mismatch | Update `.env`; several redirects/URLs derive from it |
| MySQL connection refused | Confirm `DB_*` host/port and that the schema was imported |
| `.env` drift | The checked-in `.env` targets the dev MariaDB (`3307`, user `esk`); `.env.example` targets `3306`/`root` — pick the one matching your environment |

---

## Related docs

- [`USER-MANUAL.md`](./USER-MANUAL.md)
- [`../README.md`](../README.md) · [`../AGENTS.md`](../AGENTS.md)
- [`../../docs/planning/server-requirements.md`](../../docs/planning/server-requirements.md)
- [`../../docs/operations/DEMO-CREDENTIALS.md`](../../docs/operations/DEMO-CREDENTIALS.md)
- [`../../docs/operations/PRODUCTION-CHECKLIST.md`](../../docs/operations/PRODUCTION-CHECKLIST.md)
