# WordPress Theme Conversion Guide — SchoolEase

**Source:** this Laravel 12 school management system (not `archive/`).
**Target:** a **single WordPress theme** that implements the full product. **No plugin.** No ACF, Polylang, WPML, JWT, PWA, or SMS plugins.
**Status of source (2026-08-31):** production-ready Laravel app — Blade + Tailwind v4, bilingual EN/BN, Sanctum API, bKash/Nagad/Rocket + refunds, CMS, portals, backups. Dashboard is a custom Blade back-office (not wp-admin).

This is a **fresh rebuild** as an “application theme,” not a mechanical port of PHP files.

---

## Constraint: theme only

WordPress.org theme review **will reject** this (too much application logic). Ship it as a **self-hosted / commercial theme** (zip + installer), not as a directory theme + companion plugin.

| Allowed in the theme | Not allowed (do not add) |
|---|---|
| `functions.php` + `inc/` classes | `wp-content/plugins/school-*` |
| Custom tables on `after_switch_theme` | Plugin `register_activation_hook` |
| CPTs, taxonomies, REST, roles, shortcodes, **native meta** (`post_meta` / `user_meta` / `term_meta`) | ACF / CMB2 / JWT / Polylang / any custom-fields plugin |
| Composer in the theme (`vendor/`) | Separate plugin `composer.json` |
| Front-end dashboard templates | Depending on wp-admin for school ops |
| Gutenberg blocks registered by the theme | Third-party page-builder plugins |

**Lifecycle:** create/upgrade schema on theme switch **and** on `admin_init` via a stored `schoolease_db_version` option (theme switch alone is not enough if the theme is already active during updates).

```php
// inc/class-installer.php
add_action('after_switch_theme', 'schoolease_install');
add_action('admin_init', 'schoolease_maybe_upgrade');
```

Deactivating the theme hides menus and front-end routes; **do not drop tables** on switch-away (data must survive theme updates).

---

## Architecture mapping (Laravel → theme)

| Laravel (current) | WordPress theme |
|---|---|
| `resources/views/` Blade | Theme templates + `template-parts/` |
| `app/Http/Controllers/Web/` | Template files + `inc/controllers/` |
| `app/Http/Controllers/Api/` | `register_rest_route('schoolease/v1', …)` in `inc/class-rest-api.php` |
| Eloquent models (85) | `inc/models/` wrapping `$wpdb` (not `WP_Query` for operational data) |
| Migrations | `inc/class-installer.php` (`dbDelta` + versioned upgrades) |
| `routes/web.php` + `routes/dashboard.php` | Rewrite rules + `template_include` |
| `routes/api.php`, `payments.php`, `refunds.php`, `admissions.php`, `notifications.php` | REST namespace `schoolease/v1` |
| Middleware (auth, role, throttle, envelope) | `current_user_can()`, capability checks, REST `permission_callback`, custom envelope |
| Spatie Permission | WP Roles + Capabilities created by the theme |
| Spatie Activity Log | `wp_schoolease_activity_log` table + list UI |
| Sanctum tokens | Cookie session for same-origin; Application Passwords or HMAC tokens in theme for API |
| `site_ui()` + `lang/{en,bn}/site_frontend.php` | `schoolease_t()` + `languages/{en,bn}/frontend.php` merged with CMS option tree |
| Extra fields on posts/users/terms | **WordPress meta APIs only** — `update_post_meta()`, `update_user_meta()`, `update_term_meta()`. No ACF, no CMB2, no custom `se_users_meta` table for profile extras |
| Tailwind v4 + Vite | Theme `package.json` + Vite or Tailwind CLI; enqueue built CSS |
| `barryvdh/laravel-dompdf` | `composer require dompdf/dompdf` **inside the theme** |
| Queue / `backup:database` / `queue:monitor-failed` | WP-Cron in the theme (`inc/class-cron.php`) |
| `StandardizeApiResponse` `{success,message,data,meta}` | Same envelope in REST; **never wrap** `*/webhook/*` or `*/callback/*` |
| `throttle.dashboard` 120/min writes | Theme rate limiter on POST/PUT/PATCH/DELETE dashboard routes |
| `TRUSTED_PROXIES`, `SecurityHeaders` | Theme `send_headers` (CSP, HSTS, nosniff, SAMEORIGIN) |

