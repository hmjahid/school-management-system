# Development Guide — Running Eskoofy Locally

Eskoofy is a **4-product monorepo** (Laravel app, raw-PHP port, WordPress theme, Node.js
clone) plus a **branding website + license server**.
This guide explains how to run every product and the website on your **development
server**. Each component is self-contained in its own folder and uses a dedicated
port so you can run them **side by side** at the same time.

> See the root [`README.md`](../../README.md) for the monorepo overview, the
> BD/INT variant strategy, and the features. Demo login credentials live in
> [`../operations/DEMO-CREDENTIALS.md`](../operations/DEMO-CREDENTIALS.md).

## Port map

| Component | Port | Entry point | Stack |
|---|---|---|---|
| `eskoofy-laravel-app/` (Laravel) | **8000** | `php artisan serve` | Laravel 12, Blade, Vite/Tailwind 4 |
| `eskoofy-nodejs-app/` (Node.js clone) | **3000** | `npm run dev` | Next.js App Router + Prisma + Tailwind |
| `eskoofy-php-app/` (raw PHP) | **8051** | `php -S ... -t public` | Native PHP + PDO/MySQL |
| `eskoofy-branding-website/` (marketing + license server) | **8011** | `php -S ... -t public` | Raw PHP, no Composer at runtime |
| `eskoofy-wp-theme/` (WordPress) | **8080** | Docker harness (`docker/theme-test`) | WordPress + Apache, bind-mounted theme |

Ports are fixed by convention so nothing clashes with the app's default 8000.
Keep `APP_URL` in each `.env` in sync with the port you actually run on.

## Prerequisites

| Tool | Version | Used by |
|---|---|---|
| PHP | **8.2+** (CLI + `pdo_mysql`/`pdo_sqlite`) | all products + website |
| Composer | 2.0+ | app (runtime), php/theme/website (dev tooling) |
| Node.js + npm | **20.9+** (18+ works for the app's Vite build) | app (Vite asset build), `eskoofy-nodejs-app` |
| MySQL / MariaDB | 8.x / 10.x | php port, website (and optional for the app) |
| Docker Engine + Compose v2 | ≥ 20.10 (`docker compose` plugin) | theme test harness |
| Git | any | clone + `git mv` history |

The Laravel app can also run on **SQLite** (no DB server needed) — that is its
default dev database.

## 1. eskoofy-laravel-app (Laravel 12) — port 8000

The main product and where most work happens.

```bash
cd eskoofy-laravel-app
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

## 2. eskoofy-nodejs-app (Node.js clone) — port 3000

A **single-architecture** clone of the Laravel app: the public site, the dashboard and
`/api/v1` all live in one Next.js project (no separate API server, no SPA). It reads and
writes the **same database schema** as the app, so point it at the same MySQL database.

```bash
cd eskoofy-nodejs-app
cp .env.example .env              # DATABASE_URL + AUTH_SECRET
docker start esk-mariadb          # DB on 127.0.0.1:3307 (database: eskoofy_node)
npm install                       # postinstall runs `prisma generate`
npm run prisma:push               # create the 107 tables (or: npm run prisma:migrate)
npm run db:seed                   # demo accounts (same as the app)
npm run dev                       # http://localhost:3000
```

The bundled `.env.example` points at the shared `esk-mariadb` container
(`mysql://esk:eskpw@127.0.0.1:3307/eskoofy_node`). Create that database once (as root, or
grant `esk` access) before the first `prisma:push`:

```bash
docker exec esk-mariadb mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" -e \
  "CREATE DATABASE IF NOT EXISTS eskoofy_node CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; \
   GRANT ALL PRIVILEGES ON eskoofy_node.* TO 'esk'@'%'; FLUSH PRIVILEGES;"
```

| What | URL |
|---|---|
| Public site | `http://localhost:3000` |
| Login | `http://localhost:3000/login` |
| Dashboard | `http://localhost:3000/dashboard` |
| JSON API | `http://localhost:3000/api/v1` |

Log in with the app's demo account (`admin@school.com` / `ChangeMe!2026$Tr0ng`; override with
`ADMIN_EMAIL` / `ADMIN_PASSWORD`). The API uses the same
`{success,message,data[,meta]}` envelope as the app.

Verification and parity:

