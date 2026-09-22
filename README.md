# Eskoofy — School Management System

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)](https://php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-6.x-3E5881?logo=wordpress&logoColor=white)](https://wordpress.org/)
[![Next.js](https://img.shields.io/badge/Next.js-15-000000?logo=next.js&logoColor=white)](https://nextjs.org/)
[![Prisma](https://img.shields.io/badge/Prisma-6-2D3748?logo=prisma&logoColor=white)](https://www.prisma.io/)

Eskoofy is a full-featured school management software delivered as a **4-product
monorepo**: a Laravel 12 application (`eskoofy-laravel-app`), a framework-free raw PHP
version for shared hosting (`eskoofy-php-app`), a WordPress theme (`eskoofy-wp-theme`) and a
single-architecture Node.js clone of the app (`eskoofy-nodejs-app` — Next.js App Router +
Prisma, one codebase for the public site, dashboard and API). Each product is a
feature-equivalent port of the same codebase, sharing one BD/INT build-time variant strategy.
The **branding website + license server** (`eskoofy-branding-website`) markets and sells the
products — it is **not a product** itself.

> **BD vs INT**: `bd` is the current Bangladeshi version (Bengali + English, ministry
> links, bKash/Rocket/Nagad). `int` is the international English-only version
> (PayPal/Stripe/Paddle). Variants are **build-time profiles of one codebase** — never
> separate forks. Every BD/INT difference is data/config, never hardcoded branching.

## Products

| Folder | Product | Stack | Status |
|--------|---------|-------|--------|
| `eskoofy-laravel-app/` | School management software | Laravel 12, Blade, Tailwind CSS | Active — most work happens here ([`AGENTS.md`](eskoofy-laravel-app/AGENTS.md)) |
| `eskoofy-php-app/` | Raw PHP port (no framework) | Native PHP + PDO/MySQL | Deferred — completed port on hold (`WORKPLAN.md` Phase 6, gate 0.2) ([`AGENTS.md`](eskoofy-php-app/AGENTS.md)) |
| `eskoofy-wp-theme/` | WordPress theme | WP hooks, shortcodes, REST, CPTs | Complete — active sync with app ([`AGENTS.md`](eskoofy-wp-theme/AGENTS.md)) |
| `eskoofy-nodejs-app/` | Node.js clone of the app (107 tables, 585 routes, CRUD + public site, i18n, API) | Next.js App Router, Prisma, Tailwind, Vitest | Clone functional (schema/routes/sidebar/CRUD/site verified end to end); pixel-level Blade parity + integrations pending — [`PORTING-STATUS.md`](eskoofy-nodejs-app/docs/PORTING-STATUS.md) ([`AGENTS.md`](eskoofy-nodejs-app/AGENTS.md)) |

### Branding website (not a product)

| Folder | Purpose | Stack | Status |
|--------|---------|-------|--------|
| `eskoofy-branding-website/` | Marketing/branding site + **license server** — sells the products | Raw PHP (app Core), en/bn, PWA | Complete — int-only, USD pricing ([`AGENTS.md`](eskoofy-branding-website/AGENTS.md)) |

> **Feature consistency:** all products ship the **same feature set** (+ sales copy on
> the branding website). Default scope for any feature change = **all products**,
> confirmed before implementing — see [`docs/design/FEATURE-PROPAGATION.md`](docs/design/FEATURE-PROPAGATION.md).

## Features

### Academic
- Classes, sections, batches, subjects, teachers, guardians
- Attendance (students & staff), exam scheduling, result processing & grading
- Timetables/routines, assignments & submissions, academic sessions

### Finance & Payments
- Fee structures per class, invoices, fee payments & receipts
- 7 payment gateways: bKash, Rocket, Nagad (BD) + Stripe, PayPal, Paddle (INT) + Offline
- Refunds, payment history, financial reports

### Admissions & Admissions Pipeline
- Online admission form, document upload, review / approve / reject / enroll workflow

### Content & CMS
- Public website with news, notices, events, gallery, testimonials, careers
- Announcements, contact forms, committee members, website settings

### Operations
- Transport (vehicles, routes, stops), hostel management, library (books & issues)
- Payroll (salary structures, payslips, leave), expenses & categories
- SMS campaigns, notifications, certificate / admit card / ID card generation
- Roles & permissions, JSON API under `/api/v1`, backups

## Repository layout

```
├── AGENTS.md             Agent conventions (root-level — read first)
├── eskoofy-laravel-app/          Laravel 12 app (bd/int profiles via config/eskoolfy.php)
├── eskoofy-php-app/          Raw PHP port — no Composer at runtime, shared hosting
├── eskoofy-wp-theme/        WordPress theme — plugin-theme hybrid
├── eskoofy-nodejs-app/      Node.js clone — Next.js App Router + Prisma + Tailwind
├── eskoofy-branding-website/      Branding site + license server (NOT a product) — int-only
├── build/                BD/INT export box + feature-propagation gate (export.sh, propagate/)
├── docker/               Docker dev harnesses for all 4 products (dev.sh + docker/{laravel,node,php,theme}-dev/)
├── docs/                 Docs index + guides/operations/design/features/quality/planning/prompts/notes (map: docs/README.md)
├── WORKPLAN.md           Multi-product plan (phases, gates, milestones)
└── workplan-implementation-plan.md   Per-task implementation tracker
```

## Development server

[**`docs/guides/DEVELOPMENT.md`**](docs/guides/DEVELOPMENT.md) is the full guide for
running every product and the website locally. Summary:

### Requirements

PHP **8.2+**, Composer 2+, Node **20.9+**, MySQL/MariaDB (the app also works on SQLite), and
Docker (for the WordPress theme harness and the all-product dev harnesses). Each component
uses its own port so they can run side by side:

| Component | Port | Entry |
|---|---|---|
| `eskoofy-laravel-app` | 8000 | `php artisan serve` |
| `eskoofy-php-app` | 8051 | `php -S localhost:8051 -t public` |
| `eskoofy-nodejs-app` | 3000 | `npm run dev` |
| `eskoofy-branding-website` | 8011 | `php -S 127.0.0.1:8011 -t public` |
| `eskoofy-wp-theme` | 8080 | Docker harness (`docker/theme-test`) |

### Docker dev harnesses (all products)

Every product has its own Docker dev harness — **no per-product MySQL/Node install needed**.
Each runs under its own Compose project with globally unique container names and
non-overlapping host ports, so any subset (or all four) can run simultaneously. Code is
**bind-mounted** into the containers, so source edits are picked up on the next browser
refresh / hot-reload — no image rebuild:

```bash
./docker/dev.sh up                # start ALL products
./docker/dev.sh up laravel        # start one: theme | laravel | node | php
./docker/dev.sh down              # stop all (keeps DB volumes)
./docker/dev.sh seed laravel      # re-seed demo data for one product
./docker/dev.sh ps                # container status
./docker/dev.sh urls              # print every product's URL
```

| Product | URL | Stack (in-container) | DB host port |
|---|---|---|---|
| WP theme | <http://localhost:8080> | WordPress 8.2 + Apache | — (internal) |
| Laravel | <http://localhost:8090> (+ Vite HMR :5173) | PHP built-in server + MySQL 8 | `33068` |
| Node.js | <http://localhost:3000> | Next.js dev server + MariaDB | — (internal) |
| Raw PHP | <http://localhost:8051> | PHP built-in server + MariaDB | `33069` |

Laravel migrations auto-run on boot and a fresh DB auto-seeds the demo accounts
(`docs/operations/DEMO-CREDENTIALS.md`); Node/PHP need a one-time
`./docker/dev.sh seed node|php`. Full reference: [`docker/README.md`](docker/README.md).

### eskoofy-laravel-app (Laravel)

```bash
cd eskoofy-laravel-app
composer install
npm install
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed   # SQLite by default — no DB server needed
composer dev                       # app :8000 + queue + pail + Vite HMR
```

Open **http://127.0.0.1:8000** → `/login` → `/dashboard`. Admin:
`admin@school.com` / `ADMIN_PASSWORD` in `.env` (dev default `ChangeMe!2026$Tr0ng`).
Student/guardian portals: `/student/login`, `/guardian/login`. API: `/api/v1`.
Individual servers: `php artisan serve` + `npm run dev`.

> Prefer Docker (MySQL included)? `./docker/dev.sh up laravel` serves the app on
> <http://localhost:8090> with Vite HMR and auto-migrated/seeded MySQL — see
> *Docker dev harnesses* below.

### eskoofy-nodejs-app (Node.js clone — port 3000)

Single-architecture Next.js app: public site, dashboard and `/api/v1` all in one project
(no separate API server, no SPA). It uses the **same database schema** as the Laravel app.

```bash
cd eskoofy-nodejs-app
cp .env.example .env          # set DATABASE_URL (MySQL) + AUTH_SECRET
npm install
npm run prisma:generate
npm run prisma:push           # create the 107 tables (or prisma:migrate)
npm run db:seed               # demo accounts (see below)
npm run dev                   # http://localhost:3000
```

> Prefer Docker (MariaDB included)? `./docker/dev.sh up node` then one-time
> `./docker/dev.sh seed node` — see *Docker dev harnesses* below.

Login at `/login` with the app's admin demo account (`admin@school.com` /
`ChangeMe!2026$Tr0ng`). Dashboard: `/dashboard`. API: `/api/v1` (same
`{success,message,data[,meta]}` envelope as the app).

Verification & parity:

```bash
npm run typecheck && npm run lint && npm test   # 48 Vitest tests
npm run route:parity                            # 585 routes + 95 sidebar keys vs the app
npm run build                                   # production build
```

`npm run route:parity` re-runs `php artisan route:list` in `eskoofy-laravel-app/`
and fails if the Node route surface drifts. Porting ledger:
[`eskoofy-nodejs-app/docs/PORTING-STATUS.md`](eskoofy-nodejs-app/docs/PORTING-STATUS.md).

### eskoofy-php-app (raw PHP — port 8051)

```bash
cd eskoofy-php-app
cp .env.example .env        # set DB_* credentials
mysql -u root -p < database/schema.sql
php database/seed_demo.php  # optional demo accounts (idempotent)
php -S localhost:8051 -t public
```

Point the document root at `public/` on a shared host. `composer test` runs the
dev-only PHPUnit suite. Need a MySQL without installing it? Run it in a Docker
container — see *Database via Docker* in
[`docs/guides/DEVELOPMENT.md`](docs/guides/DEVELOPMENT.md) — or use the full
harness: `./docker/dev.sh up php` + `./docker/dev.sh seed php`.

### eskoofy-branding-website (marketing + license server — port 8011)

```bash
cd eskoofy-branding-website
cp .env.example .env        # set DB_* for the licensing DB
mysql -u root -p eskoofy_website < database/schema.sql
php -S 127.0.0.1:8011 -t public
```

On this machine the site's MySQL already runs in the `esk-mariadb` Docker
container (host port `3307`, includes the `eskoofy_website` schema):
`docker start esk-mariadb`. The site itself is served by `php -S` — there is no
web container. See the *Database via Docker* section in
[`docs/guides/DEVELOPMENT.md`](docs/guides/DEVELOPMENT.md).

Single international (int) site: USD pricing, en/bn language switcher, PWA shell,
license management API at `/api/v1`. Admin seed: `admin@eskoofy.com` / `admin123`.

### eskoofy-wp-theme (WordPress — port 8080)

```bash
cd docker/theme-test
docker compose up -d        # installs WP + activates theme, bind-mounts eskoofy-wp-theme/
```

Open **http://localhost:8080** (login at `/login/`, dashboard at `/dashboard/`).
Without Docker: copy the theme into `wp-content/themes/eskoofy`, activate it, then
Settings → Eskoofy. Custom DB tables are created automatically on activation. Lint with
`cd eskoofy-wp-theme && composer install && composer run lint`.

### Build-box export

```bash
cd build
./export.sh app bd   # or: app int | theme bd | theme int | php bd | php int
./export.sh website  # website is always int (en/USD/UTC)
```

### Demo credentials

All products share the same demo accounts — canonical list in
[`docs/operations/DEMO-CREDENTIALS.md`](docs/operations/DEMO-CREDENTIALS.md):

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@school.com` | `ChangeMe!2026$Tr0ng` (dev) |
| Principal | `principal@school.com` | `principal123` |
| Teacher | `teacher.john@school.com` / `teacher.sarah@school.com` | `teach1234` / `teach5678` |
| Accountant | `accountant@school.com` | `accountant123` |
| Librarian | `librarian@school.com` | `librarian123` |
| Bulk | `teacher1..30@` / `student1..5@` / `parent1..10@school.com` | `password` |
| Website admin | `admin@eskoofy.com` | `admin123` |

## Tests & CI

| Product | Command |
|---------|---------|
| Laravel | `cd eskoofy-laravel-app && composer test` (PHPUnit, 927 tests) + `./vendor/bin/pint --test` |
| Raw PHP | `cd eskoofy-php-app && composer test` (PHPUnit, 299 tests / 604 assertions) |
| WordPress theme | `cd eskoofy-wp-theme && composer run lint` (PHPCS) |
| Node.js clone | `cd eskoofy-nodejs-app && npm run typecheck && npm run lint && npm test` (48 Vitest tests) + `npm run route:parity` |
| Website | `cd eskoofy-branding-website && composer test` (PHPUnit, 103 tests / 303 assertions) |

GitHub Actions (`.github/workflows/ci.yml`) runs the Laravel test suite, theme
linting, PHP + website tests, the Node typecheck/lint/test/parity job, and export
smoke tests for the app/theme/php/website variants on every push/PR.

## Documentation

Start at [`docs/README.md`](docs/README.md) — map + index of the whole `docs/` tree.
The tree is organized into folders by topic:

- `docs/guides/` — how-to guides. [`DEVELOPMENT.md`](docs/guides/DEVELOPMENT.md) = run
  everything in the development server (ports, quick starts, credentials, troubleshooting)
- `docs/operations/` — deployment, runbooks, backups, credentials, payments ops
  (`RUNBOOKS.md`, `PRODUCTION-CHECKLIST.md`, `BACKUP-RESTORE.md`, `DEMO-CREDENTIALS.md`,
  `API-PAYMENTS.md`, `ADMISSIONS.md`, …)
- `docs/design/` — product proposals, research and cross-product rules
  (`FEATURE-PROPAGATION.md` — feature-consistency rule + runner
  `build/propagate/propagate-feature.sh`; `VARIANT-BLUEPRINT.md` — phased roadmap for adding
  a product with the app as reference; `NODEJS-VARIANT.md` — Node stack decision (shipped as
  `eskoofy-nodejs-app`); `SMART-SCHOOL-IMPLEMENTATION.md` — planned "smart" layer;
  `SUPPORT-WIDGET.md` — branding-site support widget approach;
  `COMPETITIVE-ANALYSIS.md`, `PAYMENT-MODEL.md` — research + tiered pricing for the branding site)
- `docs/features/` — improvement proposals + implementation trackers
  (`FEATURE-IMPROVEMENTS.md`, `UIUX-IMPROVEMENTS.md`, `UIUX-GUIDELINES.md`,
  `IMPLEMENTATION-PLAN.md`, `MARKETPLACE-ELIGIBILITY.md`)
- `docs/quality/` — QA audits + review artifacts
  (`QA-PARITY-REPORT.md`, `QA-UI-FRONTEND-REPORT.md`, `QA-REMEDIATION-PLAN.md`,
  `SENIOR-PM-REVIEW-REPORT.md`, `REVIEW-PROMPT.md`)
- `docs/planning/` — historical plans, audits and reviews (read-only record)
- `docs/prompts/` + `docs/prompts/master/` — feature-implementation prompt files
  (incl. `product-folder-rename-branding-and-node-variant.md`, the session that renamed the
  product folders, extended the branding site and built the Node clone)
- `docs/notes/` — working notes / journal (tracked)
- `AGENTS.md` — agent conventions for this monorepo (read first)
- `WORKPLAN.md` — phase plan (0–9) and BD/INT variant rules

Per-product documentation lives inside each product folder:

| Product | Docs |
|---|---|
| `eskoofy-laravel-app/` | `docs/USER-MANUAL.md`, `docs/SETUP-GUIDE.md`, `AGENTS.md` |
| `eskoofy-php-app/` | `docs/USER-MANUAL.md`, `docs/SETUP-GUIDE.md`, `AGENTS.md` |
| `eskoofy-wp-theme/` | `docs/USER-MANUAL.md`, `docs/SETUP-GUIDE.md`, `AGENTS.md` |
| `eskoofy-nodejs-app/` | `docs/PORTING-STATUS.md`, `README.md`, `AGENTS.md` |
| `eskoofy-branding-website/` | `docs/USER-MANUAL.md`, `docs/SETUP-GUIDE.md`, `AGENTS.md` |

## License

MIT