**Do not** map operational entities (students, fees, attendance, ledger, payments) to custom post types. CPTs are for **content**. School ops stay in **custom tables** that mirror Laravel. Extra attributes on content/users/terms use **native WordPress meta fields** (see Step 2).

---

## Step 1 — Theme scaffold

```
wp-content/themes/schoolease/
├── style.css                      # Theme header only (name, version, text-domain)
├── functions.php                  # Bootstrap: autoload, constants, hooks
├── theme.json                     # Colors/typography tokens (optional)
├── index.php
├── header.php · footer.php · 404.php · search.php
├── front-page.php
├── page-templates/                # Public pages matching current routes
│   ├── about.php
│   ├── academics.php
│   ├── admissions.php
│   ├── admissions-apply.php
│   ├── faculty.php
│   ├── students.php
│   ├── committee.php
│   ├── news.php · single-news.php
│   ├── notices.php
│   ├── gallery.php
│   ├── events.php
│   ├── contact.php
│   ├── transport.php
│   ├── routine.php
│   ├── results.php
│   ├── payments.php
│   ├── portal.php
│   ├── terms.php · privacy.php
│   └── login-staff.php · login-student.php · login-guardian.php
├── template-parts/
│   ├── site/                      # nav (1367px), footer, hero, ticker
│   └── dashboard/                 # sidebar groups, topbar
├── dashboard/                     # Front-end back-office (not wp-admin)
│   ├── index.php
│   ├── people/  academics/  admissions/  attendance/
│   ├── finance/ hr/ documents/ library/ campus/
│   ├── cms/ system/ admin/ portals/
├── inc/
│   ├── class-installer.php        # Tables + roles + default options
│   ├── class-rewrites.php
│   ├── class-roles.php
│   ├── class-rest-api.php
│   ├── class-rest-envelope.php
│   ├── class-auth.php
│   ├── class-cms.php
│   ├── class-meta.php               # Native post/user/term meta helpers + boxes
│   ├── class-i18n.php             # schoolease_t() EN/BN merge
│   ├── class-security.php         # headers, nonces, trusted proxies
│   ├── class-throttle.php
│   ├── class-cron.php             # backup + failed-job monitor
│   ├── class-mail.php
│   ├── class-sms.php              # log driver + Twilio (theme composer)
│   ├── class-push.php
│   ├── class-pdf.php
│   ├── class-backup.php
│   ├── payments/
│   │   ├── class-gateway-factory.php
│   │   ├── class-adapter-bkash.php
│   │   ├── class-adapter-nagad.php
│   │   ├── class-adapter-rocket.php
│   │   └── class-offline.php
│   ├── models/                    # Student, Exam, Payment, …
│   ├── helpers.php
│   └── demo-seeder.php            # ALLOW_DEMO_DATA equivalent
├── assets/css · assets/js
├── languages/en/frontend.php · languages/bn/frontend.php
├── composer.json                  # dompdf (+ optional SMS SDK)
├── package.json                   # Tailwind v4
└── README.md
```

`style.css` header:

```css
/*
Theme Name: SchoolEase
Theme URI: https://your-domain.example.com
Description: Full school management application theme (not a directory theme).
Version: 1.0.0
Requires at least: 6.4
Requires PHP: 8.2
Text Domain: schoolease
*/
```

`functions.php` (bootstrap only — no business logic):

```php
<?php
defined('ABSPATH') || exit;

define('SCHOOLEASE_VERSION', '1.0.0');
define('SCHOOLEASE_DB_VERSION', '1.0.0');
define('SCHOOLEASE_PATH', get_template_directory());
define('SCHOOLEASE_URI', get_template_directory_uri());

$autoload = SCHOOLEASE_PATH . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
}

require SCHOOLEASE_PATH . '/inc/helpers.php';
require SCHOOLEASE_PATH . '/inc/class-installer.php';
require SCHOOLEASE_PATH . '/inc/class-rewrites.php';
require SCHOOLEASE_PATH . '/inc/class-roles.php';
require SCHOOLEASE_PATH . '/inc/class-i18n.php';
require SCHOOLEASE_PATH . '/inc/class-security.php';
require SCHOOLEASE_PATH . '/inc/class-rest-api.php';
require SCHOOLEASE_PATH . '/inc/class-auth.php';
require SCHOOLEASE_PATH . '/inc/class-cms.php';
require SCHOOLEASE_PATH . '/inc/class-meta.php';
require SCHOOLEASE_PATH . '/inc/class-cron.php';
require SCHOOLEASE_PATH . '/inc/payments/class-gateway-factory.php';
```

