# Eskoofy Branding Website — CMS, Product-Suggestion & Custom-Order Prompt

Hello! Please work through the three tasks below **one at a time, from top to
bottom**, and continue until every task is finished and verified. This prompt is
self-contained: read the golden rules, do the work, verify it, then record every
new capability in the feature-tracking files listed in the wrap-up section.

All changes happen inside **`eskoofy-branding-website/`** (the marketing/sales +
license-server site). Nothing here is a "school product change" — this site
sells and supports the four school products (Laravel app / raw-PHP / WP theme /
Node.js variant).

## Hard rules

- Follow `eskoofy-branding-website/AGENTS.md`. This site is the **`int` variant**
  and uses a **`en`/`bn`** language switcher — every visible string must exist in
  both `lang/en.php` and `lang/bn.php` (the `I18nTest` en↔bn key-parity test will
  fail otherwise).
- **Zero new runtime dependencies**: no Composer packages. Reuse the existing
  zero-dependency mini framework in `app/Core/` (Router, Database, Model,
  Validator, Controller, View, Session, Auth, `ActivityLog`, `Mailer`).
- Keep existing behavior byte-for-byte when no CMS override is configured
  (seed rows with NULL content = template defaults still win).
- Preserve the **`/api/v1` license-server contract** untouched.
- Match existing code conventions: admin CRUD exactly like
  `app/Controllers/Admin/PostController.php` (constructor guard +
  validate → insert/update → `ActivityLog::log` → flash → redirect; soft delete
  via `deleted_at`); views styled like `views/admin/posts.php` /
  `views/admin/post_form.php`; public pages styled like `views/site/*.php`.
- Every POST form includes `<?= csrf_field() ?>` (CSRF is enforced globally by
  `app/Core/bootstrap.php`).
- `database/schema.sql` is the single source of truth for the DB. Apply schema
  changes to it **and** to a live/local database so manual testing works.

## Commands

| command | what |
|---|---|
| `php -S 127.0.0.1:8011 -t public` | dev server (from `eskoofy-branding-website/`) |
| `composer test` | PHPUnit (DB-free via `tests/FakeDatabase.php`) |
| `php -l <file>` | syntax-check any touched PHP file |
| `mysql ... eskoofy_website < database/schema.sql` | (re)import schema |

---

## Task 1 — Full CMS control for the marketing pages

**Goal:** give the admin dashboard a "Pages" CMS that controls the SEO of every
public page and the headline content of the main marketing pages, fully aware
that the site is **dual-language** (`en` + `bn`).

### 1.1 Database

Add a `pages` table to `database/schema.sql` (utf8mb4/InnoDB, following the
existing style, with `created_at`/`updated_at`/`deleted_at` timestamps):

- `id` PK, `name` (admin label, e.g. "Home page"), `route` **unique** (the real
  path, e.g. `/`, `/pricing`, `/products/app`), `sort_order`, `status`
  (`active`/`draft`, default `active`), `noindex` TINYINT default 0.
- Per-locale content (both `en` and `bn`): `title_*`, `heading_*` (VARCHAR 191),
  `intro_*` (TEXT), `content_*` (LONGTEXT, HTML allowed).
- Per-locale SEO (both `en` and `bn`): `meta_title_*` (VARCHAR 191),
  `meta_description_*` (VARCHAR 255).
- Shared SEO: `canonical` (VARCHAR 255), `hreflang_en`, `hreflang_bn`
  (VARCHAR 255 — per-locale URL overrides), `json_schema` (LONGTEXT — one JSON-LD
  object or an array of objects).
- Seed one row per public route: `/`, `/about`, `/features`, `/compare`,
  `/pricing`, `/contact`, `/blog`, `/products/app`, `/products/php`,
  `/products/theme`, `/products/node`, `/terms`, `/privacy`, `/refund-policy`.
  Seed `name`/`route`/`sort_order`/`status` and leave all content columns **NULL**
  so template defaults win until the admin edits them.

### 1.2 Models & SEO service

- `app/Models/Page.php` (extends `App\Core\Model`, table `pages`) with:
  - `forPath(string $path): ?array` — exact route match, then fall back up
    parent segments (so `/blog/hello` resolves the `/blog` page, `/products/theme`
    matches its exact route), active + not deleted only.
  - `localized(array $row, string $locale): array` — helper that expands a row
    into `title`, `heading`, `intro`, `content`, `meta_title`, `meta_description`
    for the requested locale, plus the shared `canonical`, `hreflang_en`,
    `hreflang_bn`, `json_schema`, `noindex`.
