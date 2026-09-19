# AGENTS.md — Eskoofy Theme (WordPress)

This is the **`eskoofy-wp-theme`** product folder inside the Eskoofy monorepo (root `AGENTS.md`).
It is a feature-equivalent port of the Laravel app (`eskoofy-laravel-app/`) as a **plugin-theme
hybrid** WordPress theme (vanilla CSS, no Tailwind; WP template hierarchy + custom plugin layer).

## Project structure

- `style.css` — theme header + vanilla base CSS (front-end only, incl. the public-site reset)
- `functions.php` — theme setup, nav menus, widget areas, text domain, enqueues, PWA routes,
  theme-color/canonical/robots hooks, dynamic `:root` theme variables (theme presets)
- `header.php` / `footer.php` / `front-page.php` / `page.php` / `single.php` /
  `archive.php` / `search.php` / `searchform.php` / `sidebar.php` / `404.php` —
  template files (public site)
- `template-*.php` — page templates (about, academics, admission(s), committee, contact,
  events, faculty, fees, gallery, news, notices, portal, privacy, results, login, …)
- `assets/js/main.js` — vanilla JS, feature-port of the app's `resources/js/app.js`
  (toasts, confirm modal, scroll-top, lightbox, command palette, notifications, favorites,
  PWA install, countdowns, multistep forms, dark mode, mobile menu, …)
- `pwa/` — `sw.js`, `offline.html`, `manifest.json` (served via `template_redirect`)
- `inc/` — plugin layer (loaded by `functions.php`):
  - `front-dashboard.php` — **the app-style dashboard at `/dashboard/`** (routes via
    `template_redirect` + rewrite rules, renders the Tailwind-based admin shell)
  - `admin-shell.php` — dashboard shell (sidebar, topbar, command palette, badges, titles),
    sidebar mirroring the app's groups
  - `admin-pages.php` — legacy wp-admin menu registration (disabled unless
    `ESK_ENABLE_WPADMIN_MENU`; the dashboard lives on the frontend)
  - `database.php` — creates the `esk_*` custom tables on activation
  - `custom-post-types.php` — news, events, notices, galleries, testimonials, committee, careers
  - `rest-api.php` — `esk/v1/` endpoints (students, teachers, classes, exams, results, fees,
    notices, news, **notifications**)
  - `admin-ajax.php` — AJAX handlers (student search, attendance, results, **favorites**)
  - `shortcodes.php`, `payment-gateways.php`, `widgets.php`, `customizer.php`,
    `helpers.php`, `sms-gateway.php`, `plugin-loader.php`
- `views/admin/` — 70+ admin page templates (dashboard shell + module CRUD pages)
- `languages/` — `.pot` + `bn_BD` / `en_GB` `.po`/`.mo`
- `inc/admin-style.css`, `inc/app-dashboard.css` (compiled Tailwind), `inc/admin-shell.css`,
  `inc/admin.js` — dashboard styling/behaviour

## Golden rules

- **UI parity**: the dashboard shell and the public site must look like the app
  (`eskoofy-laravel-app/`). Class naming may differ (`esk-*` vs Tailwind utilities), the rendered
  output must match.
- **BD/INT variants** are build-time profiles (translations + branding), never forks.
- `style.css`'s global reset **must not** apply to the dashboard — it is scoped with
  `:where(body:not(.esk-admin-shell)) *`; keep it that way or the sidebar breaks.
- The dashboard is the **frontend** `/dashboard/`, not wp-admin.

## Key conventions

- Vanilla JS only in `assets/js/main.js` (no jQuery in new code; keep `window.eskToast` /
  `window.eskConfirm` as the public APIs). Data hooks use `data-*` attributes.
- AJAX/REST auth: `window.eskAdmin.nonce` (AJAX, action `esk_ajax_nonce`) vs
  `window.eskAdmin.restNonce` (`wp_rest`) — they are different nonces, don't mix them.
- New admin pages go in `inc/front-dashboard.php` (route) + `views/admin/` (template) +
  `inc/admin-shell.php` (sidebar group + title + icon).
- PWA routes (`/sw.js`, `/manifest.json`) must `status_header(200)` explicitly, otherwise
  WordPress marks the query 404 first.

## Commands

| command | what |
|---|---|
| `composer install` | install dev tooling (phpcs) |
| `composer run lint` | PHPCS (WordPress-Extra ruleset) |
| `composer run lint:fix` | auto-fix |
| `docker compose -f docker/theme-test/docker-compose.yml up -d` | local WP test env on :8080 |
| `php -l inc/admin-shell.php` | quick PHP syntax check |

## Local test environment

`docker/theme-test/` runs a WordPress + MariaDB stack with the theme bind-mounted. See
`docker/theme-test/README.md` for login credentials and the setup script.
Admin: `admin@school.com` / `ChangeMe!2026$Tr0ng` (system login at `/login/`).

## Gotchas

- The compiled dashboard CSS (`inc/app-dashboard.css`) is minified Tailwind — regenerate it
  from the app's Tailwind build if the dashboard markup gains new utility classes.
- `esk_pwa_routes()` and the front dashboard both hook `template_redirect`; keep their route
  matching non-overlapping.
- PHPCS is not a gating check (the committed codebase already has violations); match the
  surrounding file style.