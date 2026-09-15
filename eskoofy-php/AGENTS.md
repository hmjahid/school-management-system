# AGENTS.md — Eskoofy PHP (Raw PHP Port)

This is the **`eskoofy-php`** product folder inside the Eskoofy monorepo (root `AGENTS.md`).
It is a feature-equivalent port of the Laravel app (`eskoofy-app/`) in **raw PHP with no
framework at runtime**, built for shared hosting without Composer/Laravel/VPS.

## Project structure

- `public/` — single document root; `index.php` front controller + `.htaccess` routing
- `app/Core/` — lightweight MVC core: Router, Database, QueryBuilder, Model, Relation,
  Controller, View, **Blade** (compiler + runtime), Session, Auth, Validator, Request,
  Schema, Storage, Gate, UrlGenerator, ViewErrorBag, ComponentAttributeBag,
  Support/{Collection, Str, Carbon, Optional, Stringable, LengthAwarePaginator}, Middleware
- `app/Controllers/` — site + auth + 42 dashboard modules + 11 API controllers
- `app/Models/` — 78 models (Eloquent-lite: casts, scopes, relationships, ArrayAccess)
- `app/Gateways/` — bKash/Rocket/Nagad + Stripe/PayPal/Paddle/Offline adapters
- `app/Helpers/` — global helpers: `e`, `csrf_field`, `old`, `site_ui`, `dashboard_ui`,
  `route()`, `__()`, `collect()`, `optional()`, `Str`, `config()`, …
- `resources/views/` — **copied verbatim from `eskoofy-app/`**; the raw PHP app renders the
  exact same `.blade.php` templates via the minimal Blade compiler
- `config/` — `app.php`, `school.php`, `eskoolfy.php`, `payment.php`, `sms.php`,
  `routes.php` (name→URI map generated from the Laravel `route:list`)
- `database/schema.sql` — 93-table MySQL schema + admin seed
- `database/seed_demo.php` — idempotent demo-account seeder (run after schema.sql)
- `lang/` — `en/` + `bn/` (from eskoofy-app)
- `routes/web.php`, `routes/api.php` — full public + dashboard + JSON API routes
- `views/` — legacy PHP templates (fallback when no Blade file exists)

## Golden rules

- **UI parity**: keep `resources/views/` byte-identical to `eskoofy-app/`. Changes to shared
  Blade templates must be made in `eskoofy-app/` first, then copied here.
- **No framework at runtime**: the core in `app/Core/` must stay self-contained; never add a
  Composer runtime dependency that breaks shared-hosting deployments.
- **BD/INT variants** are config/`.env` only (`config/eskoolfy.php`), never forks.
- **Always mirror the app**: a feature that lands in `eskoofy-app/` must land here too.

## Key conventions

- The `Blade` compiler (`app/Core/Blade.php`) must support every directive the shared
  templates use. If a template adds a new directive, update the compiler.
- `route()` resolves Laravel route names through `config/routes.php` (regenerate it from the
  app's `route:list` when routes change).
- Student/guardian login is **email + password + role check** (matches the app); there is no
  `admission_number`-based login.
- The dashboard (`/dashboard`) renders the app's `layouts.dashboard` Blade shell; guard keys
  are `student_user_id` / `guardian_user_id` for the student/guardian portals.
- `<x-...>` components (`resources/views/components/`) are compiled by `app/Core/Blade.php`;
  `ComponentAttributeBag` must stay `Htmlable` so `e($attributes)` does not double-encode.

## Commands

| command | what |
|---|---|
| `php -S localhost:8051 -t public` | dev server (port 8051 avoids clashing with the app's 8000) |
| `composer test` | PHPUnit 11 (dev-only) — 299 tests / 604 assertions |
| `php database/seed_demo.php` | idempotent demo accounts |
| `php -l app/Core/Blade.php` | quick PHP syntax check |

## Demo credentials

See `eskoofy-php/README.md` — the canonical table lives there (and in `docs/DEMO-CREDENTIALS.md`).
Primary admin: `admin@eskoofy.com` / `password`; also `admin@school.com` / `ChangeMe!2026$Tr0ng`.

## Gotchas

- Compiled Blade templates are cached in `storage/framework/views/`; their mtime is pinned to
  the source mtime, so after changing the *compiler* (not the template) you must delete the
  cache and touch the templates to force recompilation (opcache can serve stale bytecode).
- The `users.role` column is an ENUM — keep it in sync with `database/schema.sql` when adding
  roles; the live DB can drift if seeded from an older schema.
- The port must not collide with `eskoofy-app` (8000) — keep `APP_URL` in `.env` in sync
  with whatever port you run on.