- Extend `app/Services/Seo.php`:
  - `render()` must honour optional `$seo['hreflang']` (`['en' => '', 'bn' => '']`;
    empty string falls back to the canonical URL). Keep emitting `en`, `bn` and
    `x-default` (x-default always = canonical).
  - `$seo['schema']` entries may be **strings** (raw JSON-LD from the CMS):
    `json_decode` them; skip invalid JSON silently.

### 1.3 Admin CMS (routes under `/admin/pages*`)

- `app/Controllers/Admin/PageController.php` — full CRUD matching the
  `PostController` pattern: index (list all pages), create/store, edit/update,
  delete (soft). Validate `name`, `route` (required), `status`; keep
  `route` unique (reuse the `uniqueSlug`-style de-dup or a simple uniqueness
  check). Log via `ActivityLog::log('admin.created_page'/'updated_page'/
  'deleted_page', ...)`.
- Views `views/admin/pages.php` (table: Name, Route, Status, Updated, Edit/Delete)
  and `views/admin/page_form.php` with clearly separated sections:
  1. **Page** — name, route (path), status, sort order, `noindex` checkbox.
  2. **English** — title, heading, intro, content (HTML textarea).
  3. **বাংলা** — same fields.
  4. **SEO** — meta title + meta description (en + bn), canonical, hreflang en,
     hreflang bn, JSON-LD schema textarea (with a hint that it accepts one object
     or an array).
- Add **Pages** to the admin sidebar (`views/layouts/admin.php` → `Content & inbox`
  group) and to the Quick-create list (label "Edit a page" → `/admin/pages/create`).

### 1.4 Public output

- `views/layouts/main.php`: after computing `$seoPath`, load `Page::forPath(...)`
  and merge overrides into `$seo` for the current locale — meta title,
  meta description, canonical, hreflang, `noindex`, and the raw `json_schema`
  appended to `$seo['schema']`. The `<title>` tag must use the merged SEO title
  when present. This makes SEO fully CMS-driven for **every** routed page.
- Wire the localized `$page` row into the main marketing-page controllers/views so
  a configured heading/intro replaces the hero copy and a configured `content_*`
  block renders on the page (fallback to the existing translated template when the
  CMS fields are NULL — see the hard rules). Apply to at least: home, about,
  features, compare, contact, pricing hero, the three existing product pages
  (`app`/`php`/`theme`), legal pages, and the blog index hero. Reuse a small
  partial (e.g. `views/site/partials/page_hero.php`) to avoid duplicating markup.

### 1.5 Tests

- `tests/Unit/Models/PageTest.php` (DB-free via `FakeDatabase`): route lookup
  (exact, parent-segment fallback, missing → null), status filter, localization
  picks the right locale columns and falls back gracefully.
- Extend `tests/Unit/Core/RouterRegistrationTest.php`: assert `/admin/pages`
  routes are registered and `views/admin/pages.php` + `page_form.php` exist, plus
  `views/site/choose.php` and the custom-order views (tasks 2–3) exist.

---

## Task 2 — Product-suggestion tool ("Which variant fits my school?")

**Goal:** help visitors pick the right variant of the school management system.
The four candidates are the **Laravel App**, **Raw PHP**, **WP Theme**, and the
**Node.js variant** (Node ships as a clone of the app; it's not yet on the
checkout — its recommendation must point the visitor to contact/custom-order).

### 2.1 Recommender engine

`app/Services/ProductRecommender.php` — pure, tested logic:

- `candidates(): list<array{key,label,desc,icon,link}>` describing all 4.
- `recommend(array $answers): array{product, reason, runnerUp}` — deterministic
  rules over a small set of questions (school size, hosting, tech preference /
  comfort, top priority). At minimum:
  - already on / wants **WordPress** → theme;
  - **shared/budget hosting** → raw PHP;
  - values a **JavaScript/Node** stack or wants to extend with JS → node;
  - otherwise (default / full-featured self-hosted) → app.
  Return a `reason` that a non-technical school administrator can read.
- Unit-test every branch (`tests/Unit/Services/ProductRecommenderTest.php`).

### 2.2 Public wizard

- Routes: `GET /choose` (form/wizard) and `POST /choose` (compute + show result).
- `app/Controllers/Site/ChooseController.php`: render the wizard; on submit read
  the answers, run `ProductRecommender`, render `views/site/choose_result.php`.