```bash
npm run typecheck && npm run lint && npm test   # 48 Vitest tests
npm run route:parity                            # 585 routes + 95 sidebar keys vs the app
npm run build                                   # production build
```

`route:parity` re-runs `php artisan route:list` inside `eskoofy-laravel-app/`, so the app
must be present (and its Composer deps installed) for that command. What is ported vs
pending: [`../../eskoofy-nodejs-app/docs/PORTING-STATUS.md`](../../eskoofy-nodejs-app/docs/PORTING-STATUS.md).

## 3. eskoofy-php-app (raw PHP port) — port 8051

Feature-equivalent port in **native PHP with no framework at runtime**; needs a
MySQL database. No Composer is needed to run it.

```bash
cd eskoofy-php-app
composer install                      # dev tooling only (PHPUnit)
cp .env.example .env                  # set DB_HOST / DB_DATABASE / DB_USERNAME / DB_PASSWORD
mysql -u root -p < database/schema.sql
php database/seed_demo.php            # optional demo accounts (idempotent)
php -S localhost:8051 -t public
```

> **Docker DB:** run MySQL/MariaDB in a container instead of installing one —
> this machine already has one: `esk-mariadb` (host port `3307`, user
> `esk`/`eskpw`) with the `eskoofy_php` schema pre-loaded — see
> [Database via Docker](#database-via-docker-optional) below.

Open `http://127.0.0.1:8051` and log in at `/login`. The document root is
`public/` (`.` — `index.php` is the front controller, `.htaccess` routes
everything through it on Apache).

Tests (dev-only): `composer test`.

## 4. eskoofy-branding-website (marketing + license server) — port 8011

The **branding site + license server** — markets and sells the three school-management
deployments (Laravel app / raw PHP / WordPress theme). Raw PHP, no Composer at runtime.
Needs its own MySQL database.

```bash
cd eskoofy-branding-website
cp .env.example .env                  # set DB_* for the licensing DB (eskoofy_website)
mysql -u root -p eskoofy_website < database/schema.sql
php -S 127.0.0.1:8011 -t public
```

> **Docker DB:** the site's MySQL database runs in a container
> (`esk-mariadb`, host port `3307`, user `esk`/`eskpw`) with the
> `eskoofy_website` schema already loaded — the `.env` above already points at
> it. The site itself is served by `php -S` (plain PHP), there is no web
> container. See [Database via Docker](#database-via-docker-optional) below.

Open `http://127.0.0.1:8011`. Admin seed user:
`admin@eskoofy.com` / `admin123` (change in production). The site is always
the `int` variant (English/USD/UTC) with an `en`/`bn` language switcher;
`GEO_LANG_ENABLED` / `GEO_IP_API_URL` control the location-based default
language (see `eskoofy-branding-website/README.md`).

Tests: `composer test`.

## 5. eskoofy-wp-theme (WordPress) — port 8080

The theme runs inside WordPress. The fastest local environment is the
**Docker harness** in [`docker/theme-test/`](../../docker/theme-test/README.md),
which bind-mounts `eskoofy-wp-theme/` into WordPress + MariaDB so every edit is
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

Without Docker: copy `eskoofy-wp-theme/` into `wp-content/themes/eskoofy`, activate
it in the WordPress admin, then **Settings → Eskoofy** to finish setup. Custom DB
tables are created automatically on activation. Lint the theme with
`cd eskoofy-wp-theme && composer install && composer run lint`.

## Database via Docker (optional)

`eskoofy-php-app`, `eskoofy-nodejs-app` and `eskoofy-branding-website` require MySQL.
Instead of installing a DB server locally, run one in a container and point the
component's `.env` (`DB_*` keys, or `DATABASE_URL` for the Node clone) at it. **A ready-made
container already exists on this machine** — `esk-mariadb` (mariadb 11, host port **3307**,
user `esk` / `eskpw`) — holding the `eskoofy_website`, `eskoofy_php` and `eskoofy_node`
databases:

```bash
docker start esk-mariadb        # DB on 127.0.0.1:3307
```

The `eskoofy-branding-website` and `eskoofy-php-app` `.env` files are already configured to
use it. For a fresh container elsewhere:

```bash
# MariaDB 11.4 (same image family the theme harness uses)
docker run -d --name eskoofy-mariadb -e MARIADB_ROOT_PASSWORD=root \
  -e MARIADB_DATABASE=eskoofy_website -p 3306:3306 mariadb:11.4

# or MySQL 8 (same engine the app's docker-compose uses)
docker run -d --name eskoofy-mysql -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_DATABASE=eskoofy_website -p 3306:3306 mysql:8.0
```

Then import the schema into a DB and run the component (schemas/seeds create the
remaining `eskoofy` / `esk_*` tables):

```bash
docker exec -i eskoofy-mariadb mariadb -uroot -proot -e \
  "CREATE DATABASE IF NOT EXISTS eskoofy;"
docker exec -i eskoofy-mariadb mariadb -uroot -proot eskoofy_website \
  < eskoofy-branding-website/database/schema.sql
```

Existing Docker setups in this repo + this machine:

| Setup | Container | DB image | Host port | Databases |
|---|---|---|---|---|
| **Local dev DB (this machine)** | `esk-mariadb` | MariaDB 11 | `3307` | `eskoofy_website`, `eskoofy_php`, `eskoofy_node` (user `esk`/`eskpw`) |
| `docker/theme-test/docker-compose.yml` | `db` (theme harness) | MariaDB 11.4 | internal (compose network) | `wordpress` |
| `eskoofy-laravel-app/docker-compose.yml` | `db` (app prod-like) | MySQL 8.0 | `33061` | `school_db` |

The app's compose (`cd eskoofy-laravel-app && docker compose up -d`) also gives you a
ready MySQL 8 at `127.0.0.1:33061` for `eskoofy-php-app`/`eskoofy-branding-website` —
set `DB_PORT=33061` in their `.env`. For `eskoofy-nodejs-app`, point `DATABASE_URL` at the
same server, e.g. `mysql://root:root@127.0.0.1:33061/school_db`.

## Database summary

| Component | Default DB | Create command |
|---|---|---|
| `eskoofy-laravel-app` | SQLite (`database/database.sqlite`) | `php artisan migrate:fresh --seed` |
| `eskoofy-laravel-app` (MySQL) | `eskoofy` (set `DB_*` in `.env`) | `php artisan migrate:fresh --seed` |
| `eskoofy-nodejs-app` | `eskoofy_node` (same schema, `esk-mariadb` :3307) | `docker start esk-mariadb` + `npm run prisma:push` + `npm run db:seed` |
| `eskoofy-php-app` | `eskoofy` | `mysql -u root -p < database/schema.sql` + `php database/seed_demo.php` |
| `eskoofy-branding-website` | `eskoofy_website` | `mysql -u root -p eskoofy_website < database/schema.sql` |
| `eskoofy-wp-theme` | MariaDB (Docker) | auto-created on theme activation (`docker/theme-test`) |

## Running everything at once

Use five terminals (one per component):

| Terminal | Command | URL |
|---|---|---|
| 1 | `cd eskoofy-laravel-app && composer dev` | `http://127.0.0.1:8000` |
| 2 | `cd eskoofy-nodejs-app && npm run dev` | `http://localhost:3000` |
| 3 | `cd eskoofy-php-app && php -S localhost:8051 -t public` | `http://127.0.0.1:8051` |
| 4 | `cd eskoofy-branding-website && php -S 127.0.0.1:8011 -t public` | `http://127.0.0.1:8011` |
| 5 | `cd docker/theme-test && docker compose up -d` | `http://localhost:8080` |

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
| Node.js clone admin | `admin@school.com` | from `.env` `ADMIN_PASSWORD` (`ChangeMe!2026$Tr0ng` in dev) |
| Website admin | `admin@eskoofy.com` | `admin123` |

## Verifying your setup

```bash
cd eskoofy-laravel-app && composer test                # Laravel PHPUnit suite
cd eskoofy-laravel-app && ./vendor/bin/pint --test     # Laravel code style
cd eskoofy-nodejs-app && npm run typecheck && npm run lint && npm test   # Node clone
cd eskoofy-nodejs-app && npm run route:parity          # app <-> node route + sidebar parity
cd eskoofy-php-app && composer test                # raw-PHP suite (dev-only)
cd eskoofy-branding-website && composer test            # website suite (DB-free)
cd eskoofy-wp-theme && composer run lint          # PHPCS (needs composer install)
cd build && ./export.sh app bd                 # build-box export smoke test
```

GitHub Actions (`.github/workflows/ci.yml`) runs all of the above on every
push/PR — including the Node clone's typecheck, lint, Vitest and app↔node parity job —
plus exports both BD/INT variants and smoke-tests the artifacts.

## Troubleshooting

- **Port already in use** — every component has a fixed port; pick a free port
  and mirror it in the component's `APP_URL` in `.env`.
- **App shows weak-password / appears in production mode** — the seeder refuses
  weak passwords when `APP_ENV=production`; keep `APP_ENV=local` for dev.
- **Blade templates render stale in `eskoofy-php-app`** — after changing the Blade
  *compiler* (not a template) delete `storage/framework/views/` cache and re-touch
  the templates to force recompilation.
- **Theme changes not visible** — hard-refresh (`Ctrl-Shift-R`) or append a
  `?v=<timestamp>` cache-buster; the Docker harness bind-mounts live files.
- **Theme dashboard unreachable / `/login/` bounces to `wp-login.php` or 404s** — the harness
  `setup` service never ran, so its demo pages (`login`, `about`, …) are missing and
  WordPress core hijacks the reserved `/login` slug. `wp`+`db` start anyway, which makes this
  easy to miss (`docker compose logs setup` shows
  `ERROR: WP_ADMIN_PASSWORD must be set…`). Fix:
  `cd docker/theme-test && WP_ADMIN_PASSWORD='ChangeMe!2026$Tr0ng' docker compose run --rm setup`.
- **Theme dashboard renders unstyled** — the shell loads CSS/JS from `inc/`; the theme's
  `.htaccess` must allow static assets under `inc/` while still denying `.php` (a 403 on
  `wp-content/themes/eskoofy/inc/admin-shell.css` means that rule regressed).
- **Vite assets missing in the app** — run `npm install` first, then `npm run dev`
  (HMR) or `npm run build`.
- **Website locale surprises** — the geo/language cookie decides the default
  language; a manual `/language/{en|bn}` switch always wins.
- **`SQLSTATE[HY000] [2002] Connection refused` (raw-PHP app, website, Node clone)** — the
  shared database container is almost certainly stopped. `esk-mariadb` already has
  `restart=unless-stopped`, so a manual stop is the usual cause:
  `docker start esk-mariadb` (then confirm `ss -ltn | grep 3307`). Symptom in the browser is a
  500 on `POST /login` with a PDOException from `app/Core/Database.php`.
- **MySQL connection refused** — check the `DB_HOST`/port match your local
  MySQL, and that the DB + user exist before importing the schema.
- **Node clone cannot reach the database** — `eskoofy-nodejs-app` uses a single
  `DATABASE_URL` (MySQL DSN, e.g. `mysql://user:pass@127.0.0.1:3306/eskoofy`), not the
  app's `DB_*` keys. After changing `prisma/schema.prisma`, re-run `npm run prisma:generate`.
- **`npm run route:parity` fails** — the Laravel app gained/lost/renamed a route or a sidebar
  key; regenerate `eskoofy-nodejs-app/lib/routes.generated.ts` (and/or update `lib/nav.ts`)
  so the clone tracks the app.

## Related docs

- [`../operations/PRODUCTION-CHECKLIST.md`](../operations/PRODUCTION-CHECKLIST.md) — go-live checklist
- [`../operations/RUNBOOKS.md`](../operations/RUNBOOKS.md) — deployment + operations runbooks
- [`../operations/BACKUP-RESTORE.md`](../operations/BACKUP-RESTORE.md) — backup & restore
- [`../operations/DEMO-CREDENTIALS.md`](../operations/DEMO-CREDENTIALS.md) — demo accounts
- [`../../docker/theme-test/README.md`](../../docker/theme-test/README.md) — theme Docker harness
- [`../../build/README.md`](../../build/README.md) — build-box export
- [`../../eskoofy-nodejs-app/docs/PORTING-STATUS.md`](../../eskoofy-nodejs-app/docs/PORTING-STATUS.md) — Node clone: ported vs pending
- [`../design/VARIANT-BLUEPRINT.md`](../design/VARIANT-BLUEPRINT.md) — how to build a variant from the app