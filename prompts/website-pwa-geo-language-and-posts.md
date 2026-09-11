# Eskoofy Website — PWA, Geo-Language Detection, Marketing Blog

Master prompt file. Execute every part below until all tasks complete and all
verification passes. Implementing agent: work inside `eskoofy-website/` only,
and read `eskoofy-website/README.md` + root `AGENTS.md` first.

> Golden rule (AGENTS.md): never hardcode BD/INT branching inside products. The
> website stays **one codebase, one English/USD/UTC variant** (is always exported
> as `int`). Geolocation only changes the *default UI language of the public
> site*; it is plain runtime config/data, never a fork.

---

## Context — what the website is

`eskoofy-website/` is the **marketing, selling, and licensing site for the
Eskoofy school management system**. It is **not itself a product**. It markets
and sells the two deployments of the school management system:

- **Eskoofy School App** (`/products/app`) — the Laravel application.
- **Eskoofy WP Theme** (`/products/theme`) — the WordPress theme.

It also operates the license server (API + customer dashboard + admin backend).
The blog you will add exists to serve that marketing+selling mission:
announcements, product guides, comparisons, release notes, SEO content — all
pointing back to `/pricing` and `/products/{app,theme}`.

Current facts you must preserve:

- Single variant: `ESKOOFY_VARIANT=int`, `APP_LOCALE=en`, `APP_TIMEZONE=UTC`,
  amounts USD — enforced by `build/export.sh website <any>` and CI smoke.
- Public marketing pages: `/`, `/products/{app,theme}`, `/pricing`,
  `/features`, `/about`, `/contact`. Full en↔bn i18n via `app/Services/I18n.php`
  + `lang/{en,bn}.php` (key parity enforced by `tests/Unit/Services/I18nTest.php`).
- Auth: `customers` table (roles `customer`/`admin`), `AuthMiddleware`,
  `AdminMiddleware`. All POST forms carry `csrf_field()`; `/api/*` is CSRF-exempt.
- Cash-constrained data (plans/pricing) sells in USD regardless of site locale.
- Test suite baseline: `cd eskoofy-website && composer test` → 35 tests / 101
  assertions (PHPUnit 11, DB-free via `tests/FakeDatabase.php`).
- `public/` currently contains only `index.php`. Apache rewrites everything to
  `index.php` **unless a real file exists** — so static files (`sw.js`,
  `manifest.json`, `icons/*.png`, `offline.html`) are served directly, no routes
  needed.

---

## Part A — Progressive Web App (PWA)

Make the marketing site installable and resilient while staying a plain MPA
(multi-page app) on the existing raw-PHP stack. No build step, no new Composer
runtime deps.

### A1. Static PWA shell (new files under `public/`)

- **`public/manifest.json`** (JSON, no comments):
  - `name` "Eskoofy — School Management System", `short_name` "Eskoofy",
    `description` = one-line intent (school management marketing + sales),
    `lang` "en", `start_url` "/", `scope` "/", `display` "standalone",
    `background_color` "#0f172a" (slate-900, matches header/hero),
    `theme_color` "#2563eb" (blue-600), `categories` `["education","business"]`.
  - `icons`: 192×192 any + 512×512 any + 512×512 `purpose: "maskable"`,
    all `image/png`, `src` relative (`/icons/icon-192.png`, etc.).
  - `shortcuts`: two entries — Pricing (`/pricing`) and Blog (`/blog`).
- **Icons** — generate real PNGs and commit them under `public/icons/`:
  - `icon-192.png`, `icon-512.png`, `maskable-512.png`, `apple-touch-icon.png` (180×180).
  - Visual: dark slate-900 rounded square, white bold "E" monogram on a
    blue-600 rounded tile (matches the header logo). For the maskable icon keep
    all content inside the inner 80% safe circle.
  - Add a small dev-only generator `public/icons/generate.sh` (uses ImageMagick
    `convert`/`magick` when available) + `public/icons/README.md` so icons are
    reproducible. The PNGs themselves are committed.
- **`public/favicon.svg`** branded monogram (used as `rel="icon"` and by the
  SVG fallback). Optionally a `favicon.ico` if ImageMagick can produce it.
- **`public/offline.html`** — minimal, self-contained (inline CSS, no external
  CDN) branded fallback page with a "try again" (reload) button and a link to
  `/`. Used when a navigation fails offline.

