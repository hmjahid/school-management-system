# Eskoofy PHP — Raw PHP (no framework) version

**Status: ACTIVE.** Feature-equivalent port of `eskoofy-app/` (Laravel 12) in raw PHP with
**no framework at runtime**, built to run on shared hosting where Composer/Laravel/VPS is
not available. See `WORKPLAN.md` Phase 6.

## Stack

- Native PHP 8.2+, PDO/MySQL, custom lightweight MVC
- No Composer or framework at runtime — manual autoloader + bootstrap
- **Blade-compatible template engine** (`app/Core/Blade.php`) — a compiler + runtime that
  renders the **exact same `.blade.php` templates as the Laravel app** (`resources/views/`),
  so the UI is byte-for-byte the same design system (theming, dark mode, components,
  layouts, stacks, slots, `@can`/`@auth`/`@props`, `<x-...>` components).
- Runs off a single document root (`public/` with `.htaccess`)

## Layout

```
├── app/
│   ├── Core/               Router, Database, QueryBuilder, Model, Relation, Controller, View,
│   │                       Blade (compiler+runtime), Session, Auth, Validator, Request,
│   │                       Schema, Storage, Gate, UrlGenerator, ViewErrorBag,
│   │                       ComponentAttributeBag, Support/{Collection,Str,Carbon,Optional,
│   │                       Stringable,LengthAwarePaginator}, Middleware
│   ├── Gateways/           Gateway interface + factory + BdKash/Rocket/Nagad/
│   │                       Stripe/PayPal/Paddle/Offline adapters
│   ├── Helpers/            Global helpers (e, csrf_field, old, site_ui, dashboard_ui,
│   │                       route(), __(), collect(), optional(), Str, config()…)
│   ├── Controllers/        site + auth + 42 dashboard modules + 11 API
│   └── Models/             78 models with Eloquent-lite features (casts, scopes,
│                           relationships, ArrayAccess so old array code keeps working)
├── resources/views/        THE Laravel Blade view tree (copied verbatim from eskoofy-app)
├── config/                 app.php, school.php, eskoolfy.php, payment.php, sms.php,
│                           routes.php (name→URI map generated from `route:list`)
├── database/schema.sql     93-table MySQL schema + admin seed
├── lang/                   en/ + bn/ (site_frontend, dashboard, messages — from eskoofy-app)
├── routes/web.php          Full public + dashboard route table
├── routes/api.php          JSON API (results/lookup, news, notices, events + protected CRUD)
├── views/                  Legacy PHP templates (fallback when no Blade file exists)
└── public/index.php        Front controller (loads routes, CSRF, CORS)
```

## UI parity (Blade)

The raw PHP app now renders the **same Blade templates as the Laravel app** through a
minimal Blade compiler supporting the directives the app actually uses:

- Layouts (`@extends/@section/@yield/@parent/@show`), includes, stacks (`@push/@stack`),
  `@php`, `{{ }}` / `{!! !!}` escapes with nested-literal handling, `@{{ }}`
- Control flow: `@if/@elseif/@else/@unless/@isset/@empty/@foreach/@forelse/@for/@while/
  @switch/@case/@break/@default` with a `$loop` variable
- Auth/perms: `@auth/@guest/@can/@cannot/@elsecan/@canany` (via `App\Core\Gate`),
  `@error/@enderror` with an `$errors` bag, `@csrf`, `@method`
- Anonymous components `<x-…/>`, `<x-slot:name>`, `:prop="$expr"` bindings, `@props`,
  `{{ $attributes->merge([...]) }}`, `@class`
- Laravel globals aliased for templates: `Str`, `Carbon`, `Schema`, `Storage`, `Optional`,
  `Collection`, plus `route()`, `__()`, `collect()`, `request()`, `auth()`, `session()`
- `route()` resolves Laravel route names via the generated `config/routes.php` map

**Ported so far (renders the exact Laravel UI, verified 200s + green suite):**
- Full public site — home, about, academics, news, news article, notices (paginated),
  events, gallery, contact, faculty, committee, transport, routines, results,
  admissions, payments, search, portal, terms, privacy, careers, students-life, sitemap
- Dashboard shell — `layouts.dashboard`, sidebar, topbar (dark mode, locale switch,
  favorites, live clock, user menu, command-palette search) and the dashboard overview
- Home + every public page verified via `php -S` smoke (HTTP 200)

**Remaining (tracked in `workplan-implementation-plan.md`):** porting the remaining
dashboard module controllers to feed the copied Blade views the Eloquent-shaped data they
expect (models/collections/paginators) — the backend-parity track.

## Quick start

```bash
cp .env.example .env      # set DB_HOST / DB_DATABASE / DB_USERNAME / DB_PASSWORD
mysql -u root -p < database/schema.sql
php -S localhost:8000 -t public
```

Internet-facing setup: point the document root at `public/`; `.htaccess` routes all
requests through `index.php`.

Seeded admin login: `admin@eskoofy.com` / `password`.

## Tests

```bash
composer test   # PHPUnit 11 (dev-only). 279 tests / 535 assertions.
```

The integration suite exercises the front controllers against an in-memory fake DB and
asserts the SQL uses real schema columns; it skips view rendering by design (see
`tests/bootstrap.php` — `View::$renderViews = false`).

## Variants

`bd` (bKash/Rocket/Nagad, Bengali+English) vs `int` (Stripe/PayPal/Paddle, English-only)
are both driven by `config/` + `.env` — same codebase, no forked branches. This follows
the monorepo golden rule: every BD/INT difference is data/config, never hardcoded `if (bd)`.