---

## Step 2 — Data layer (mirror Laravel, not CPTs)

### Custom tables (operational)

Create with `dbDelta` in the installer. Prefix `{$wpdb->prefix}se_` (SchoolEase). Align columns with **current** Laravel schema, not the outdated WordPress draft.

| Table | Mirrors | Notes (current Laravel) |
|---|---|---|
| (no extra meta table) | profile extras | WP `users` + **`update_user_meta()`** (`se_role_id`, phone, gender, dob, address, photo). Never a custom `se_users_meta` table |
| `se_students` | `students` | `class_id`, `batch_id`, `roll_number`, `admission_number` |
| `se_guardians` | `guardians` | Pivot `se_guardian_student` |
| `se_teachers` | `teachers` | `employee_id`, salary structure link |
| `se_school_classes` | `school_classes` | Not `class_id` on exams |
| `se_sections` | `sections` | |
| `se_batches` | `batches` | Exam joins student via `batch_id` |
| `se_academic_sessions` | `academic_sessions` | |
| `se_subjects` | `subjects` | |
| `se_exams` | `exams` | **`batch_id` + `academic_session_id` + `section_id` + `subject_id`**. No `class_id`/`year`. `total_marks` on exam. `is_published` **and** `status`. Fully published only when both true (`isFullyPublished()`). |
| `se_exam_results` | `exam_results` | Column is **`obtained_marks`** (not `marks_obtained`). |
| `se_routines` | `routines` | Timetable |
| `se_attendance` | `attendances` | High volume |
| `se_staff_attendance` | `staff_attendances` | |
| `se_fees` / `se_fee_payments` | fees | |
| `se_payments` | `payments` | Status, gateway, `transaction_id`, `refund_status` |
| `se_payment_gateways` | `payment_gateways` | Runtime creds; **never** hardcode secrets |
| `se_payment_webhook_events` | `payment_webhook_events` | Idempotency on `payload_hash` |
| `se_refunds` | `refunds` | HMAC webhooks, concurrent-safe |
| `se_admissions` + documents + settings + tests | admissions | Public apply + admin review |
| `se_chart_of_accounts` / `se_ledger_entries` | ledger | Debit/credit; date range balance |
| `se_expenses` / categories / `se_budgets` | finance | |
| `se_books` / categories / issues | library | |
| `se_vehicles` / routes / stops / assignments | transport | |
| `se_hostels` / rooms / assignments | hostel | |
| `se_leave_requests` / types / `se_payslips` / salary structures | HR | |
| `se_certificates` / `se_admit_cards` / `se_student_id_cards` | documents | PDF |
| `se_assignments` / submissions | academics | |
| `se_messages` / `se_announcements` / `se_notices` | comms | Bilingual columns where Laravel has them |
| `se_sms_campaigns` / recipients | SMS | |
| `se_notification_logs` / templates / preferences / scheduled | notifications | Channels: email, sms, push, in_app |
| `se_website_settings` | `website_settings` | Singleton |
| `se_website_contents` | `website_contents` | `title_en`/`title_bn`, `content_en`/`content_bn`, prune untranslated BN |
| `se_website_media` / documents | CMS media | MIME-validate uploads; no PHP in uploads |
| `se_activity_log` / `se_visitor_logs` | audit | |
| `se_contact_submissions` | contact forms | Throttle 12/min |

### Custom post types (content only)

Register from the **theme** (`init`):

| CPT | Laravel | Public template |
|---|---|---|
| `se_news` | `News` | `/news`, `/news/{slug}` |
| `se_event` | `Event` | `/events` |
| `se_gallery` | `Gallery` | `/gallery` |
| `se_testimonial` | `Testimonial` | homepage / about |
| `se_career` | `Career` | REST exists in Laravel; optional public page |

Do **not** create CPTs for student, exam, fee, payment, ledger, attendance.

CPT extras (subtitle, EN/BN title, event date, gallery category, featured image alt, SEO) live in **`post_meta`** with a `se_` prefix — not extra columns, not ACF.

### Custom fields = WordPress meta only

If a field is not a first-class column on an operational table, store it as native meta. **No custom-fields plugin.**