### A2. Service worker `public/sw.js`

- `const CACHE = 'eskofy-pwa-v1';` (bump this constant on every cache-affecting
  deploy; a comment in the file explains the convention).
- **Precache on install** (`/`, `/blog`, `/pricing`, `/features`, `/about`,
  `/contact`): only public marketing pages. **Never** cache `/account/**`,
  `/admin/**`, `/api/**`, `/checkout`, or any POST response.
- `activate`: delete all older `eskofy-pwa-*` caches, then `self.clients.claim()`.
- `skipWaiting()` on install (old SW never blocks the new one).
- `fetch` handler, `GET` only:
  - **navigation** requests: network-first → fall back to the precached copy of
    that URL → final fallback to `/offline.html`.
  - **cross-origin Tailwind CDN** (`https://cdn.tailwindcss.com`): runtime
    stale-while-revalidate so offline pages keep their layout (opaque
    responses, fail-safe).
  - **other same-origin GET** (existing files / other pages): stale-while-revalidate.
  - return `fetch` untouched for `/api/`, `/account`, `/admin`, `/checkout`.
- Do not use workbox or any external library — plain self-contained JS.

### A3. Registration + `<head>` wiring

- **`public/js/register-sw.js`**: registers `/sw.js` only when
  `'serviceWorker' in navigator` AND the page is HTTPS or hostname is
  `localhost`/`127.0.0.1`. Deferred (`load` event). Log registration failures to
  the console without throwing.
- **`views/layouts/main.php`** `<head>` additions (all pages, keep order):
  - `<meta name="description" content="...">` (concise marketing line; keep
    single English line — translate only if you add a `meta.description` key to
    both lang files, otherwise keep it literal English).
  - `<link rel="manifest" href="/manifest.json">`
  - `<link rel="icon" type="image/svg+xml" href="/favicon.svg">`
  - `<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">`
  - `<meta name="theme-color" content="#2563eb">`
  - `<meta name="apple-mobile-web-app-capable" content="yes">`
  - `<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">`
  - `<meta name="apple-mobile-web-app-title" content="Eskoofy">`
  - load `js/register-sw.js` with `defer` before `</body>`.
- **`views/layouts/admin.php`**: same manifest + theme-color + icon links in
  its `<head>` for consistency, but **no service-worker registration script**
  (admin is never cached).
- The `lang` attribute on `<html>` already uses `I18n::current()` — leave that
  mechanism in charge.

### A4. PWA objective proof

An auditor must be able to produce an install prompt on Android/Chrome (HTTPS
host) and see `/` working from the service worker while offline (Tailwind CDN
served from the runtime cache; navigation falls back through precache →
`offline.html`).

---

## Part B — Location-based default language

**Business rule:** visitors whose IP/location is **Bangladesh** get **Bangla
(bn)** by default; everyone else gets **English (en)** by default. A manual
language switch always wins for the rest of the session. The site remains
single-variant `/USD/UTC` in every other respect.

### B1. Pure geo→locale detector `app/Services/GeoLocale.php`

Static methods (no DB, no I/O → unit-testable):

- `detect(array $server, array $config = [], ?string $timezone = null): string`
  — returns a *supported* locale (`'bn'` or `'en'`; anything not resolvable to
  `bn` returns `en`).
  - Priority (config-order driven, see B2):
    1. **CDN/proxy country header** from `$server['CF_IPCountry']` /
       `$server['X_IPCountry']` / `$server['IPCountry']` (uppercase trim);
       value exactly `BD` → `bn`.
    2. **`Accept-Language`** (`$server['HTTP_ACCEPT_LANGUAGE']`): segment
       starting `bn` (e.g. `bn`, `bn-BD`) → `bn`.
    3. **Remote IP-geo API** — only if `config['geo']['remote_api_url']` is
       non-empty: look up `HTTP_X_FORWARDED_FOR ?: REMOTE_ADDR` via a tiny
       `file_get_contents` call with a short timeout (e.g. 2s) reading JSON
       field `country`; `BD` → `bn`. Any failure is swallowed → next source.
    4. **`$timezone` hint** (client-supplied `Asia/Dhaka`) — *only* upgrades
       `en → bn` when sources above were inconclusive (never downgrades bn→en).
- `isBdHeader(?string $value): bool`, `isBdAcceptLanguage(?string $lang): bool`,
  `isBdTimezone(?string $tz): bool` as small pure helpers so tests target them
  directly.
