# Eskoofy — School Management System

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)](https://php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-6.x-3E5881?logo=wordpress&logoColor=white)](https://wordpress.org/)

Eskoofy is a full-featured school management software delivered as a **3-product
monorepo**: a Laravel 12 application (`eskoofy-app`), a framework-free raw PHP version
(`eskoofy-php`) and a WordPress theme (`eskoofy-theme`). Each product is a
feature-equivalent port of the same codebase, sharing one BD/INT build-time variant strategy.
The **branding website + license server** (`eskoofy-website`) markets and sells the
products — it is **not a product** itself.

> **BD vs INT**: `bd` is the current Bangladeshi version (Bengali + English, ministry
> links, bKash/Rocket/Nagad). `int` is the international English-only version
> (PayPal/Stripe/Paddle). Variants are **build-time profiles of one codebase** — never
> separate forks. Every BD/INT difference is data/config, never hardcoded branching.

## Products

| Folder | Product | Stack | Status |
|--------|---------|-------|--------|
| `eskoofy-app/` | School management software | Laravel 12, Blade, Tailwind CSS | Active — most work happens here ([`AGENTS.md`](eskoofy-app/AGENTS.md)) |
| `eskoofy-php/` | Raw PHP port (no framework) | Native PHP + PDO/MySQL | Deferred — completed port on hold (`WORKPLAN.md` Phase 6, gate 0.2) ([`AGENTS.md`](eskoofy-php/AGENTS.md)) |
| `eskoofy-theme/` | WordPress theme | WP hooks, shortcodes, REST, CPTs | Complete — active sync with app ([`AGENTS.md`](eskoofy-theme/AGENTS.md)) |

### Branding website (not a product)

| Folder | Purpose | Stack | Status |
|--------|---------|-------|--------|
| `eskoofy-website/` | Marketing/branding site + **license server** — sells the products | Raw PHP (app Core), en/bn, PWA | Complete — int-only, USD pricing ([`AGENTS.md`](eskoofy-website/AGENTS.md)) |

> **Feature consistency:** all 3 products ship the **same feature set** (+ sales copy on
> the branding website). Default scope for any feature change = **all products**,
> confirmed before implementing — see [`docs/FEATURE-PROPAGATION.md`](docs/FEATURE-PROPAGATION.md).

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
├── eskoofy-app/          Laravel 12 app (bd/int profiles via config/eskoolfy.php)
├── eskoofy-php/          Raw PHP port — no Composer at runtime, shared hosting
├── eskoofy-theme/        WordPress theme — plugin-theme hybrid
├── eskoofy-website/      Branding site + license server (NOT a product) — int-only
├── build/                BD/INT export box + feature-propagation gate (export.sh, propagate/)
├── docker/               Dev tooling (theme-test WordPress stack)
├── docs/                 Design/review/runbook docs (map: docs/README.md)
├── WORKPLAN.md           Multi-product plan (phases, gates, milestones)
└── workplan-implementation-plan.md   Per-task implementation tracker
```

## Quick start

### eskoofy-app (Laravel)

```bash
cd eskoofy-app
composer install
npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### eskoofy-php (raw PHP)

Requires PHP 8.2+ and MySQL. No Composer needed at runtime.

```bash
cd eskoofy-php
cp .env.example .env        # set DB_* credentials
mysql -u root -p < database/schema.sql
php database/seed_demo.php  # optional demo accounts (idempotent)
php -S localhost:8051 -t public
```

The PHP port is `8051` so it does not clash with the Laravel app (`eskoofy-app` defaults to
`8000`). Point the document root at `public/` on the shared host.

```bash
cd eskoofy-php && composer test     # dev-only PHPUnit suite (299 tests / 604 assertions)
```

### eskoofy-website (marketing + license server)

```bash
cd eskoofy-website
cp .env.example .env        # set DB_* for the licensing DB
mysql -u root -p < database/schema.sql
php -S localhost:8011 -t public
```

Single international (int) site: USD pricing, en/bn language switcher, PWA shell,
and a license management API at `/api/v1`.

### eskoofy-theme (WordPress)

```bash
cd eskoofy-theme
composer install
composer run lint           # PHPCS, WordPress-Extra ruleset
```

Copy the theme folder into `wp-content/themes/eskoofy`, activate it, then
Settings → Eskoofy to finish setup. Custom DB tables are created automatically on
activation.

### Build-box export

```bash
cd build
./export.sh app bd   # or: app int | theme bd | theme int | php bd | php int
./export.sh website  # website is always int (en/USD/UTC)
```

## Tests & CI

| Product | Command |
|---------|---------|
| Laravel | `cd eskoofy-app && composer test` (PHPUnit, 923 tests) + `./vendor/bin/pint --test` |
| Raw PHP | `cd eskoofy-php && composer test` (PHPUnit, 299 tests / 604 assertions) |
| WordPress theme | `cd eskoofy-theme && composer run lint` (PHPCS) |
| Website | `cd eskoofy-website && composer test` (PHPUnit, 79 tests / 214 assertions) |

GitHub Actions (`.github/workflows/ci.yml`) runs the Laravel test suite, theme
linting, PHP + website tests, and export smoke tests for the app/theme/php/website
variants on every push/PR.

## Documentation

Start at [`docs/README.md`](docs/README.md) — map + index of the whole `docs/` tree.

- `docs/FEATURE-PROPAGATION.md` — cross-product feature-consistency rule + runner
  (`build/propagate/propagate-feature.sh`)
- `docs/SMART-SCHOOL-IMPLEMENTATION.md` — planned "smart" layer (automation engines,
  analytics/prediction, opt-in AI) across all products
- `docs/NODEJS-VARIANT.md` — proposal for a **4th product** built on Node.js
  (single-architecture Nest/Next/Adonis)
- `docs/COMPETITIVE-ANALYSIS.md`, `docs/PAYMENT-MODEL.md` — competitor research and the
  freemium/tiered pricing model for the branding website
- `docs/DEMO-CREDENTIALS.md` — seeded demo accounts (all products)
- `docs/RUNBOOKS.md` — deployment runbooks + semver tagging convention (`app/v*`, `theme/v*`, `v*`)
- `docs/planning/` — historical plans, audits and reviews
- `docs/prompts/` + `docs/prompts/master/` — feature-implementation prompt files
- `docs/notes/` — working notes / journal (tracked)
- `AGENTS.md` — agent conventions for this monorepo (read first)
- `WORKPLAN.md` — phase plan (0–8) and BD/INT variant rules

## License

MIT