| Object | API | Examples |
|---|---|---|
| Posts / CPTs | `add_post_meta` / `update_post_meta` / `get_post_meta` | `se_title_bn`, `se_event_start`, `se_event_end`, `se_gallery_category`, `se_meta_description` |
| Users | `update_user_meta` / `get_user_meta` | `se_role_id`, `se_phone`, `se_gender`, `se_date_of_birth`, `se_address`, `se_photo` |
| Terms | `update_term_meta` / `get_term_meta` | `se_sort_order`, `se_label_bn` |
| Site singleton | `update_option` / `get_option` | school name, logo, payment display — not post meta |

Rules:

- Prefix every key with `se_` to avoid collisions.
- Register meta with `register_post_meta()` / `register_term_meta()` / `register_meta('user', …)` (`show_in_rest` only when the REST schema needs it; never expose secrets).
- Theme metaboxes (`add_meta_box` in `inc/class-meta.php`) + `save_post` with nonces. Dashboard forms call the same helpers.
- Arrays/repeaters: one meta key, PHP array stored via `update_post_meta` (WordPress serializes). Prefer one key over `field_0`, `field_1`.
- Querying: `meta_query` is fine for content CPTs. Do **not** put high-volume filter fields (attendance date, exam `batch_id`, payment status) in post meta — those stay SQL columns on `se_*` tables.
- Sanitize on write (`sanitize_text_field`, `sanitize_email`, `wp_kses_post`, integers). Never `$_POST` straight into meta.

```php
// inc/class-meta.php
register_post_meta('se_event', 'se_event_start', [
    'type' => 'string',
    'single' => true,
    'sanitize_callback' => 'sanitize_text_field',
    'auth_callback' => fn () => current_user_can('edit_posts'),
    'show_in_rest' => true,
]);

update_user_meta($user_id, 'se_phone', sanitize_text_field($phone));
$phone = (string) get_user_meta($user_id, 'se_phone', true);
```

### Exam / student lookup (must stay identical)

- Student: `class_id` → class, `batch_id` → batch, `roll_number`.
- Result join: student `batch_id` → exam `batch_id`.
- Public: `GET /results` and REST `GET /schoolease/v1/academics/results/lookup`.
- PDF marksheet uses exam `total_marks` + result `obtained_marks`.

---

## Step 3 — Roles & capabilities

Match `RolePermissionSeeder` (the seeder `DatabaseSeeder` actually runs):

| Role | WP role slug | Access |
|---|---|---|
| Administrator | `administrator` + `se_admin` cap set | Full dashboard + settings |
| Teacher | `se_teacher` | Attendance, marks, notices, view students |
| Student | `se_student` | `/portal` + `/student/*` |
| Parent | `se_parent` | `/guardian/*` (linked children) |
| Accountant | `se_accountant` | Fees, expenses, payments, ledger |
| Librarian | `se_librarian` | Books, issues, library reports |
| User | `se_user` | None |

Caps should mirror Spatie names used in Laravel (`manage_users`, `manage_students`, `collect_fees`, `issue_books`, …). Check with `current_user_can('collect_fees')` on every write.

**Auth (current app has two systems — keep both):**

1. **Staff** — `/login` → front-end dashboard (`/dashboard`).
2. **Student / parent** — `/student/login`, `/guardian/login` → portals. Wrong role → logout + redirect (same as `StudentGuardianMiddleware`).
3. **REST** — cookie for same-origin; Application Passwords or theme-issued tokens for mobile. Self-register **student/parent only**.

Do not use wp-login.php as the primary UI; keep branded templates.

---

## Step 4 — Public site (match `routes/web.php`)

| Laravel route | Theme | Notes |
|---|---|---|
| `/` | `front-page.php` | Hero designs 1–5, CMS slider, principal photo, admission CTA when open |
| `/about` | `page-templates/about.php` | Repeater sections; ministry guidelines |
| `/academics` | academics | |
| `/admissions` + `/admissions/apply` | admissions + apply | Online apply, payment, status, approval letter |
| `/faculty` | faculty | Teacher directory |
| `/students` | students | |
| `/committee` | committee | |
| `/news` `/news/{slug}` | news CPT | |
| `/notices` | notices table | |
| `/gallery` | gallery | Tabs/items share slugs |
| `/events` | events CPT | |
| `/contact` | contact | Throttle 12/min |
| `/transport` | transport | Public info |
| `/routine` | routine | Public timetable |
| `/results` | results | Lookup + PDF download |
| `/payments` | payments | Auth for pay/receipt |
| `/portal` | portal | Student/guardian |
| `/terms` `/privacy` | legal | |
| `/search` | `search.php` | |
| `/locale/{locale}` | query var | `en` / `bn` |
| `/sitemap.xml` `/robots.txt` `/manifest.json` | rewrite endpoints | PWA manifest in theme |