- Read supported locales from `config/app.php` (`i18n.locales`) — never
  hardcode the en/bn list in the service.

### B2. Config (`config/app.php`)

Add under `i18n`:

```php
'geo' => [
    'enabled'             => $_ENV['GEO_LANG_ENABLED'] ?? 'true',
    'cdn_headers'         => ['CF-IPCountry', 'X-IPCountry', 'IPCountry'],
    'accept_language'     => true,
    'remote_api_url'      => $_ENV['GEO_IP_API_URL'] ?? '',   // '' = disabled
    'remote_api_timeout'  => 2,
    'use_timezone_hint'   => true,
    'bd_locale'           => 'bn',   // the locale a BD visitor should get
    'other_locale'        => 'en',   // everyone else
],
```

Add `GEO_LANG_ENABLED` + `GEO_IP_API_URL` to `eskoofy-website/.env.example`
(commented, with a note that both are optional; default off).

### B3. Hot path — resolution middleware `app/Core/Middleware/LocaleMiddleware.php`

Implements precedence (highest wins):

1. **Session locale (manual switch)** — set by `LanguageController::switch`
   (already exists) — wins.
2. **Session geo locale** — computed once per session, stored on first request.
3. **Config default** (`i18n.default`).

Behavior:

- If no session `locale` and no session `geo_locale`: run `GeoLocale::detect` in
  a try/catch; on any exception store `i18n.default`. Store the result as
  `geo_locale` and apply it (write to `I18n`/`Session` so `I18n::current()`
  returns it).
- Register in `public/index.php` (after `CorsMiddleware`, before routes load, so
  it applies to web + API alike). Apply to all requests; it performs no output,
  so the API is unaffected.
- `I18n::current()` must return: manual session locale → geo session locale →
  default. Update `app/Services/I18n.php` to check the `geo_locale` session key
  between the manual locale and the default (keep the existing supported-locale
  validation).

### B4. Manual switch semantics (`app/Controllers/Site/LanguageController.php`)

- Keep exact current behavior, plus: when a manual switch happens, **clear the
  `geo_locale` session key** so the manual choice is authoritative until the
  session ends.

### B5. Client timezone backstop (config-gated)

Only when `i18n.geo.use_timezone_hint` is true: add a tiny deferred script on
the public pages that sends its resolved timezone exactly once per page view:

`GET /language/geo?tz=<timezone>` (URL-encoded; `Asia/Dhaka` → bn).

- New controller action `LanguageController::geo` that applies only when no
  manual `locale` session is set: it runs `GeoLocale::detect` with the
  timezone hint and stores `geo_locale` if it changed. No redirect loop: the
  script is idempotent (only fires when `geo_locale` is absent, via a
  `data-` attribute or return value).
- Route added to `routes/web.php`.
- Keep this lightweight and non-blocking: `navigator.language` is ignored; only
  the timezone is used.

### B6. Tests

`tests/Unit/Services/GeoLocaleTest.php` (pure, no DB):

- CDN header `BD` → `bn`; `US` → `en`; missing header → falls through.
- `Accept-Language: bn,en;q=0.9` → `bn`; `en-US,q=0.9` → `en`.
- Remote API returning `country: "BD"` → `bn`; API failure/network error →
  falls through (no exception).
- Timezone `Asia/Dhaka` upgrades inconclusive → `bn`; and does NOT downgrade a
  `bn` result to `en`.
- Unknown/invalid header values never throw and never return an unsupported
  locale.
- Precedence in resolution: session manual `locale` > session `geo_locale` >
  default. (Use the `I18nTest` pattern — session-backed, tear down keys.)

---

## Part C — Marketing blog / post system

The site currently has no posts. Add a small, admin-managed **post (blog)
system** whose content exists to market and sell the school management system.
Content itself is authored prose (stored per-row, not in lang arrays); only
labels/navigation are translated. Seed content in English.

### C1. Data model (`database/schema.sql` — append two tables, DDL-only, no
destructive migration; follow existing style: utf8mb4, BIGINT UNSIGNED ids,
soft-delete, timestamps, FKs)

- **`post_categories`**: `id`, `name`, `slug` (unique), `description`,
  `sort_order`, `active` (bool), `created_at`, `updated_at`.