- Views `views/site/choose.php` (step-by-step, styled with the existing Tailwind
  classes, works without JS) and `views/site/choose_result.php` — result card for
  the recommended product (name, one-line reason, top bullets, CTA) plus a "not
  sure?" secondary card linking to `/compare` and `/contact`. For the **Node**
  recommendation the primary CTA must link to `/custom-order?product=node` (task 3).
- Add a visible "Find my best fit" entry: a nav link (or under Products/Compare),
  a card/CTA on the home page, the `/compare` page, and the `/pricing` page.
- All visible strings in `lang/en.php` + `lang/bn.php` (`choose.*`).

---

## Task 3 — Custom development / modification / extra-feature ordering

**Goal:** give visitors a way to **order custom development, modifications, or
extra features for any of the 4 products/variants**, storing requests so the
sales/admin team can follow up.

### 3.1 Database

Add `custom_requests` to `database/schema.sql`:

- `id`, `name`, `email`, `phone` (nullable), `product` (VARCHAR 16, one of
  `app`/`theme`/`php`/`node`/`multi`), `request_type` (VARCHAR 32:
  `custom_development`/`modification`/`extra_feature`/`other`), `subject`,
  `details` (TEXT, required), `budget` (VARCHAR 32, nullable —
  e.g. `< $100` / `$100–$500` / `$500+` / `not_sure`), `timeline` (nullable),
  `status` (VARCHAR 16 default `new`: `new`/`in_review`/`quoting`/`approved`/
  `declined`/`done`), `read_at`, timestamps. Index `status`.

### 3.2 Public form

- Routes: `GET /custom-order` and `POST /custom-order`.
- `app/Controllers/Site/CustomOrderController.php`: pre-fill `product` from
  `?product=` (validate against the allowed set). `store()` validates
  name/email/details (+ product/request_type `in:` rules, optional fields
  length-limited), inserts, sends a best-effort email to the sales inbox
  (`site.sales_email`) reusing `App\Services\Mailer` + a new
  `views/emails/custom_request.php` template, flashes success, redirects back.
- View `views/site/custom_order.php`: product selector (all 4 + multi),
  request-type selector, subject, name/email/phone, budget, timeline, details,
  reassurance copy ("no obligation, we reply within 1 business day"). Dropdowns
  must **not** depend on JS.
- Visible strings in `lang/en.php` + `lang/bn.php` (`custom_order.*`).

### 3.3 Admin follow-up

- Routes under `/admin/custom-requests`:
  `GET /admin/custom-requests` (list + filters by status),
  `POST /admin/custom-requests/{id}/read` (mark read),
  `POST /admin/custom-requests/{id}/status` (update status).
- `app/Controllers/Admin/CustomRequestController.php` + view
  `views/admin/custom_requests.php` (unread badge on the inbox style, status
  pills, inline status select + read button per row). Add **Custom orders** to
  the admin sidebar (`Content & inbox`) with an unread-count badge like Messages.

### 3.4 CTAs

- Link `Custom development` from: the pricing page (near plan cards), the three
  existing product pages (or the shared `partials/services_addon.php`), the
  `choose_result`, and the footer.

---

## Wrap-up (do this before reporting done)

1. `php -l` every touched `.php` file; run `composer test` from
   `eskoofy-branding-website/` and leave the suite green.
2. Boot the dev server and open: a public page (SEO meta present + hreflang),
   `/admin/pages` (CRUD), `/choose` (wizard + result), `/custom-order`
   (submit one), `/admin/custom-requests` (see the submitted row).
3. **Record the features** (repository-wide tracking files):
   - `docs/feature-tracking/build-branding-matrix.py` — add new feature rows
     (one per sub-feature under the matching `Area`, e.g. `Public site`,
     `Blog`-adjacent content, `Admin backend`) and **regenerate** the XLSX:
     `python3 docs/feature-tracking/build-branding-matrix.py`.
   - `eskoofy-branding-website/README.md` — add the features to the bulb list.
   - `eskoofy-branding-website/docs/USER-MANUAL.md` — document the new public
     pages + admin sections.
   - `workplan-implementation-plan.md` (Phase 8 notes) — one line noting the
     new capabilities and the updated test count.
   - `docs/design/BRANDING-SITE-IMPROVEMENTS.md` — mark the relevant checklist
     items done where this work satisfies them.

Thank you — happy building!