# AGENTS.md — Eskoofy Website

This is the **`eskoofy-branding-website`** product folder inside the Eskoofy monorepo (root `AGENTS.md`).
It is the **marketing, sales, and license server** for the Eskoofy school management system —
not a school-management product itself. It markets and sells the three deployments
(`/products/app`, `/products/theme`, `/products/php`) and runs the licensing API + customer
dashboard + admin backend. Always exported as the `int` variant (English/USD/UTC, en/bn switcher).

## Project structure

- `public/` — front controller (`index.php`), static PWA assets (`sw.js`, `manifest.json`,
  `icons/`, `offline.html`, `favicon.svg`), site JS
- `app/` — reuses the zero-dependency `eskoofy-php-app/app/Core` (Router, Database,
  QueryBuilder, Model, Controller, View, Session, Auth, Validator, Request, Middleware, Helpers)
  plus website-specific `Controllers/`, `Models/`, `Gateways/`, `Services/`, `Views/`
- `config/`, `database/` (schema + seeds), `lang/`, `routes/web.php` + `routes/api.php`
- `tests/` — DB-free PHPUnit suite (uses `tests/FakeDatabase.php`)
- `views/` — plain PHP templates (no Blade)

## Golden rules

- **Not a school product** — do not port school-management modules here; this site sells them.
- **Single variant (`int`)** — English/USD/UTC by default; a `en`/`bn` language switcher and
  location-based default (`GEO_LANG_ENABLED`) are the only i18n mechanisms.
- **License server** is part of this product — keep the `/api/v1` activate/validate/
  deactivate/status/ping contract stable; customers depend on it.
- No Composer at runtime (shared hosting) — `app/Core` must stay self-contained.

## Key conventions

- Static assets under `public/` are served directly; everything else goes through the
  front controller.
- Admin seed user: `admin@eskoofy.com` / `admin123` (change in production).
- Geo/language behaviour is env-driven (`GEO_LANG_ENABLED`, `GEO_IP_API_URL`) — see README.
- **Support widget** copy/config: `views/site/partials/support_widget.php`; settings keys
  `support.widget_enabled`, `site.support_email`, `site.sales_email`, `site.support_phone`,
  `site.whatsapp`. Approach documented in `docs/design/SUPPORT-WIDGET.md`.
- **Visitor log**: `App\Models\Visitor` + `App\Services\VisitorLogger` (hooked in
  `public/index.php`), admin page `views/admin/visitors.php`, route `/admin/visitors`,
  table `visitors` in `database/schema.sql`. Honours `DNT`, skips admin/api/auth/assets, and
  de-dupes repeat paths per session; controlled by `visitors.logging_enabled`.
- **Footer**: `views/site/partials/footer.php` (included by `layouts/main.php`); social URLs
  come from `site.social_*` settings (blank hides the icon).

## Commands

| command | what |
|---|---|
| `php -S 127.0.0.1:8011 -t public` | dev server (README documents 8011; any port works) |
| `composer test` | PHPUnit — 77 tests (DB-free) |
| `mysql -u root -p eskoofy_website < database/schema.sql` | schema import |

## Gotchas

- Port: the README shows `8011` (and `8001` as an alternative); keep `.env` `APP_URL` in sync
  with whichever port you actually run.
- The en/bn locale switch and geo default both touch the language cookie — keep the
  "manual switch always wins" rule (README documents it).
- PWA: `public/icons/generate.sh` regenerates icons (ImageMagick); PNGs are committed.

See `eskoofy-branding-website/README.md` for the full feature list and environment reference.