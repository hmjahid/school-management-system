# Eskoofy Theme (WordPress)

**Status: COMPLETE — plugin-theme hybrid.** Feature-equivalent port of the Laravel app as a
single WordPress theme: a public marketing site + a full **app-style management dashboard**
served at `/dashboard/`, mirroring `eskoofy-app/`. See `../WORKPLAN.md` Phase 7.

BD/INT variants are build-time profiles — translations via `.pot`/`.po` and branding via a
per-variant stylesheet/profile, never forks.

## Architecture

- **Public site** — WP template hierarchy (`front-page.php`, `header.php`, `footer.php`,
  `template-*.php`, `page.php`, `single.php`, …), vanilla CSS in `style.css`, and
  `assets/js/main.js` (vanilla JS port of the app's `resources/js/app.js`: toasts, confirm
  modal, scroll-top, gallery filter + lightbox, multi-step forms, countdowns, command
  palette, debounce, unsaved-changes warning, PWA install, image previews, notifications
  dropdown, dashboard favorites, dark mode, mobile menu, sticky header, sliders, count-up).
- **Management dashboard** — served at `/dashboard/` by `inc/front-dashboard.php` (rewrite
  rules + `template_redirect`) rendering `inc/admin-shell.php`. It mirrors the app's
  dashboard: sidebar groups (Main/Academic/System/Website/Administration/Configuration/
  Help), topbar, stat cards, charts, command palette, dark mode, favorites. The Tailwind-
  based shell CSS lives in `inc/app-dashboard.css` (compiled) + `inc/admin-shell.css`;
  the legacy wp-admin menu is disabled unless `ESK_ENABLE_WPADMIN_MENU` is set.
- **Plugin layer** (`inc/`, loaded by `functions.php`):
  - `database.php` — creates the custom `esk_*` tables on activation
  - `front-dashboard.php` / `admin-shell.php` — the `/dashboard/` shell (70+ pages)
  - `custom-post-types.php` — news, events, notices, galleries, testimonials, committee, careers
  - `rest-api.php` — `esk/v1/` endpoints (students, teachers, classes, exams, results, fees,
    notices, news, **notifications**)
  - `admin-ajax.php` — AJAX handlers (student search, mark attendance, save results,
    **favorites**)
  - `shortcodes.php` — results lookup, admission form, fee payment, student profile, class
    schedule, news/events lists, gallery, contact form, payment gateways
  - `payment-gateways.php` — bKash, Rocket, Nagad, Stripe, PayPal, Paddle, Offline
  - `helpers.php`, `customizer.php`, `widgets.php`, `sms-gateway.php`, `plugin-loader.php`
- `views/admin/` — 70+ dashboard page templates (full CRUD for every module)
- `pwa/` — `sw.js`, `offline.html`, `manifest.json` (served via `template_redirect`)
- `languages/` — `eskoofy.pot` + `bn_BD` / `en_GB` `.po`/`.mo`

## Development

```bash
cd eskoofy-theme
composer install
composer run lint       # PHPCS with WordPress-Extra ruleset
composer run lint:fix   # auto-fix
```

## Local WordPress test environment

```bash
cd ../docker/theme-test
docker compose up -d    # WordPress + MariaDB, theme bind-mounted, port 8080
```

Open **http://localhost:8080**. System login (`/login/`): `admin@school.com` /
`ChangeMe!2026$Tr0ng` (lands on `/dashboard/`). WP admin: `admin` / `ChangeMe!2026$Tr0ng`.
See `../docker/theme-test/README.md` for the full setup/seed details.

## Build

```bash
../build/export.sh theme bd    # → ../build/dist/eskoofy-theme-bd.zip
../build/export.sh theme int   # → ../build/dist/eskoofy-theme-int.zip
```

The export script attempts `wp i18n make-pot` when `wp-cli` is available.

## Key files

| File | Purpose |
|---|---|
| `inc/front-dashboard.php` | `/dashboard/` routing + shell head |
| `inc/admin-shell.php` | dashboard shell (sidebar, topbar, palette, titles, badges) |
| `inc/database.php` | `esk_*` table creation on activation |
| `inc/rest-api.php` | `esk/v1/` REST endpoints |
| `inc/admin-ajax.php` | admin AJAX handlers |
| `assets/js/main.js` | front-end + dashboard JS (app.js port) |
| `style.css` | public-site vanilla CSS (reset is scoped away from the dashboard) |
| `pwa/sw.js` / `manifest.json` | PWA (served via `template_redirect`) |