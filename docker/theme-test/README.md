# Eskoofy Theme — Docker test harness

Starts a local WordPress + MariaDB environment with the theme
**bind-mounted** into the container, so every PHP/CSS change you make on
the host applies immediately on page refresh (no rebuild step).

## Prerequisites

* Docker Engine ≥ 20.10 + Compose v2 plugin (`docker compose`)

## Quick start

```bash
cd docker/theme-test
docker compose up -d        # first run pulls images + installs WP
```

`setup-theme.sh` (runs once) will:
1. Wait for the WordPress container to finish its first-run install.
2. Pin the site URL to `http://localhost:8080`.
3. **Activate the Eskoofy theme** — this triggers `after_switch_theme`,
   which creates every `esk_*` DB table and seeds demo users.
4. Create a demo page for every `template-*.php` file and assign the
   correct `_wp_page_template` meta, so the whole public site is
   immediately browsable.

Open **http://localhost:8080** — you should see the Eskoofy homepage,
pre-seeded with the same demo content as the Laravel app (30 teachers,
events, notices, galleries, notable students, homepage CMS sections).

The site URL is resolved from the request host, so **both
`http://localhost:8080` and `http://127.0.0.1:8080` work** for logging in.

## Login

There are two separate login paths:

**1. System login — `/login/`** (the school management system's own login).
Accepts username **or email**, and redirects administrators to the Eskoofy
management dashboard (`wp-admin/admin.php?page=esk-dashboard`). This page does
**not** use the WordPress login.

| Name | Login / Email | Password |
|---|---|---|
| Super Administrator | `admin` or `admin@school.com` | `ChangeMe!2026$Tr0ng` |
| Principal | `principal` or `principal@school.com` | `principal123` |
| Teacher | `teacher.john` | `teach1234` |
| Teacher | `teacher.sarah` | `teach5678` |
| Accountant | `accountant` | `accountant123` |
| Librarian | `librarian` | `librarian123` |

**2. WordPress login — `wp-login.php`** (WordPress platform users / site
administrators only). The site URL is resolved from the request host, so
`localhost:8080` and `127.0.0.1:8080` both work.

> These are the credentials from `docs/operations/DEMO-CREDENTIALS.md`, shared across
> the app, the raw PHP system, and this theme.

## Management dashboard (frontend)

The school management dashboard lives at **`/dashboard/`** on the **frontend**
— it is completely separate from the WordPress admin (like the Laravel/PHP
versions). After logging in at `/login/` you land on `/dashboard/`.

* `/dashboard/` — dashboard overview (stat cards, recent students/admissions)
* `/dashboard/students/`, `/dashboard/fees/`, `/dashboard/settings/`, … —
  one route per module (the sidebar links to these)
* `/dashboard/events/`, `/dashboard/news/`, `/dashboard/gallery/`,
  `/dashboard/testimonials/`, `/dashboard/committee/`, `/dashboard/notices/` —
  the content modules are managed here (frontend), **not** in the WordPress
  admin. News/Events/Galleries/Testimonials/Committee/Notices custom post
  types are hidden from wp-admin.
* Anonymous visitors are redirected to `/login/`.
* Non-admin accounts (teachers/accountant/librarian) get an "Access denied"
  page; administrators and the principal have full access.

The dashboard renders in the app-style shell (**uses the Laravel app's actual
compiled Tailwind CSS** — `eskoofy-wp-theme/inc/app-dashboard.css` — and the same
markup classes as the app's Blade views):

* **Layout** — the app's exact `admin-shell flex h-screen` structure: `w-64`
  sidebar, `h-16` topbar, `flex-1` scrollable content.
* **Topbar** — live clock, "Website", command palette (**Ctrl+K**, searches
  pages + students), EN/বাংলা switcher, pin-favorite, notifications badge,
  dark-mode toggle, user menu (Dashboard / My Profile / School Settings /
  Log out).
* **Sidebar** — filter box, Favorites (pinned pages), grouped navigation with
  the app's `admin-nav-link` styling, dark-mode + logout in the footer.
* **Dashboard home** — the app's exact `admin-stat-card` / `admin-card`
  components: 5 stat cards, Revenue-vs-Expenses chart, Today's Attendance
  mini-cards, 7-day Attendance Trend bars, Quick Actions, and a Workbench
  (fed by seeded payments/expenses/attendance).
* Toasts, app-style confirm modal, and dark mode throughout.

> `inc/app-dashboard.css` is a snapshot of the app's compiled Tailwind build
> (`eskoofy-laravel-app/public/build/assets/app-*.css`). Regenerate it whenever the
> app's dashboard styling changes:
> `cd eskoofy-laravel-app && npm run build && cp public/build/assets/app-*.css ../eskoofy-wp-theme/inc/app-dashboard.css`

The WordPress admin (`/wp-admin/`) is reserved for WordPress platform use and
has **no** Eskoofy menu.

The dashboard URL is `http://localhost:8080/dashboard/` for the theme in
this harness (the Laravel app runs on its own port, e.g. `127.0.0.1:8090`).

## How theme changes are applied

The compose file bind-mounts the local `eskoofy-wp-theme/` directory into
`/var/www/html/wp-content/themes/eskoofy` inside both the WordPress and
WP-CLI containers.

Because PHP re-reads source files on every request, any edit you make to
`front-page.php`, `header.php`, `style.css`, etc. is visible after a
browser refresh — no Docker rebuild required.

> CSS and JS may be cached by the browser. Append `?v=<timestamp>` or
> hard-refresh (`Ctrl-Shift-R`) after editing static assets.

## Environment overrides (.env)

Create a `.env` in this directory to override defaults:

| Variable | Default | Purpose |
|---|---|---|
| `WP_PORT` | `8080` | Host port mapped to Apache 80 |
| `WP_TITLE` | `Eskoofy Theme Test` | Site title |
| `WP_ADMIN_USER` | `admin` | WP admin username |
| `WP_ADMIN_PASSWORD` | *(required)* | WP admin password — **no default**; set a strong value (≥12 chars). `docker compose` refuses to start without it |
| `WP_ADMIN_EMAIL` | `admin@example.test` | Admin email |
| `WP_DEBUG` | `0` | Sets `WP_DEBUG` in `wp-config.php` |

## Useful commands

```bash
# Follow live logs (Apache + PHP errors)
docker compose logs -f wp

# WP-CLI one-liners
docker compose run --rm --entrypoint wp setup option get siteurl
docker compose run --rm --entrypoint wp setup post list --post_type=page --fields=ID,post_title --format=table

# Full tear-down (destroys DB data)
docker compose down -v

# Rebuild from scratch without image caches
docker compose down -v --rmi local
docker compose up -d
```

## Debugging

* **PHP errors**: `docker compose logs wp` or read `wp-content/debug.log`
  (WP_DEBUG + WP_DEBUG_LOG are enabled).
* **Stuck setup container**: `docker compose logs setup` — usually means
  WP did not start. Check the `wp` container logs first.
* **Theme activation fails**: verify `esk_create_tables()` exists in
  `functions.php`/`inc/database.php` and that the MariaDB container is
  healthy (`docker compose ps`).