**Nav (current):** groups About · Academics · Contact · News + Home / Portal / Login. Breakpoint **`min-width: 1367px`** (hamburger below). Labels via `schoolease_t('nav.xxx')` — add keys in **both** `languages/en/frontend.php` and `languages/bn/frontend.php`.

**Public site: no theme-toggle.** Dashboard may keep dark mode (`localStorage`), matching current Blade.

**Homepage CMS sections** (template parts, data from `se_website_contents` slug `home` + settings):

hero (selectable design + slider) · stats · principal message · teachers · testimonials · events · news · highlights · partners · CTA · admissions bar (hide apply button when admissions closed).

CMS UI must stay **form fields / repeaters**, not JSON textareas (Laravel already removed JSON editors).

---

## Step 5 — Front-end dashboard (match `routes/dashboard.php`)

Do **not** rebuild school ops inside wp-admin. Use `/dashboard/*` templates + rewrite rules, same IA as `resources/views/partials/dashboard/sidebar.blade.php`.

| Group | Pages |
|---|---|
| Main | Home KPIs, messages, communications, bulk SMS |
| People | Students (+ promote), teachers, guardians, staff, users |
| Academics | Classes/sections/subjects/batches/sessions, exams + results (publish = **both** flags), assignments, routines |
| Admissions | Applications, tests, payment verify, open/close |
| Daily | Student attendance (bulk), staff attendance |
| Finance | Fees, fee payments, expenses, budgets, bank rec, ledger (journal/cashbook/bankbook), income statement, balance sheet, cash flow, refunds |
| HR | Leaves, payroll, payslips |
| Documents | Admit cards (batch/print), ID cards, certificates, testimonials, committee, progress reports, seat plans — PDF via theme Dompdf |
| Library | Books, categories, issues, reports |
| Campus | Events calendar, transport, hostels |
| Website CMS | Pages, global labels (`site-ui` merge), news, gallery, announcements, notices, documents, media, contact submissions |
| System | Activity log, visitor logs, backups (create/download/restore, keep 7) |
| Admin | Users, roles, permissions, settings (general / localization / payment / library / about), reports + builder + analytics, import/export, onboarding, help |
| Portals | Student dashboard, guardian dashboard, profile, notification prefs |

Write routes: rate-limit **120/min/user/IP**.

---

## Step 6 — REST API (`schoolease/v1`)

Port `/api/v1` groups. Envelope:

```json
{ "success": true, "message": "", "data": {}, "meta": { "pagination": { "current_page": 1, "per_page": 15, "total": 0, "last_page": 1 } } }
```

- Unwrap `{data: …}` resource wrappers so `data` is the payload.
- **Never rewrap** `*/webhook/*` or `*/callback/*`.
- Public result lookup throttled.
- Payments initiate/status behind auth + caps.
- Careers apply exists on Laravel API (optional in theme).

Webhook paths (raw JSON, HMAC + idempotency):

- `POST /wp-json/schoolease/v1/payments/webhook/{bkash|nagad|rocket}`
- `POST /wp-json/schoolease/v1/payments/callback/{gateway}`
- Refund webhooks same pattern.

---

## Step 7 — Payments & refunds (current product)

Gateways (runtime rows in `se_payment_gateways`, env fallbacks only — no secrets in code):

| Code | Type |
|---|---|
| `bkash` | Online MFS |
| `nagad` | Online MFS |
| `rocket` | Online MFS |
| `cash` / `bank_transfer` / `cheque` | Offline |

Theme classes: `GatewayAdapterInterface` + factory + three adapters (same responsibilities as `app/Services/Payment/`). Offline instructions from settings.

Refunds: amount ≤ remaining, concurrency-safe, webhook HMAC, `idempotent: true` on replay.

Currency default **BDT**.

---

## Step 8 — i18n (no Polylang)

Copy the Laravel approach:

