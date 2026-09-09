# Eskoofy PHP — Raw PHP (no framework) version

**Status: COMPLETE.** Full feature-equivalent port of `eskoofy-app/` (Laravel 12) in raw
PHP with no framework, built to run on shared hosting where Composer/Laravel/VPS is not
available. See `WORKPLAN.md` Phase 6.

## Stack

- Native PHP 8.2+, PDO/MySQL, custom lightweight MVC
- No Composer or framework at runtime — manual autoloader + bootstrap
- Runs off a single document root (`public/` with `.htaccess`)

## Layout

```
├── app/
│   ├── Core/             Router, Database, QueryBuilder, Model, Controller, View,
│   │                     Session, Auth, Validator, Request + 5 middleware classes
│   ├── Gateways/         Gateway interface + factory + BdKash/Rocket/Nagad/
│   │                     Stripe/PayPal/Paddle/Offline adapters
│   ├── Helpers/          Global helper functions
│   ├── Controllers/      Site + Auth + 43 Dashboard controllers
│   └── Models/           78 models
├── config/               app.php, school.php, payment.php (all BD/INT gateway config)
├── database/schema.sql   93-table MySQL schema + admin seed
├── lang/                 en/ + bn/ (site_frontend, dashboard, messages)
├── routes/web.php        Full public + dashboard route table
├── views/                97 templates (layouts, public site, dashboard, auth, emails)
└── public/index.php      Front controller
```

## Quick start

```bash
cp .env.example .env      # set DB_HOST / DB_DATABASE / DB_USERNAME / DB_PASSWORD
mysql -u root -p < database/schema.sql
php -S localhost:8000 -t public
```

Internet-facing setup: point the document root at `public/`; `.htaccess` routes all
requests through `index.php`.

Seeded admin login: `admin@eskoofy.com` / `password`.

## Variants

`bd` (bKash/Rocket/Nagad, Bengali+English) vs `int` (Stripe/PayPal/Paddle, English-only)
are both driven by `config/` + `.env` — same codebase, no forked branches. This follows
the monorepo golden rule: every BD/INT difference is data/config, never hardcoded `if (bd)`.