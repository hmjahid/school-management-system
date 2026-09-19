# Features Implementation Prompt #17 — Sales Ops, SEO, and 3-Product Branding/Parity

## Objective
Complete a multi-product round across `eskoofy-branding-website` (license server / marketing), `eskoofy-laravel-app`
(Laravel), `eskoofy-php-app` (raw PHP), and `eskoofy-wp-theme` (WordPress). Verify each change end to end,
keep `composer test` green in every affected repo, and commit.

---

## A. Website — Sales operations (eskoofy-branding-website)

### 1. Issue license to unregistered users by email only
**Issue:** The admin "Issue license" page only supports selecting an existing customer, so licenses
can't be sold to a brand-new email.

**Done (commit 7b5a5c1):** `views/admin/license_form.php` gained an optional "new customer email"
input; `Admin\LicenseController::store()` auto-creates the customer (role `customer`, random
unguessable password hash so they can reset via forgot-password) when no existing customer is
chosen, validates email format, and requires customer-or-email. Verified via curl.

### 2. Optional add-on package on the issue-license page
**Issue:** No way to record an optional deployment/maintenance add-on when issuing a license.

**Done (7b5a5c1):** Add-on select (`none | deployment | care | deployment_care`) saved into
`licenses.metadata.addons`; shown on `views/admin/license_detail.php`. Verified.

### 3. Blog pages not showing
**Issue:** `/blog/{slug}` returned HTTP 500 (nothing visible).

**Done (7b5a5c1):** Root cause — `Post::bySlug()` JOINed `post_categories` and used bare `slug` in
`WHERE` → ambiguous column 1052. Fixed to `p.slug`. Verified 200.

### 4. Checkout page not working / blank
**Issue:** `POST /checkout` fataled → blank response.
**Done (commit 2d90cc8):** Root cause — `payments.variant` existed in `database/schema.sql` but was
missing from the live DB, so the INSERT threw `Unknown column 'variant'`. Altered the live DB;
verified full purchase (payment paid → license issued → subscription → redirect to license page).

---

## B. Website — Full SEO (eskoofy-branding-website)

### 5. SEO across all public pages
**Done (7b5a5c1):**
- New `app/Services/Seo.php`: canonical URLs (dynamic base), robots meta, Open Graph + Twitter
  cards, hreflang (en/bn/x-default), JSON-LD (Organization, WebSite, WebPage, Product, FAQPage,
  BlogPosting, BreadcrumbList).
- Per-page `$seo` in: home, features, compare, about, contact, blog, post, pricing, checkout, and
  the 3 product pages (product schema uses the lowest plan price; FAQPage from product.faq.1-5).
- `noindex` on `/account`, `/login`, `/register`, `/checkout`, `/admin` (both layouts).
- Static `public/robots.txt` (allow public, disallow private/api) + `public/sitemap.xml`
  (static pages + published posts, absolute eskoofy.com URLs).
- New i18n keys `nav.home`, `about.intro` in en + bn.
- Keep meta description/title/headings semantically correct and lead-generation focused
  (self-hosted-on-your-server framing, deployment & maintenance add-on).

**Remaining SEO polish:** verify every public page emits one `h1`, unique title/description,
canonical, and valid JSON-LD; confirm no `<h1>` inside `<section>` duplicate; check `alt` on
non-decorative images.

---

## C. Products — Branding (app / php / theme)

### 6. Use website logo + favicon + "Eskoofy" brand in all 3 dashboards
- Replace any placeholder/old logo/brand text in the dashboard sidebar + login pages of
  `eskoofy-laravel-app`, `eskoofy-php-app`, `eskoofy-wp-theme` with the Eskoofy mark
  (`eskoofy-branding-website/public/brand/eskofy-mark.svg`) and brand name "Eskoofy".
- Add/point favicon to the website favicon in all 3 products.

