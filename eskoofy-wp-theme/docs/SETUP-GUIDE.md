# Eskoofy Theme — Setup Guide

> Product: `eskoofy-wp-theme` (WordPress theme, v0.2.0, text domain `eskoofy`) · This guide covers install, activation, database, configuration and deployment.

- Day-to-day usage: [`USER-MANUAL.md`](./USER-MANUAL.md)
- Local WordPress test environment: [`../../docker/theme-test/README.md`](../../docker/theme-test/README.md)
- Run all Eskoofy components locally: [`../../docs/guides/DEVELOPMENT.md`](../../docs/guides/DEVELOPMENT.md)

---

## 1. Prerequisites

| Requirement | Version | Notes |
|---|---|---|
| WordPress | 6.x recommended | Not pinned in `style.css` (no `Requires at least` header) |
| PHP | **8.0+** (8.2+ recommended) | The theme uses `declare(strict_types=1)`, `str_starts_with()`, nullsafe `?->`, union return types |
| MySQL / MariaDB | 5.7+ / 10.x | Custom `esk_*` tables |
| Required plugins | **None** | Self-contained plugin-theme hybrid |
| Docker (optional) | Docker Engine + Compose v2 | For the local test harness |

There are no required plugins. External CDNs referenced by the theme's CSP:
`cdn.tailwindcss.com`, `fonts.bunny.net`, `fonts.googleapis.com` (Inter + Noto Sans Bengali).

---

## 2. Quick start — local WordPress (Docker harness)

The fastest local environment bind-mounts the theme into WordPress + MariaDB so every edit is
visible on refresh (no rebuild step):

```bash
cd docker/theme-test
docker compose up -d        # first run pulls images, installs WordPress, activates the theme
```

`setup-theme.sh` runs once and:

1. pins the site URL to `http://localhost:8080`,
2. activates the theme — which creates all `esk_*` tables and the demo users,
3. creates a demo page for every `template-*.php`.

| What | URL |
|---|---|
| Public site | `http://localhost:8080` |
| System login | `http://localhost:8080/login/` |
| Management dashboard | `http://localhost:8080/dashboard/` |
| WordPress admin | `http://localhost:8080/wp-admin/` |

Useful commands:

```bash
docker compose logs -f wp                    # Apache + PHP logs
docker compose down -v                       # full tear-down (destroys the DB)
docker compose down -v --rmi local && docker compose up -d   # rebuild from scratch
```

---

## 3. Standard install (without Docker)

1. Copy the theme folder into WordPress:

   ```bash
   cp -r eskoofy-wp-theme /path/to/wp-content/themes/eskoofy
   ```

2. In **Appearance → Themes**, activate **Eskoofy**.
3. Go to **Settings → Eskoofy** to finish setup.
4. Custom DB tables are created **automatically on activation** — no manual SQL import.

---

## 4. What activation does

Hooked on `after_switch_theme` (`functions.php`), in order:

1. **`esk_create_tables()`** (`inc/database.php`) — `dbDelta` creates all `esk_*` tables and
   sets the `esk_db_version` option (`1.0.0`).
2. **`esk_encrypt_gateway_secrets()`** — migrates plaintext gateway API secrets to AES-256-GCM
   (`esk1:` values).
3. **`esk_create_demo_users()`** — creates/updates the demo users (see §6).
4. **`esk_assign_homepage_and_blog()`** — ensures **Home** and **Blog** pages exist and sets
   `show_on_front`/`page_on_front`/`page_for_posts`, then flushes rewrite rules.
5. **`esk_repair_site_url()`** — strips a stray `/client` segment from `home`/`siteurl`.
6. **`esk_install_demo_content()`** — seeds demo content (`inc/demo-content.php`), guarded by
   the `esk_demo_seeded` option (idempotent).

Re-run demo content or URL repair any time from **Dashboard → System → Tools**
(`/dashboard/tools/`), which handles the `esk_install_demo` and `esk_repair_url` actions.

The three extra roles (`teacher`, `accountant`, `librarian`) are registered on `init`, and the
capability map is stored in the **`esk_role_caps`** option.

---

## 5. Configuration

