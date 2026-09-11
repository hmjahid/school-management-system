# Eskoofy — School Management System

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)](https://php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-6.x-3E5881?logo=wordpress&logoColor=white)](https://wordpress.org/)

Eskoofy is a full-featured school management software delivered as a **3-product
monorepo**: a Laravel 12 application, a framework-free raw PHP version for shared
hosting, and a WordPress theme. Each product is a feature-equivalent port of the same
codebase, sharing one BD/INT variant strategy.

> **BD vs INT**: `bd` is the current Bangladeshi version (Bengali + English, ministry
> links, bKash/Rocket/Nagad). `int` is the international English-only version
> (PayPal/Stripe/Paddle). Variants are **build-time profiles of one codebase** — never
> separate forks. Every BD/INT difference is data/config, never hardcoded branching.

## Products

| Folder | Product | Stack | Status |
|--------|---------|-------|--------|
| `eskoofy-app/` | Full management software | Laravel 12, Blade, Tailwind CSS | Active — most work happens here |
| `eskoofy-php/` | Raw PHP version (no framework) | Native PHP + PDO/MySQL | Complete — for shared hosting without Composer |
| `eskoofy-theme/` | WordPress theme | WP hooks, shortcodes, REST, CPTs | Complete — plugin-theme hybrid |
| `eskoofy-website/` | Marketing site + license server | Raw PHP (app Core), en/bn i18n, PWA | Complete — int-only, USD pricing |

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
├── eskoofy-app/          Laravel 12 app (bd/int profiles via config/eskoolfy.php)
├── eskoofy-php/          Raw PHP app — no Composer at runtime, runs on shared hosting
├── eskoofy-theme/        WordPress theme — plugin-theme hybrid
├── eskoofy-website/      Marketing site + license server, license/lookup API, PWA, en/bn i18n
├── build/                Export box: profiles + zip the app/theme/php/website variants
├── docs/                 Design/review/runbook docs
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
php -S localhost:8000 -t public
```

Point the document root at `public/` on the shared host.

```bash
cd eskoofy-php && composer test     # dev-only PHPUnit suite (255 tests / 463 assertions)
```

### eskoofy-website (marketing + license server)

```bash
cd eskoofy-website
cp .env.example .env        # set DB_* for the licensing DB
mysql -u root -p < database/schema.sql
php -S localhost:8001 -t public
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
| Laravel | `cd eskoofy-app && composer test` (PHPUnit, 900+ tests) + `./vendor/bin/pint --test` |
| Raw PHP | `cd eskoofy-php && composer test` (PHPUnit, 255 tests / 463 assertions) |
| WordPress theme | `cd eskoofy-theme && composer run lint` (PHPCS) |
| Website | `cd eskoofy-website && composer test` (PHPUnit, 79 tests / 214 assertions) |

GitHub Actions (`.github/workflows/ci.yml`) runs the Laravel test suite, theme
linting, PHP + website tests, and export smoke tests for the app/theme/php/website
variants on every push/PR.

## Documentation

- `WORKPLAN.md` — phase plan (0–8) and BD/INT variant rules
- `docs/RUNBOOKS.md` — deployment runbooks + semver tagging convention (`app/v*`, `theme/v*`, `v*`)
- `docs/` — ADMISSIONS, API-PAYMENTS, BACKUP-RESTORE, DEMO-CREDENTIALS, PAYMENT-DEPLOYMENT, and more

## License

MIT