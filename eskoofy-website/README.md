# Eskoofy Website — Marketing, sales, and license server

The Eskoofy **website** is the marketing, sales, and licensing site for the
Eskoofy school management system. It is **not itself a product** — it markets
and sells the two deployments:

- **Eskoofy School App** (`/products/app`)
- **Eskoofy WP Theme** (`/products/theme`)

…and operates the license server (JSON API + customer dashboard + admin
backend). The site is **one codebase, one variant** (always exported as
`int`): English/USD/UTC, with a language switcher between English and
Bangla. Visit IP/location decides the default UI language
(Bangladesh → Bangla, otherwise English); a manual switch always wins.

See `WORKPLAN.md` Phase 8 and `workplan-implementation-plan.md` for scope.

## Stack

- Raw PHP, no framework, no Composer at runtime.
- Reuses the zero-dependency `eskoofy-php/app/Core` (Router, Database,
  QueryBuilder, Model, Controller, View, Session, Auth, Validator, Request,
  bootstrap, Middleware, Helpers).
- PDO/MySQL, PHP 8.2+.

## Features

- Public marketing site (`/`, `/products/{app,theme}`, `/pricing`,
  `/features`, `/about`, `/contact`, `/blog`, `/blog/category/{slug}`,
  `/blog/{slug}`).
- **PWA**: installable, offline-capable (manifest, service worker,
  apple-touch icon, maskable icon, `offline.html`).
- **Language switcher** (`/language/{locale}`) + **location-based default**
  (`/language/geo`).
- Customer license portal (login → `/account` with licenses, activations,
  renewals, payments).
- Admin backend (customers, plans, licenses, payments, **posts**,
  **post-categories**, messages, activity).
- License server JSON API under `/api/v1` (activate/validate/deactivate/status/ping).

## Environment

Copy `.env.example` → `.env`, fill in DB credentials, then import the schema
and (optionally) seed data:

```bash
cp .env.example .env
mysql -u root -p eskoofy_website < database/schema.sql
```

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
composer test        # 77 tests, DB-free via tests/FakeDatabase.php
```