# Eskoofy Website — Marketing, sales, and license server

The Eskoofy **website** is the marketing, sales, and licensing site for the
Eskoofy school management system. It is **not itself a product** — it markets
and sells the four deployments of the school management system:

- **Eskoofy School App** (`/products/app`) — Laravel application
- **Eskoofy WP Theme** (`/products/theme`) — WordPress theme
- **Eskoofy School System (PHP)** (`/products/php`) — raw PHP rewrite for
  shared hosting and low-cost VPS
- **Eskoofy Node App** (`/products/node`) — Node.js variant of the app

…and operates the license server (JSON API + customer dashboard + admin
backend). The site is **one codebase, one variant** (always exported as
`int`): English/USD/UTC, with a language switcher between English and
Bangla. Visit IP/location decides the default UI language
(Bangladesh → Bangla, otherwise English); a manual switch always wins.

See `WORKPLAN.md` Phase 8 and `workplan-implementation-plan.md` for scope.

## Documentation

- [User Manual](docs/USER-MANUAL.md) — public site, license-server API, customer & admin dashboards
- [Setup Guide](docs/SETUP-GUIDE.md) — install, database, configuration, license settings, deploy
- [Deployment Guide](docs/DEPLOYMENT-GUIDE.md) — production deployment & license-server operations
- [Server Requirements](docs/SERVER-REQUIREMENTS.md) — sizing and prerequisites

## Stack

- Raw PHP, no framework, no Composer at runtime.
- Reuses the zero-dependency `eskoofy-php-app/app/Core` (Router, Database,
  QueryBuilder, Model, Controller, View, Session, Auth, Validator, Request,
  bootstrap, Middleware, Helpers).
- PDO/MySQL, PHP 8.2+.

## Features

- Public marketing site (`/`, `/products/{app,php,theme,node}`, `/pricing`,
  `/features`, `/compare`, `/about`, `/contact`, `/blog`, `/blog/category/{slug}`,
  `/blog/{slug}`, `/terms`, `/privacy`, `/refund-policy`).
- **CMS-managed pages**: per-page hero heading/intro/body + SEO
  (meta title/description, canonical, hreflang, JSON-LD, noindex) with en/bn
  locale columns, editable at Admin → Pages. Template text wins until edited.
- **Product-selection wizard** (`/choose`): a 5-question quiz that recommends
  the best-fitting deployment (app / WP theme / raw PHP / Node variant) with a
  runner-up and links to all four product pages.
- **Custom orders** (`/custom-order`): request custom development,
  modifications, or extra features for any product; submissions land in
  Admin → Custom orders for follow-up.
- **Customer support widget** on every public page (quick links + email/phone/WhatsApp,
  configurable in Admin → Settings → Support).
- **Enterprise footer**: grouped product/solutions/company/resources/legal columns, contact
  block, trust strip, social links, status line.
- **PWA**: installable, offline-capable (manifest, service worker,
  apple-touch icon, maskable icon, `offline.html`).
- **Language switcher** (`/language/{locale}`) + **location-based default**
  (`/language/geo`).
- Customer license portal (login → `/account` with licenses, activations,
  renewals, payments).
- Admin backend (customers, plans, licenses, payments, **posts**,
  **post-categories**, messages, activity, **visitor log**, **pages/CMS**,
  **custom orders**).
- **Visitor log** (`/admin/visitors`): KPI cards, 30-day trend chart, top pages/countries,
  filterable + paginated table (bot-filtered); toggled by `visitors.logging_enabled`.
- License server JSON API under `/api/v1` (activate/validate/deactivate/status/ping).

## Environment

Copy `.env.example` → `.env`, fill in DB credentials, then import the schema
and (optionally) seed data:

```bash
cp .env.example .env
mysql -u root -p eskoofy_website < database/schema.sql
```

## Run the dev server

The site is a plain PHP front-controller — no Composer at runtime.

```bash
# from eskoofy-branding-website/
php -S 127.0.0.1:8011 -t public
# or any host/port you like
php -S localhost:8001 -t public
```

Then open <http://127.0.0.1:8011/>. Static assets in `public/` (`/sw.js`,
`/manifest.json`, `/icons/*.png`, `/offline.html`, `/favicon.svg`) are served
directly; everything else routes to `public/index.php`.

The admin seed user is `admin@eskoofy.com` / `admin123` (change in production).

### Geo / language env

| Key | Default | Purpose |
|---|---|---|
| `GEO_LANG_ENABLED` | `true` | Toggle the location-based default language. |
| `GEO_IP_API_URL` | empty | Optional `https://ipapi.co/{ip}/json/`-style URL that returns JSON `{country: "XX"}`. Leave empty to disable remote IP geolocation. |

## Icons

PWA icons live in `public/icons/`. To regenerate, run `./public/icons/generate.sh`
(requires ImageMagick). The PNGs are committed.

## Tests

```bash
composer test        # 123 tests, DB-free via tests/FakeDatabase.php
```