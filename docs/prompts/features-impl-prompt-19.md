# Features Implementation Prompt #19 — Branding Parity, Legal Pages, Manual Payment Approval

## Objective
Fix theme frontend/dashboard parity, add legal pages + manual-payment approval flow to the
branding website, and align dashboard sidebar branding across all 3 products. Verify and
commit per product.

---

## A. Dashboard sidebar branding — "Eskoofy Admin Panel" (app + php + theme)

### 1. App/php sidebar: drop the school-name tagline
**Issue:** after the last change the sidebar shows "Eskoofy" but the tagline shows the school
name ("Example School"). It should read **Eskoofy / Admin Panel** like the php version.
- `eskoofy-app/resources/views/partials/dashboard/sidebar.blade.php`: brand name =
  `config('app.name', 'Eskoofy')`, sub-label = `__('dashboard.admin_panel')` **always**
  (no school-name tagline). Sync byte-identical to `eskoofy-php`.
- Verify by booting each app.

### 2. Theme dashboard sidebar: "Management System" → "Eskoofy Admin Panel"
**Issue:** `inc/admin-shell.php` sidebar brand shows the school name + "Management System".
- Change the brand name to `get_bloginfo('name')` only when a custom logo exists, otherwise
  show **Eskoofy**; caption = **Admin Panel**. Mirrors the app/php sidebar.

---

## B. Theme frontend parity with the app (urgent)

### 3. Investigate and align the theme's public frontend
**Goal:** theme `header.php`/`footer.php`/`front-page.php` must match the Laravel app's
public site (nav, brand, ticker, hero, footer, permalinks).
- Compare `eskoofy-app/resources/views/partials/site/nav.blade.php`, `footer.blade.php`,
  `home.blade.php`, `layouts/app.blade.php` vs theme `header.php`, `footer.php`,
  `front-page.php`, `style.css`.
- Fix concrete gaps found: brand block (Eskoofy mark + name), announcement ticker, nav items
  and labels, footer columns, section headings. Keep the theme's `esk_site_ui()` i18n.
- Permalinks: ensure theme links use `home_url()` (already the case) and the `/client/`
  normalisation stays in place.

---

## C. Branding website — legal pages + manual payment approval

### 4. Refund policy, Terms & Conditions, Privacy Policy pages
- New routes `/refund-policy`, `/terms`, `/privacy` + `Site\LegalController` + views
  (`views/site/refund-policy.php`, `terms.php`, `privacy.php`) reusing the content-page shell
  (inner hero + prose sections).
- Add en/bn lang keys, footer links (Refund Policy / Terms / Privacy), sitemap entries, and
  SEO `$seo` blocks (noindex off, canonical).

### 5. Manual / bank transfer payment approval flow
**Issue:** `ManualGateway::process()` marks payments paid instantly. Required: manual payments
sit **pending** until an admin approves them; on approval the system sends the product
package, license key and client documents to the customer.
- `ManualGateway::process()` → returns `['success' => false, 'status' => 'pending',
  'message' => 'Your payment is awaiting confirmation.']` (do NOT mark paid, do NOT issue).
- `CheckoutController::process()`: when the gateway result is `pending`, redirect to
  `/checkout/status/{reference}?status=pending` (PaymentStatusController already shows a
  pending message) instead of erroring.
- Admin payments page (`/admin/payments` + `Admin\PaymentController`): add an **Approve**
  action for `status = 'pending'` manual payments → mark `paid` (set `paid_at`), issue the
  license (LicenseManager::issue), create the subscription, and email the client the
  package download link + license key + documents link. Log activity.
- Verify: checkout with manual → pending payment; admin approve → paid + license + email
  path; account shows the license.

### 6. Website admin dashboard: 3-product dashboard header
- Add a header strip on `/admin` (and/or sidebar footer) with links to the 3 product
  dashboards: App, PHP, Theme (links open the respective products). Simple labelled links.

---

## Cross-cutting
- php view parity = copy app Blade byte-identical. Theme per its own conventions.
- Run `composer test` in all repos; `php -l` touched files; boot-check each change.
- Commit per product.