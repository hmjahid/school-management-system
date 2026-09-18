# Development Guide — Running Eskoofy Locally

Eskoofy is a **3-product monorepo** plus a **branding website + license server**.
This guide explains how to run every product and the website on your **development
server**. Each component is self-contained in its own folder and uses a dedicated
port so you can run them **side by side** at the same time.

> See the root [`README.md`](../../README.md) for the monorepo overview, the
> BD/INT variant strategy, and the features. Demo login credentials live in
> [`../operations/DEMO-CREDENTIALS.md`](../operations/DEMO-CREDENTIALS.md).

## Port map

| Component | Port | Entry point | Stack |
|---|---|---|---|
| `eskoofy-app/` (Laravel) | **8000** | `php artisan serve` | Laravel 12, Blade, Vite/Tailwind 4 |
| `eskoofy-php/` (raw PHP) | **8051** | `php -S ... -t public` | Native PHP + PDO/MySQL |
| `eskoofy-website/` (marketing + license server) | **8011** | `php -S ... -t public` | Raw PHP, no Composer at runtime |
| `eskoofy-theme/` (WordPress) | **8080** | Docker harness (`docker/theme-test`) | WordPress + Apache, bind-mounted theme |

Ports are fixed by convention so nothing clashes with the app's default 8000.
Keep `APP_URL` in each `.env` in sync with the port you actually run on.

## Prerequisites

| Tool | Version | Used by |
|---|---|---|
| PHP | **8.2+** (CLI + `pdo_mysql`/`pdo_sqlite`) | all products + website |
| Composer | 2.0+ | app (runtime), php/theme/website (dev tooling) |
| Node.js + npm | 18+ | app (Vite asset build) |
| MySQL / MariaDB | 8.x / 10.x | php port, website (and optional for the app) |
| Docker Engine + Compose v2 | ≥ 20.10 (`docker compose` plugin) | theme test harness |
| Git | any | clone + `git mv` history |

The Laravel app can also run on **SQLite** (no DB server needed) — that is its
default dev database.

## 1. eskoofy-app (Laravel 12) — port 8000

The main product and where most work happens.

```bash
cd eskoofy-app
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed   # SQLite by default; switch DB_* in .env for MySQL
composer dev                        # app :8000 + queue + pail (logs) + Vite HMR — all at once
```

Or run the pieces individually:

```bash
php artisan serve    # http://127.0.0.1:8000
npm run dev          # Vite HMR for Blade/Tailwind assets
php artisan queue:listen
```

Access the app:

| What | URL |
|---|---|
| Public site / login | `http://127.0.0.1:8000` / `.../login` |
| Admin dashboard | `http://127.0.0.1:8000/dashboard` |
| Student portal | `http://127.0.0.1:8000/student/login` |
| Guardian portal | `http://127.0.0.1:8000/guardian/login` |
| JSON API | `http://127.0.0.1:8000/api/v1` |

Admin login comes from `ADMIN_EMAIL` / `ADMIN_PASSWORD` in `.env` (default
`admin@school.com` / `ChangeMe!2026$Tr0ng`). The `composer dev` helper runs
serve + queue + pail + Vite together and is the fastest way to start.

Commands: `composer test` (PHPUnit), `./vendor/bin/pint --test` (style check),
`npm run build` (production asset build), `php artisan backup:database` (backup).

> **MySQL variant:** set `DB_CONNECTION=mysql`, `DB_HOST`, `DB_DATABASE`,
> `DB_USERNAME`, `DB_PASSWORD` in `.env`, then run `php artisan migrate:fresh --seed`.

## 2. eskoofy-php (raw PHP port) — port 8051

Feature-equivalent port in **native PHP with no framework at runtime**; needs a
MySQL database. No Composer is needed to run it.

```bash
cd eskoofy-php
composer install                      # dev tooling only (PHPUnit)
cp .env.example .env                  # set DB_HOST / DB_DATABASE / DB_USERNAME / DB_PASSWORD
mysql -u root -p < database/schema.sql
php database/seed_demo.php            # optional demo accounts (idempotent)
php -S localhost:8051 -t public
```

Open `http://127.0.0.1:8051` and log in at `/login`. The document root is
`public/` (`.` — `index.php` is the front controller, `.htaccess` routes
everything through it on Apache).

Tests (dev-only): `composer test`.

## 3. eskoofy-website (marketing + license server) — port 8011

The **branding site + license server** — markets and sells the 3 products. Raw
PHP, no Composer at runtime. Needs its own MySQL database.

```bash
cd eskoofy-website
cp .env.example .env                  # set DB_* for the licensing DB (eskoofy_website)
mysql -u root -p eskoofy_website < database/schema.sql
php -S 127.0.0.1:8011 -t public
```

Open `http://127.0.0.1:8011`. Admin seed user:
`admin@eskoofy.com` / `admin123` (change in production). The site is always
the `int` variant (English/USD/UTC) with an `en`/`bn` language switcher;
`GEO_LANG_ENABLED` / `GEO_IP_API_URL` control the location-based default
language (see `eskoofy-website/README.md`).

Tests: `composer test`.

## 4. eskoofy-theme (WordPress) — port 8080

The theme runs inside WordPress. The fastest local environment is the
**Docker harness** in [`docker/theme-test/`](../../docker/theme-test/README.md),
which bind-mounts `eskoofy-theme/` into WordPress + MariaDB so every edit is
visible on refresh (no rebuild step).

```bash
cd docker/theme-test
docker compose up -d        # first run pulls images + installs + activates the theme
```