```php
function schoolease_t(string $key, ?string $locale = null): string
{
    $locale = $locale ?? schoolease_locale(); // cookie/session: public vs dashboard
    $tree = schoolease_merged_frontend($locale); // lang file deep-merged with CMS `site-ui`
    return data_get($tree, $key, $key);
}
```

- Locales: `en`, `bn` only.
- Separate public vs dashboard locale (Laravel uses `locale` vs `dashboard_locale`).
- BN content: store `*_bn`; **prune identical/empty BN leaves** so they fall back to EN / lang file (`WebsiteContent` behavior).

---

## Step 9 — Notifications, SMS, PDF, cron

| Laravel | Theme |
|---|---|
| Database / mail / SMS / push channels | `inc/class-notifications.php` + mail / SMS / push drivers |
| `SMS_DRIVER=log` default | Log driver in theme; Twilio optional via Composer |
| Dompdf marksheet, admit card, ID, certificate, receipt | `inc/class-pdf.php` + HTML templates in theme |
| `backup:database` daily 02:00 | WP-Cron `schoolease_backup` → `wp-content/uploads/schoolease-backups`, keep 7 |
| `queue:monitor-failed` every 5 min | WP-Cron + `error_log` / Slack webhook option |
| Recurring payments command (unscheduled) | Optional WP-Cron, off by default |

Uploads: MIME-check; block `.php`; serve via download helper, not raw execution.

---

## Step 10 — Security (current production bar)

Port these — they are already signed off on Laravel:

- `APP_DEBUG` equivalent: `WP_DEBUG` **false** in production.
- HTTPS, `SESSION_SECURE_COOKIE` → `secure` auth cookies.
- `TRUSTED_PROXIES` CIDRs, never `*` in production.
- Security headers middleware → `send_headers`.
- No real gateway keys in theme PHP; options UI masks secrets.
- Dashboard writes throttled; public forms 12/min; result lookup throttled.
- Capability checks on every mutation (Laravel dashboard still mixed; **do not copy that gap** — enforce caps in the theme).
- Nonces on all forms; `esc_html` / `esc_attr` / `wp_kses` / `$wpdb->prepare`.

---

## Step 11 — Demo accounts (theme seeder)

Skip in production unless `SCHOOLEASE_ALLOW_DEMO_DATA` is true (same idea as `ALLOW_DEMO_DATA`).

| Role | Email | Password |
|---|---|---|
| admin | `admin@school.com` | from installer / env (never default `password` in production) |
| admin | `principal@school.com` | `principal123` |
| teacher | `teacher.john@school.com` | `teach1234` |
| teacher | `teacher.sarah@school.com` | `teach5678` |
| accountant | `accountant@school.com` | `accountant123` |
| librarian | `librarian@school.com` | `librarian123` |
| teacher | `teacher1@school.com` … | `password` |
| student | `student1@school.com` … | `password` |
| parent | `parent1@school.com` … | `password` |

Create WP users **and** assign `se_*` roles + `update_user_meta(…, 'se_role_id', …)`. Do not mass-assign privileged role fields from request data.

---

## Step 12 — Assets

Tailwind **v4** (current app uses `@tailwindcss/vite`). Theme `package.json`:

```json
{
  "scripts": {
    "dev": "npx @tailwindcss/cli -i ./assets/css/tailwind.css -o ./assets/css/app.css --watch",
    "build": "npx @tailwindcss/cli -i ./assets/css/tailwind.css -o ./assets/css/app.css --minify"
  }
}
```

Enqueue `app.css` / `app.js` in `wp_enqueue_scripts`. Nav JS must use `matchMedia('(min-width: 1367px)')`.

PWA: `manifest.json` rewrite + optional service worker **in the theme** (no PWA plugin).

---

## Step 13 — Rewrites

```php
add_rewrite_rule('^dashboard(/.*)?$', 'index.php?se_dashboard=1&se_path=$matches[1]', 'top');
add_rewrite_rule('^results/?$', 'index.php?se_page=results', 'top');
add_rewrite_rule('^admissions/apply/?$', 'index.php?se_page=admissions-apply', 'top');
add_rewrite_rule('^student/login/?$', 'index.php?se_page=student-login', 'top');
add_rewrite_rule('^guardian/login/?$', 'index.php?se_page=guardian-login', 'top');
add_rewrite_rule('^portal/?$', 'index.php?se_page=portal', 'top');
add_rewrite_rule('^locale/(en|bn)/?$', 'index.php?se_locale=$matches[1]', 'top');
```