- **`posts`**:
  - `id`, `category_id` (FK → `post_categories` ON DELETE SET NULL),
    `author_id` (FK → `customers` ON DELETE SET NULL — admins are customers,
    see seed below).
  - `title VARCHAR(191) NOT NULL`, `slug VARCHAR(191) NOT NULL UNIQUE`,
    `excerpt VARCHAR(400) NULL`, `content LONGTEXT NOT NULL`,
    `status VARCHAR(16) NOT NULL DEFAULT 'draft'` (values: `draft|published`),
    `featured_image VARCHAR(255) NULL` (URL string — no upload pipeline yet;
    note this explicitly as a future task), `meta_title VARCHAR(191) NULL`,
    `meta_description VARCHAR(255) NULL`,
    `views INT UNSIGNED NOT NULL DEFAULT 0`,
    `published_at DATETIME NULL`, timestamps, `deleted_at`.
  - Indexes: `(status, published_at)`, `slug` unique, `category_id`,
    `author_id`.
- **Seed** (append to the seeds section, all `INSERT IGNORE`):
  - Two categories: `Announcements` (`announcements`) and `Guides` (`guides`).
  - Admin author record exists already (`customers` id 1, `admin@eskoofy.com`).
  - 2–3 realistic `published` posts (one `draft`) in English with `published_at`
    = NOW, e.g.:
    - *"Introducing Eskoofy: one school management system, two deployments"*
      (category Announcements; points at `/products/app` + `/products/theme`).
    - *"How to choose between the Eskoofy app and the WordPress theme"* (Guides).
    - One draft as an editor example.

### C2. Public routes + controller + views

- **`app/Controllers/Site/PostController.php`**:
  - `index()` → list published posts (only `status='published'`,
    `deleted_at IS NULL`, order `published_at DESC`, paginate 6 per page via
    `?page=`; keep it a simple LIMIT/OFFSET query + prev/next links).
  - `show(string $slug)` → single published post → `views/site/post.php`;
    increments `views`; `errors.404` if missing/private/draft.
  - `category(string $slug)` (optional but expected) → published posts for a
    published category.
  - Pass 3 latest published posts as `$recent` for a sidebar/related block.
- **Routes** (`routes/web.php`, public section):
  - `GET /blog`
  - `GET /blog/category/{slug}`
  - `GET /blog/{slug}`
  - (Order matters: register `category/{slug}` before `{slug}` so the router
    matches the longer static prefix first — the existing router matches in
    registration order, verify this assumption in `app/Core/Router.php`.)
- **Views**:
  - `views/site/blog.php` — page heading, category filter links, grid of post
    cards (`featured_image` if set, title, excerpt, category, `published_at`
    formatted with `date('M j, Y')`), pagination links, all labels via `__()`.
  - `views/site/post.php` — article layout: title, meta line (category ·
    date · views), excerpt lead, `content` as HTML (assume trusted admin HTML,
    rendered raw — note this in a comment) or a tiny allow-list sanitizer,
    back-to-blog link, `$recent` list.
- **Nav + home**:
  - Add `nav.blog` link in `views/layouts/main.php` header and footer company
    column (`footer.blog`).
  - Home (`views/site/home.php`): add a "Latest from the blog" section with the
    3 latest published posts linking to `/blog`.
- **i18n keys** added to BOTH `lang/en.php` and `lang/bn.php` (parity test must
  keep passing): `nav.blog`, `footer.blog`, `blog.title`, `blog.sub`,
  `blog.read_more`, `blog.empty`, `blog.back`, `blog.recent`, `blog.category`,
  `blog.published_on`, `blog.featured`, `blog.prev`, `blog.next`.
- **Models**: `App\Models\Post` + `App\Models\PostCategory` mirroring
  `App\Models\Plan` (static `$table`, focused static query helpers: `published`,
  `latest`, `publishedByCategory`, `bySlug`).

### C3. Admin management

- **`app/Controllers/Admin/PostController.php`** (mirror `PlanController` style,
  allowed for `AdminMiddleware`):
  - `index` → table of all posts (title, status, category, author, published_at,
    actions), paginated.
  - `create` / `store` — fields: `title` (required|max:191),
    `slug` (required|max:191, unique in `posts` — validate against DB, reject
    dupes with a friendly flash), `category_id` (nullable int),
    `excerpt` (max:400), `content` (required, LONGTEXT),
    `status` (in:draft,published), `featured_image` (nullable url),
    `meta_title`, `meta_description`, `published_at` (nullable datetime, default
    NOW when publishing).
  - `edit` / `update`, `delete` (POST, soft delete via `deleted_at`).
  - Auto-fill `author_id` from the logged-in admin on create.