| Where | What |
|---|---|
| **Settings → Eskoofy** (`/dashboard/settings/`) | School name/tagline/contact, currency, established year, hero/slider CMS, branding and theme preset |
| **Dashboard → Roles & Permissions** (`/dashboard/roles/`) | Edit the `esk_role_caps` capability map per role |
| **Customizer** (`inc/customizer.php`) | Logo, colours, theme presets (dynamic `:root` variables) |
| **Widgets** | `sidebar-1`, `footer-1/2/3`, `home-hero`, `home-features` |
| **Menus** | *Primary* and *Footer* (`Appearance → Menus`) |
| Gateway / SMS options | WordPress options: `esk_sms_options`, gateway settings via `inc/payment-gateways.php` / `inc/sms-gateway.php` |

`ESK_ENABLE_WPADMIN_MENU` — define it truthy to also register the legacy wp-admin **Eskoofy**
menu (default off; the dashboard is the frontend `/dashboard/`).

### Variants (BD / INT)

BD and INT are build-time profiles of this single theme — translations (`.pot`/`.po`) plus a
per-variant stylesheet/profile; never forked releases.

---

## 6. Seeded accounts

Created by `esk_create_demo_users()` on activation (WP usernames in the first column):

| Username | Email | Password | Role |
|---|---|---|---|
| `admin` | `admin@school.com` | `ChangeMe!2026$Tr0ng` | administrator |
| `principal` | `principal@school.com` | `principal123` | administrator |
| `teacher.john` | `teacher.john@school.com` | `teach1234` | teacher |
| `teacher.sarah` | `teacher.sarah@school.com` | `teach5678` | teacher |
| `accountant` | `accountant@school.com` | `accountant123` | accountant |
| `librarian` | `librarian@school.com` | `librarian123` | librarian |

Log in at **`/login/`** (system login) with `admin@school.com` / `ChangeMe!2026$Tr0ng` to reach
`/dashboard/`. WordPress platform users sign in at `wp-login.php` — in the Docker harness the WP
admin defaults to `admin` / `admin` (override via the harness `.env`); some docs also list
`ChangeMe!2026$Tr0ng`. Canonical school-product credentials:
[`../../docs/operations/DEMO-CREDENTIALS.md`](../../docs/operations/DEMO-CREDENTIALS.md).
**Change all demo passwords before production use.**

---

## 7. Development

```bash
cd eskoofy-wp-theme
composer install
composer run lint       # PHPCS (WordPress-Extra ruleset)
composer run lint:fix   # phpcbf auto-fix
```

> PHPCS currently reports a large pre-existing backlog (see the note in the
> [User Manual](./USER-MANUAL.md#7-verification-status)). It is documented in `AGENTS.md` as
> non-gating even though the monorepo CI runs it — treat it as advisory for now.

Quick syntax check while editing: `php -l inc/admin-shell.php`.

---

## 8. Build / export

```bash
cd ../
./build/export.sh theme bd    # → build/dist/eskoofy-wp-theme-bd.zip
./build/export.sh theme int   # → build/dist/eskoofy-wp-theme-int.zip
```

The export script attempts `wp i18n make-pot` when `wp-cli` is available.

---

## 9. Troubleshooting

| Symptom | Fix |
|---|---|
| Theme changes not visible | Hard-refresh (`Ctrl-Shift-R`) or add a `?v=<timestamp>` cache-buster; the Docker harness bind-mounts live files |
| Dashboard looks unstyled | Confirm `inc/app-dashboard.css` is enqueued and the global reset stays scoped to `body:not(.esk-admin-shell)` |
| `/dashboard/` 404s | Re-save permalinks (Settings → Permalinks) to flush rewrite rules, or re-activate the theme |
| `/sw.js` or `/manifest.json` 404 | `esk_pwa_routes()` must `status_header(200)` explicitly; keep it non-overlapping with the dashboard `template_redirect` |
| Can't reach wp-admin menu | The wp-admin menu is off by default; enable `ESK_ENABLE_WPADMIN_MENU` if you need it |
| New dashboard page missing from sidebar | Add route in `inc/front-dashboard.php`, template in `views/admin/`, and the sidebar group/title/icon in `inc/admin-shell.php` |

---

## Related docs

- [`USER-MANUAL.md`](./USER-MANUAL.md)
- [`../README.md`](../README.md) · [`../AGENTS.md`](../AGENTS.md)
- [`../../docker/theme-test/README.md`](../../docker/theme-test/README.md)
- [`../../build/README.md`](../../build/README.md)
- [`../../docs/operations/DEMO-CREDENTIALS.md`](../../docs/operations/DEMO-CREDENTIALS.md)
- [`../../docs/guides/DEVELOPMENT.md`](../../docs/guides/DEVELOPMENT.md)