Flush on theme switch. `template_include` maps `se_page` / `se_dashboard` to files under `page-templates/` and `dashboard/`.

---

## Step 14 — Laravel → WordPress data import (optional)

WP-CLI command **in the theme** (`inc/class-cli.php`, load if `WP_CLI`):

```
wp schoolease import-laravel --db=/path/to/database.sqlite
```

Map tables 1:1 into `se_*`. Users → `wp_users` + roles. Settings → `se_website_settings`. Do not import `.env` secrets into the repo.

---

## Step 15 — Local workflow

```bash
wp core download && wp config create --dbname=schoolease --dbuser=root --dbpass=root
wp db create
wp core install --url=http://localhost:8080 --title="SchoolEase" \
  --admin_user=admin --admin_password='ChangeMe!2026$Tr0ng' --admin_email=admin@school.com

# Copy this theme into wp-content/themes/schoolease
cd wp-content/themes/schoolease
composer install --no-dev --optimize-autoloader
npm install && npm run build
wp theme activate schoolease
wp rewrite flush
# Demo (never on production):
wp eval 'schoolease_seed_demo();'   # or a theme CLI: wp schoolease seed-demo
```

Production: `WP_DEBUG=false`, HTTPS, real mail, real gateway creds in options (masked), strong admin password, trusted proxies, backups verified.

---

## Implementation prompt (theme only)

> Build a **WordPress theme named SchoolEase** that is a full school management system. **Do not create a plugin.** All PHP lives in the theme (`functions.php` + `inc/`).
>
> **Source of truth:** the Laravel 12 app SchoolEase — Blade + Tailwind v4, EN/BN via `site_ui()`, custom dashboard, Sanctum API envelope `{success,message,data,meta}`, payments bKash/Nagad/Rocket + offline + refunds with HMAC/idempotency, admissions, exams (`batch_id` + `academic_session_id` + `section_id`, `obtained_marks`, publish only when boolean **and** status), student `class_id`+`batch_id`+`roll_number`, CMS form editors (no JSON), public nav breakpoint 1367px, no public theme toggle, roles admin/teacher/student/parent/accountant/librarian.
>
> **Data:** custom `wp_se_*` tables for operations; CPTs only for news/events/gallery/testimonials. Extra fields use **native WordPress meta** (`post_meta` / `user_meta` / `term_meta`) with `se_` keys — no ACF, CMB2, or extra meta tables. Installer on `after_switch_theme` + versioned `admin_init` upgrade. Composer `dompdf` in the theme.
>
> **UI:** public pages matching Laravel `routes/web.php`; front-end `/dashboard/*` matching sidebar groups (not wp-admin). REST `schoolease/v1` with the same envelope; never wrap webhooks.
>
> **i18n:** custom `schoolease_t()` + `languages/en|bn/frontend.php` merged with CMS `site-ui` — no Polylang.
>
> **Security:** caps on every write, nonces, prepared SQL, throttles (dashboard 120/min writes, public forms 12/min), security headers, no secrets in PHP files.
>
> Follow WordPress PHP coding standards. PHP 8.2+.

---

## Checklist

- [ ] Theme scaffold only (no `wp-content/plugins/…`)
- [ ] Installer + db version upgrades; do not drop tables on theme switch
- [ ] Custom tables matching current Laravel schema (exam/result/student columns correct)
- [ ] CPTs limited to content; extras via native `post_meta` / `user_meta` / `term_meta` (`se_` prefix, no ACF)
- [ ] Roles: admin, teacher, student, parent, accountant, librarian
- [ ] Public templates + 1367px nav + EN/BN `schoolease_t()`
- [ ] Front-end dashboard groups as in current sidebar
- [ ] REST envelope + raw webhooks
- [ ] Gateways bkash/nagad/rocket + offline + refunds
- [ ] CMS forms (no JSON editor); bilingual prune/fallback
- [ ] PDF (marksheet, admit, ID, certificate, receipt)
- [ ] WP-Cron backup (keep 7) + failed-job monitor
- [ ] Demo seeder guarded in production
- [ ] Tailwind v4 build; no public theme toggle
- [ ] Security headers, throttles, masked payment secrets
- [ ] Optional Laravel DB import CLI