### 7. eskoofy-php-app: "Install app" sidebar item missing
- The Laravel dashboard exposes an install/update page; the raw PHP dashboard sidebar is missing
  the equivalent item. Locate the app's sidebar entry (search "Install" / "Update" / "installer"
  in `eskoofy-laravel-app/resources/views/layouts/dashboard.blade.php` and routes), mirror it into
  `eskoofy-php-app`'s dashboard shell and its controller/route so the menu item shows and links work.

### 8. eskoofy-wp-theme dashboard: user dropdown broken + no Communications items
- Fix the admin header user dropdown (menu does not open).
- Add the Communications group to the theme sidebar with the same items as app/php (SMS / Email /
  Announcements / etc.) so parity holds.

### 9. Notification templates & notification preferences sidebar items
- Theme already has them; add the equivalent sidebar items + pages to `eskoofy-laravel-app` and
  `eskoofy-php-app` dashboards (mirror naming/parity rules: php views copied from app views).

### 10. Theme frontend parity with app/php
- Make the WordPress theme's public frontend look/behave like the app/php public site (same
  header/nav/footer structure and styling).

### 11. Same demo content in all 3 products
- Ensure the demo seed data (classes, subjects, students, teachers, fees, exams, announcements,
  etc.) matches across app, php, theme so screenshots/rollouts look identical.

### 12. Theme home page permalink fix (URGENT)
- Theme home page permalink currently renders `http://localhost:8080/client/`. Fix so the home
  page resolves to the correct front page URL (site URL / permalink structure), not the
  `client/` path.

---

## D. Cross-cutting
- Preserve the `bd` profile behaviour byte-for-byte unless a task explicitly changes it.
- Theme views live in `eskoofy-wp-theme/views/admin/*.php` + `inc/admin-shell.php` +
  `inc/database.php` for table changes; php view parity = copy `eskoofy-laravel-app/resources/views/**`.
- Run `composer test` in `eskoofy-laravel-app`, `eskoofy-php-app`, and `eskoofy-branding-website`; `php -l` touched
  files; boot-check the dashboard routes of each product.
- Commit each product's changes with clear messages.

---

## Status

| # | Task | Status |
|---|------|--------|
| A1 | Issue license to unregistered users by email | Done — `7b5a5c1` |
| A2 | Optional add-on on issue-license page | Done — `7b5a5c1` |
| A3 | Blog detail 500 (ambiguous `slug`) | Done — `7b5a5c1` |
| A4 | Checkout blank (missing `payments.variant` in live DB) | Done — `2d90cc8` |
| B5 | Full-site SEO (Seo service, meta/OG/JSON-LD, sitemap, robots, noindex) | Done — `7b5a5c1` |
| C6 | Eskoofy logo/favicon/brand in all 3 dashboards | Done — `202e271` (theme), `3241a43` (app/php) |
| C7 | php Install App item always visible | Done — `3241a43` (root cause: button was `hidden` until `beforeinstallprompt`; now always shown) |
| C8 | Theme user dropdown + Communications sidebar group | Done — `202e271` |
| C9 | Notification templates & preferences items in app/php (+ new templates page) | Done — `3241a43` |
| C10 | Theme frontend parity with app/php | Done (verified) — theme `front-page.php` already mirrors the app's `home.blade.php` section-for-section (hero, features, stats, principal, teachers, committee, testimonials, remarkable students, photo slider, events, news, highlights, CTA, partners); remaining differences are CMS-data-driven, not structure |
| C11 | Same demo content in all 3 products | Done — new `eskoofy-php-app/database/seed_demo_content.php` seeds the same "Example School" demo set as the app seeders and the theme `docker/theme-test/seed-demo.php` (14 classes, 30 teachers, 5 students + guardians, 5 notices, 15 events, 8 galleries, fees). Also fixed systemic `*_at` date-cast 500s on php dashboard pages |
| C12 | Theme home permalink `/client/` | Done — `202e271` (home_url normalisation filter + revert `client` front page) |

All repos green: website 92/256, php 311/699, app 923/2361.