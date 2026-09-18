# Features Implementation Prompt #18 — Client Delivery, Gateways, Push, Caching

## Objective
Extend `eskoofy-website` (license server / marketing) with client-delivery tooling and
add cache-clearing across all 4 products. Verify each change end to end and keep
`composer test` green in every affected repo. Commit per product.

---

## A. Website admin — client deliverables (eskoofy-website)

### 1. Product packages (ZIP) for licensed clients
**Goal:** admins can publish downloadable product packages (app/php/theme ZIPs) and send
them to licensed clients.
- New `packages` table: id, product (app|php|theme), version, filename (stored under
  `storage/packages/`), size, notes, is_active, created_at, updated_at. Add to
  `database/schema.sql` + ALTER the live DB (idempotent).
- Admin page `/admin/packages` (grouped under Sales & licensing): upload ZIP (product,
  version, file, notes), list packages, delete, and a "Send to licensed clients" action
  that emails every customer with an active license for that product a download link.
- Client-facing download page `/account/downloads`: lists the customer's licensed
  products and the active package(s) for each with a download link (auth-gated; admin
  serves the file from `storage/packages/`, noindex).
- Reuse `App\Services\Mailer::sendView` with a new `package_available` email template
  (add to `views/emails/` + a default subject; content rendered from template).

### 2. Email templates management
**Goal:** admins can edit the emails the license server sends.
- New `email_templates` table: id, key (unique: welcome|license_issued|payment_received|
  license_expiring|package_available|contact_message), subject, body, is_active,
  created_at, updated_at. Schema + live DB.
- Admin page `/admin/email-templates` (System group): list + edit subject/body per key,
  with `{name}`, `{licenseKey}`, `{amount}` etc. placeholders documented.
- `Mailer::sendView` looks up the template by key; uses DB override when present, else the
  default `views/emails/{template}.php`. The `layout.php` stays the shell.

### 3. User manual & setup guide files for clients
**Goal:** admins can publish help files (PDF/markdown) and send them to clients.
- New `client_documents` table: id, title, kind (user_manual|setup_guide|other), filename
  (stored in `storage/documents/`), size, notes, is_active, created_at, updated_at.
- Admin page `/admin/client-documents` (System group): upload/list/delete + "Send to
  clients" (emails all active licensed customers a download link).
- Client page: same `/account/downloads` page lists documents alongside packages.

### 4. Push notifications
**Goal:** admins can push a notice to customers' account dashboards.
- New `push_notifications` table: id, title, message, link, created_by, created_at,
  updated_at (sent to all customers with an account).
- Admin page `/admin/push-notifications` (System group): compose + send; recorded in
  activity log.
- Customer account area: a notifications bell/list on `/account` showing unread
  push_notifications (add a `read_at` column; mark-read on click). Keep it simple and
  noindex.

## B. Website admin — payment gateways + demo images

### 5. Payment gateway management (intl + local)
**Goal:** admins can enable/configure gateways (manual, bKash, Rocket, Nagad, Stripe,
PayPal, Paddle) from the admin instead of .env.
- Settings keys `gateway.<code>.enabled` (0/1) and `gateway.<code>.*` credential fields
  stored in the `settings` table (reuse the existing key/value table — no schema change).
- Admin page `/admin/gateways` (Sales & licensing group): toggle enabled + edit test/live
  credentials per gateway; Save writes settings.
- `GatewayFactory`/checkout consult these settings first, falling back to `.env` config.
  BD gateways listed for BD customers, int gateways for international.

### 6. Demo featured images on demo posts
**Goal:** the seeded blog posts show a featured image.
- Update the 2 published demo posts in the live DB to set `featured_image` to a picsum
  URL (seeded via `database/schema.sql` INSERT or a one-off SQL).
- Blog list + post detail views already have `featured_image` column — render `<img>`
  when present (check `views/site/blog.php` + `views/site/post.php`).

## C. Cache clearing — all 3 products + website

### 7. Website admin "Clear cache"
- `/admin/cache` action (System group quick action + button): clear `storage/cache/*`,
  `storage/framework/views/*` (if present), `opcache_reset()`, and bump a
  `cache.version` setting used to bust frontend asset URLs. Flash success + activity log.

### 8. eskoofy-app (Laravel) "Clear cache"
- Dashboard action (Settings → System or a "Clear cache" button in settings): run
  `Cache::flush()`, `Artisan::call('view:clear')`, `config:clear`, `route:clear`,
  `event:clear` guarded to admin role. Add route + controller method + button.

### 9. eskoofy-php "Clear cache"
- Dashboard action: delete `storage/framework/views/*`, `storage/cache/*`,
  `opcache_reset()`. Route + controller method + button.

### 10. eskoofy-theme "Clear cache"
- Dashboard action (`esk-cache` page + sidebar item under System): `wp_cache_flush()`
  (if available), `wp_clean_plugins_cache()`, delete `esk_cache` transients, flush
  opcache. New admin page per theme conventions.

## D. Fix
- **php favicon**: `public/favicon.svg` was missing from eskoofy-php (and app) → copied
  from the website; served 200. Done this round.

## Cross-cutting
- Preserve `bd` behaviour unless a task changes it. php view parity = copy
  `eskoofy-app/resources/views/**` byte-identical. Theme views in `eskoofy-theme/views/admin/`.
- Update `database/schema.sql` whenever a website table changes; ALTER the live DB.
- Run `composer test` in all repos; `php -l` touched files; boot-check each new page.
- Commit per product with clear messages.

---

## Status

| # | Task | Status |
|---|------|--------|
| A1 | Product packages (ZIP) + send to licensed clients + `/account/downloads` | Done `24513f1` (+ path fix `033f6aa`) |
| A2 | Email templates admin + Mailer DB overrides | Done `24513f1` (verified override render) |
| A3 | Client documents (user manual / setup guide) + send | Done `24513f1` |
| A4 | Push notifications admin + account notifications + read receipts | Done `24513f1` |
| B5 | Payment gateway admin (intl + local), settings-driven overrides | Done `24513f1` (verified at checkout) |
| B6 | Demo featured images on demo posts (DB + schema seed) | Done `24513f1` |
| C7 | Website admin clear-cache | Done `24513f1` |
| C8 | eskoofy-app clear-cache | Done `7c666ce` |
| C9 | eskoofy-php clear-cache | Done `7c666ce` |
| C10 | eskoofy-theme clear-cache page | Done `7c666ce` |
| D | php favicon (missing favicon.svg) | Done `7c666ce` |
| — | Theme `/client/` home permalink (hardened filters + self-heal + front-page revert) | Done `202e271` + `7c666ce`; docker env verified clean |

All repos green: website 92/256, php 311/699, app 923/2361.