- **`app/Controllers/Admin/PostCategoryController.php`** (small): index, create,
  edit, soft-ish delete (only when no published posts reference it).
- **Routes** (inside the existing `/admin` group with `AdminMiddleware`):
  `GET /admin/posts`, `GET /admin/posts/create`, `POST /admin/posts`,
  `GET /admin/posts/{id}/edit`, `POST /admin/posts/{id}`,
  `POST /admin/posts/{id}/delete`, and `/admin/post-categories` equivalents.
- **Views** under `views/admin/posts/` (`index.php`, `form.php`) and
  `views/admin/post-categories/` — reuse the existing admin layout + table/form
  patterns from `views/admin/plans/`; every POST form gets `csrf_field()`.
- Add **Posts** + **Categories** links to the sidebar in
  `views/layouts/admin.php` (and wire any dashboard "recent posts" count on
  `views/admin/dashboard.php` if it already lists counts — do not break it).

### C4. Tests

- `tests/Unit/Models/PostTest.php` (FakeDatabase-backed like `LicenseManagerTest`):
  published/private filtering, bySlug lookup, category scoping.
- `tests/Unit/RouterRegistrationTest.php`: add assertions that `/blog`,
  `/blog/category/{slug}`, `/blog/{slug}` and the admin `/admin/posts*` routes
  are registered.
- `tests/Unit/PwaTest.php` (no DB): assert `public/manifest.json` exists and is
  valid JSON with required `name`, `start_url`, `icons` entries (192 + 512 +
  maskable), that the four icon files exist, that `public/sw.js` contains
  `skipWaiting`/`clients.claim` and the precache list with `/`, and that
  `routes/web.php`-backed admin prefixed URLs (`/admin`) are excluded from the
  precache list via a strict string check of `sw.js`.
- Keep `I18nTest::test_every_en_key_has_bangla_translation` passing with the new
  `blog.*` keys.

---

## Part D — Build / CI / docs

- `build/export.sh website <any>` must include the new static files untouched
  (it rsyncs the whole tree already; confirm `sw.js`, `manifest.json`, `icons/`,
  `offline.html` land inside `build/dist/eskoofy-website-int.zip`) and keep
  `ESKOOFY_VARIANT=int`/`APP_LOCALE=en`.
- `.github/workflows/ci.yml`: `website-test` job already runs `composer test` —
  no new job required unless you add one; just make sure the suite covers the
  new code. No new exports.
- Update `docs/README.md` or `eskoofy-website/README.md` with a short "PWA +
  geo-language + blog" section: what works offline, how geo-locale is configured
  (`GEO_IP_API_URL` hook), how to publish a post.
- Update `workplan-implementation-plan.md`: mark Phase 8 rows done and add rows
  for these three sub-features (PWA, geo-language, posts) with updated test
  baselines.

---

## Definition of done

1. `cd eskoofy-website && composer test` → green; ≥ 40 passing tests
   (baseline 35 + GeoLocale, Post, PWA static checks, route assertions).
2. `php -l` clean on every changed/added PHP file.
3. `gd eskoofy-php && composer test` and `cd eskoofy-app && composer test` still
   green (nothing there may change).
4. Smoke with the built-in server (`php -S 127.0.0.1:8011 -t public`):
   - `/manifest.json` → 200, valid JSON; `/sw.js` → 200;
     `/icons/icon-192.png` → 200.
   - `/blog` → 200 (seeded), `/blog/{slug}` → 200, unknown slug → 404.
   - Language: `curl -H 'CF-IPCountry: BD' /` renders bn; without the header
     renders en; after `GET /language/bn`, geo never overrides back to en in the
     same session.
5. `./build/export.sh website int` → zip contains `public/sw.js`,
   `public/manifest.json`, `public/icons/` and an `APP_LOCALE=en` / int `.env`;
   CI `website-test` + `export` green.
6. `I18nTest::test_every_en_key_has_bangla_translation` passes (new `blog.*` and
   any `meta.*` keys exist in both lang files).
7. Golden rule honored: no `if (bd)`/variant branches; geo is runtime config;
   site still exports as single int variant; USD/UTC untouched.