`setup-theme.sh` runs once and: pins the site URL to `http://localhost:8080`,
activates the theme (this creates all `esk_*` tables + demo users), and creates
a demo page for every `template-*.php`.

| What | URL |
|---|---|
| Public site | `http://localhost:8080` |
| System login (`/login/`) | `http://localhost:8080/login/` |
| Management dashboard | `http://localhost:8080/dashboard/` |
| WordPress admin | `http://localhost:8080/wp-admin/` |

System logins use the shared demo credentials (`admin` / `admin@school.com` /
`ChangeMe!2026$Tr0ng`, etc. — see
[`../operations/DEMO-CREDENTIALS.md`](../operations/DEMO-CREDENTIALS.md)).
WordPress admin user defaults to `admin` / `admin` (override via `.env`).

Useful commands:

```bash
docker compose logs -f wp                       # live logs (Apache + PHP errors)
docker compose run --rm --entrypoint wp setup option get siteurl
docker compose down -v                          # full tear-down (destroys DB)
docker compose down -v --rmi local && docker compose up -d   # rebuild from scratch
```

Without Docker: copy `eskoofy-theme/` into `wp-content/themes/eskoofy`, activate
it in the WordPress admin, then **Settings → Eskoofy** to finish setup. Custom DB
tables are created automatically on activation. Lint the theme with
`cd eskoofy-theme && composer install && composer run lint`.

## Database summary

| Component | Default DB | Create command |
|---|---|---|
| `eskoofy-app` | SQLite (`database/database.sqlite`) | `php artisan migrate:fresh --seed` |
| `eskoofy-app` (MySQL) | `eskoofy` (set `DB_*` in `.env`) | `php artisan migrate:fresh --seed` |
| `eskoofy-php` | `eskoofy` | `mysql -u root -p < database/schema.sql` + `php database/seed_demo.php` |
| `eskoofy-website` | `eskoofy_website` | `mysql -u root -p eskoofy_website < database/schema.sql` |
| `eskoofy-theme` | MariaDB (Docker) | auto-created on theme activation |

## Running everything at once

Use four terminals (one per component):

| Terminal | Command | URL |
|---|---|---|
| 1 | `cd eskoofy-app && composer dev` | `http://127.0.0.1:8000` |
| 2 | `cd eskoofy-php && php -S localhost:8051 -t public` | `http://127.0.0.1:8051` |
| 3 | `cd eskoofy-website && php -S 127.0.0.1:8011 -t public` | `http://127.0.0.1:8011` |
| 4 | `cd docker/theme-test && docker compose up -d` | `http://localhost:8080` |

## Demo credentials

All products share the same demo accounts. Canonical reference:
[`../operations/DEMO-CREDENTIALS.md`](../operations/DEMO-CREDENTIALS.md).

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@school.com` | from `.env` `ADMIN_PASSWORD` (`ChangeMe!2026$Tr0ng` in dev) |
| Principal | `principal@school.com` | `principal123` |
| Teacher | `teacher.john@school.com` | `teach1234` |
| Teacher | `teacher.sarah@school.com` | `teach5678` |
| Accountant | `accountant@school.com` | `accountant123` |
| Librarian | `librarian@school.com` | `librarian123` |
| Bulk teachers / students / parents | `teacher1..30@` / `student1..5@` / `parent1..10@` | `password` |
| Website admin | `admin@eskoofy.com` | `admin123` |

## Verifying your setup

```bash
cd eskoofy-app && composer test                # Laravel PHPUnit suite
cd eskoofy-app && ./vendor/bin/pint --test     # Laravel code style
cd eskoofy-php && composer test                # raw-PHP suite (dev-only)
cd eskoofy-website && composer test            # website suite (DB-free)
cd eskoofy-theme && composer run lint          # PHPCS (needs composer install)
cd build && ./export.sh app bd                 # build-box export smoke test
```

GitHub Actions (`.github/workflows/ci.yml`) runs all of the above on every
push/PR, plus exports both BD/INT variants and smoke-tests the artifacts.

## Troubleshooting

- **Port already in use** — every component has a fixed port; pick a free port
  and mirror it in the component's `APP_URL` in `.env`.
- **App shows weak-password / appears in production mode** — the seeder refuses
  weak passwords when `APP_ENV=production`; keep `APP_ENV=local` for dev.
- **Blade templates render stale in `eskoofy-php`** — after changing the Blade
  *compiler* (not a template) delete `storage/framework/views/` cache and re-touch
  the templates to force recompilation.
- **Theme changes not visible** — hard-refresh (`Ctrl-Shift-R`) or append a
  `?v=<timestamp>` cache-buster; the Docker harness bind-mounts live files.
- **Vite assets missing in the app** — run `npm install` first, then `npm run dev`
  (HMR) or `npm run build`.
- **Website locale surprises** — the geo/language cookie decides the default
  language; a manual `/language/{en|bn}` switch always wins.
- **MySQL connection refused** — check the `DB_HOST`/port match your local
  MySQL, and that the DB + user exist before importing the schema.

## Related docs

- [`../operations/PRODUCTION-CHECKLIST.md`](../operations/PRODUCTION-CHECKLIST.md) — go-live checklist
- [`../operations/RUNBOOKS.md`](../operations/RUNBOOKS.md) — deployment + operations runbooks
- [`../operations/BACKUP-RESTORE.md`](../operations/BACKUP-RESTORE.md) — backup & restore
- [`../operations/DEMO-CREDENTIALS.md`](../operations/DEMO-CREDENTIALS.md) — demo accounts
- [`../../docker/theme-test/README.md`](../../docker/theme-test/README.md) — theme Docker harness
- [`../../build/README.md`](../../build/README.md) — build-box export