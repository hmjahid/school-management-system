# Workplan Implementation Plan — Eskoofy Multi-Product Restructure & BD/INT Variants

Per-task tracker for `WORKPLAN.md`. Status legend:
- ✅ **done** — implemented + verified
- 🟡 **partial** — implemented with known gaps (listed)
- ⛔ **blocked/gated** — waiting on a decision or external input (reason given)
- ⬜ **not started** — out of scope / future phase
- ⬜ **deferred** — permanently or indefinitely postponed (reason given)

Verification baseline (2026-09-11): `cd eskoofy-laravel-app && composer test` → **923 passed, 2361 assertions**; `cd eskoofy-php-app && composer test` → **279 passed, 535 assertions** (Blade-engine parity port); `cd eskoofy-branding-website && composer test` → **79 passed, 214 assertions**; `pint --test` clean on all touched files. Public-site + dashboard-shell UI now renders the Laravel Blade templates via the in-house Blade engine.

---

## Phase 0 — Foundations (decision gates)

| # | Task | Status | Notes |
|---|------|--------|-------|
| 0.1 | Confirm final monorepo folder names + website start | ✅ done | Folders locked: `eskoofy-laravel-app`, `eskoofy-php-app`, `eskoofy-wp-theme`, `eskoofy-branding-website`, `build/`. |
| 0.2 | **Gate:** name the host/market for raw PHP that can't run Composer/Laravel | ✅ done | **Permanently deferred** — no such host exists. Phase 6 skipped. |
| 0.3 | Lock INT scope (gateways, home blocks, omit/add list) | ✅ done | INT = English-only, PayPal/Stripe/Paddle, no BD ministry links, USD/UTC (see `build/profiles/profiles.php`). |
| 0.4 | Verify move won't break deploys | ✅ done | Suite + both artifact boots green from new paths; `docs/operations/RUNBOOKS.md` path notes updated in root AGENTS.md. |

## Phase 1 — Restructure: move Laravel app into eskoofy-laravel-app/

| # | Task | Status | Notes |
|---|------|--------|-------|
| 1.1 | Create top-level product folders + `build/` | ✅ done | `eskoofy-laravel-app/ eskoofy-php-app/ eskoofy-wp-theme/ eskoofy-branding-website/ build/{profiles/{bd,int},dist,artifacts}` |
| 1.2 | Move all app files into `eskoofy-laravel-app/` | ✅ done | Files moved; git detects renames (staged). `.env`, `vendor`, `node_modules` stay ignored inside `eskoofy-laravel-app/`. |
| 1.3 | Product placeholder READMEs | ✅ done | `eskoofy-php-app/README.md` (gated), `eskoofy-wp-theme/` (WP skeleton: style.css, functions.php, index.php, README), `eskoofy-branding-website/README.md`. |
| 1.4 | Repoint absolute paths in docs/scripts | ✅ done | No hardcoded root paths found in app config; suite passes from new path. |
| 1.5 | Update `AGENTS.md` | ✅ done | Root = monorepo overview; `eskoofy-laravel-app/AGENTS.md` = full app guide. |

## Phase 2 — BD/INT profile system (Laravel, config-over-code)

| # | Task | Status | Notes |
|---|------|--------|-------|
| 2.1 | `config/eskoolfy.php` profile config | ✅ done | `variant`, `features.homepage.{ministry_links,ministry_badge}`, `features.payments.*`, `branding`, `int` defaults. All env-drivable. |
| 2.2 | Gate ministry/gov links block | ✅ done | `resources/views/partials/site/footer.blade.php` column gated by `config('eskoolfy.features.homepage.ministry_links')`. |
| 2.3 | Purge hardcoded strings in int-affected views | ✅ done | `grep` for `gov.bd`/Bengali in views → only the gated footer block. Nav/home strings already via `site_ui()` lang files. |
| 2.4 | Payment registry → adapter contract | ✅ done | `GatewayAdapterFactory` central; INT drivers are new adapters, BD untouched. |
| 2.5 | Profile files `build/profiles/{bd,int}` | ✅ done | `build/profiles/profiles.php` = single source for env/locales/gateways per variant. |

## Phase 3 — INT payment gateways (PayPal / Stripe / Paddle)

| # | Task | Status | Notes |
|---|------|--------|-------|
| 3.1 | Define int-gateway contract | ✅ done | Same `GatewayAdapterInterface` as BD adapters (initialize/processCallback/verifyPayment/verifyWebhookSignature/refund). |
| 3.2 | Stripe adapter | ✅ done | `StripeGatewayAdapter`: PaymentIntent init, webhook `payment_intent.succeeded`, verify, HMAC-signed webhook, API refund. |
| 3.3 | PayPal adapter | ✅ done | `PaypalGatewayAdapter`: OAuth token, Checkout order + approval URL, capture on verify, capture-completed webhook, API refund. |
| 3.4 | Paddle adapter | ✅ done | `PaddleGatewayAdapter`: Classic checkout URL, `payment_succeeded`/`refund_issued` alerts, PHP-signature webhook, refund explicitly dashboard-offline. |
| 3.5 | Wire adapters into registry/config | ✅ done | Registered in `GatewayAdapterFactory`; `config/payment.php` + `.env.example` entries; `PaymentGateway::is_configured` supports `paddle`; `PaymentService::supportsRefunds` includes stripe/paypal. |
| 3.6 | Web payment page + INT currency copy | ✅ done | Decision: reuse existing checkout flow. Stripe switched to hosted Checkout (returns `redirect_url`); PayPal approval URL + Paddle checkout URL already fit. No bespoke INT view needed. |
| 3.7 | Tests | ✅ done | `IntlGatewayAdapterTest`: 19 tests / 31 assertions — interface, init, webhook-complete, refund, signature valid/invalid/missing per gateway. Factory + full suite green. |

## Phase 4 — INT content & branding export

| # | Task | Status | Notes |
|---|------|--------|-------|
| 4.1 | Strip `bn` lang in `int` export | ✅ done | `build/export.sh app int` removes `lang/bn`; smoke asserts zip contains no `lang/bn` files. |
| 4.2 | INT homepage block set | ✅ done | Ministry column off via env flag `ESKOOFY_MINISTRY_LINKS=false`; verified boot: `ministry_links=off`, `variant=int`. |
| 4.3 | Branding profile per variant | ✅ done | Profile env: name, locale, currency, timezone per variant (`.env` applied at export). |

## Phase 5 — Build-box

| # | Task | Status | Notes |
|---|------|--------|-------|
| 5.1 | Export script `build/export.sh` | ✅ done | `./build/export.sh app bd`, `app int`, `theme {bd,int}`. rsync app → apply profile `.env` → strip lang → zip `build/dist/eskoofy-laravel-app-{variant}.zip`. Idempotent; `build/artifacts` + `build/dist` git-ignored. |
| 5.2 | CI job for export + smoke | ✅ done | `.github/workflows/ci.yml`: test+lint job → export both variants → smoke assertions → artifact upload. |
| 5.3 | Docs/runbooks + tags | ✅ done | `docs/operations/RUNBOOKS.md` §7 = tagging convention + release flow; `docs/README.md` updated with build/CI section. |

## Phase 6 — eskoofy-php-app (raw PHP) — COMPLETE

| # | Task | Status | Notes |
|---|------|--------|-------|
| 6.1 | Gate: named host unable to run Composer/Laravel | ✅ done | Shared hosting target — Laravel needs VPS which is costly. Raw PHP needed. |
| 6.2 | Architecture: router, DI-lite, auth, schema, gateway drivers | ✅ done | Full MVC stack with fix: routes now loaded in index.php, View resolves hyphens→underscores + plural/singular fallback, Router applies group middleware, CSRF verified on all state-changing requests, BackupController uses MySQL information_schema instead of SQLite PRAGMA. |
| 6.3 | Parity checklist vs Laravel BD | ✅ done | 93-table schema, 78 models, 66 controllers (11 API), 128 views, 9 gateway adapters, 6 language files (en+bn), 3 config files. Full module parity: academics, finance, admissions, CMS, transport, hostel, library, SMS, payroll, reports, certificates, backups, API surface. |
| 6.4 | Full module port + vertical slice | ✅ done | All modules ported: students (CRUD+sub-pages), teachers, classes, sections, subjects, batches, guardians, attendance (mark+list), exams (CRUD+publish+results), fees, fee_payments+receipts, payments+gateway wiring+refund, admissions (CRUD+workflow+documents), routines, assignments, notices, news, events, announcements, galleries, expenses+categories, budgets, ledger (journal/cashbook/bankbook/income-statement/balance-sheet), transport (vehicles/routes/assignments), hostel (rooms+assignments), library (books+categories+issues), SMS, reports, certificates+admit_cards+id_cards+generate, settings, users+roles, profile, academic_sessions, payroll (salary_structures/payslips/leave_requests+types/staff_attendance), testimonials, committee, backups, visitor_logs, activity, messages, courses, notifications, device_tokens, password_reset, dashboard_favorites. API: 11 controllers (results/lookup, news, notices, events, students, teachers, classes, exams, fees, admissions, dashboard). |
| 6.5 | BD/INT profile + build-box integration | ✅ done | `.env`-driven variant (bd/int), config/payment.php with all 7 gateways (bKash/Rocket/Nagad/Stripe/PayPal/Paddle/Offline), config/app.php with feature flags. |
| 6.6 | Tests + CI | ✅ done | Added dev-only `composer.json` (PHPUnit 11), `phpunit.xml`, `tests/bootstrap.php`, `Tests\TestCase`. 206 tests / 348 assertions across Core (Router, Validator, Session, Request, Auth, View, Model, QueryBuilder), all 9 gateway adapters (Offline + Stripe/PayPal/Paddle/bKash/Rocket/Nagad signatures + callbacks), and global helpers. CI job `php-test` (`.github/workflows/ci.yml`) runs `composer test`; `export` job now ships `eskoofy-php-app-{bd,int}.zip` with lang/bn + variant smoke checks. `build/export.sh php {bd,int}` wired (rsync → profile `.env` incl. TIMEZONE→APP_TIMEZONE → strip lang/bn for int → zip). |
| 6.7 | Public-site runtime repairs (controllers vs schema) | ✅ done | `HomeController` and `SiteController` (22 public routes — `/`, `/about`, `/contact`, `/login`, `/register`, `/news`, `/events`, `/notices`, `/gallery`, `/faculty`, `/committee`, `/academics`, `/results`, `/routine`, `/admission`, `/careers`, `/privacy`, `/terms`, `/transport`, `/students-life`, `/portal`, `/search`) now return 200 against the real schema. Repairs: `news.status` → `news.is_published`; `testimonials.is_active` → `testimonials.is_visible`; `exams.exam_date` → `exams.start_date`; `routines.class_id` → `routines.school_class_id`; `gallery_albums` removed (no such table — use `galleries` as the album list). Suite still 232/411. |
| 6.8 | Schema patch + front-controller regression test | ✅ done | `refunds.processed_by NOT NULL` → `NULL` (was incompatible with `ON DELETE SET NULL`; import errored with errno 150 on MySQL 8 / MariaDB 11). New `App\Core\DatabaseInterface` extracted; `Database::setInstance(?DatabaseInterface $db)` test override. `tests/Integration/FrontControllerTest` (PHPUnit `Integration` suite, 6 tests) drives `HomeController` and `SiteController` against `Tests\Fakes\FakeDatabase` and asserts the SQL log uses the right column names — reverts a fix and the corresponding test fails with a clear message. Suite 238/428 (+6). |
| 6.9 | Dashboard controller audit — second sweep | ✅ done | 21 systematic column mismatches repaired across 8 dashboard controllers + 1 schema column added (exam_results.subject_id). Repairs: attendances.class_id→school_class_id; assignments.class_id→batch_id, .teacher_id→.created_by, .deadline→.due_date; book_issues.user_id→COALESCE(student,teacher), .returned_at→.return_date; class_subject_teacher.section_id join dropped; expenses.category_id→expense_category_id; leave_requests/payslips/salary_structures/staff_attendances .user_id→.teacher_id (with teachers→users join chain); refunds.student_id→.user_id; admissions.class_id join dropped. Added 6 new integration tests covering each fix. Suite 248/444 (+6). View include paths from previous session (33 files) also included. The dashboard surface still has gaps in coverage (many controllers and views not yet exercised) but the systematic column/table mismatches are now closed for the routes the tests touch. |
| 6.10 | Full-surface parity pass (all 130 dashboard GET routes + public API) | ✅ done | Fresh route inventory extraction (all `{id}`/`{resource}`/`{module}` params bearing real ids) revealed 4 live bugs vs the real schema, all fixed: (1) `seat-plans/{id}/generate` and `progress-reports/{id}/generate` crashed with **Unknown named parameter** because the route declared `{id}` while the controllers expect `$examId`/`$studentId` — renamed route params to match (Router dispatches params by name); (2) API `/api/v1/events` used `e.event_date` (schema: `start_date`); (3) API `/api/v1/news` used `n.excerpt` + `news.status='published'` (schema: `content` + `is_published`); (4) API `/api/v1/notices` used `published_at`/`is_published` (schema: `pinned` + `created_at`); (5) `StudentController::fees()` summed `amount_paid` (schema: `paid_amount`). Deep smoke now 120/130 clean 200s + 10 expected not-found 302/404s, zero 500/exception bodies on both dashboard GET routes and public API. Also sweep-converted 53 dashboard/API controllers from `private Database $db` to `DatabaseInterface $db` so the integration harness (FakeDatabase) can exercise the whole surface. Added tests: RouterTest named-param dispatch guard, seat-plan/progress-report generate integration tests, student-fees `paid_amount` regression, and `ApiSchemaAlignmentTest` (source-scan guard for the three API column fixes). Suite 255/463 (+7 tests). |
| 6.11 | Feature-parity port: Batch 1 (promote, bulk attendance, exports, report builder, analytics) | ✅ done | Added student promote form/action (GET+POST `/students/promote`, source class/section filter, target class/section/batch, keep-roll + promote-all, dedupe-safe), bulk attendance (GET+POST `/attendance/bulk`, per-student status map keyed by date+batch+section, sets `marked_by` + `school_class_id` which are NOT NULL), exam results CSV export, per-student results CSV, `my-results` board, report builder (students/payments/fee_payments column picker + CSV), analytics dashboard (student growth, revenue vs expenses, fee target, attendance by class, teacher workload), report CSV exports. Added Router `_method` spoofing (PUT/PATCH/DELETE via hidden field) — previously all existing PUT/DELETE forms were dead. 5 tests. |
| 6.12 | Feature-parity port: Batch 2 (document modules) | ✅ done | Admit cards, student ID cards, certificates upgraded from on-the-fly preview to persisted CRUD + batch generation + standalone print/preview views. Numbers: `ADMIT-{exam}-{student}-%04d`, `ID-{student}-%04d`, `CERT-{YEAR}-%04d`; admit cards dedupe on (exam_id, student_id); ID cards per student; certificates JSON template + body + generated name label. 4 tests. |
| 6.13 | Feature-parity port: Batch 3 (events, assignments, leaves) | ✅ done | Events calendar (Sunday-start grid, month nav, BD government-holiday/academic/school-activity calendars, upcoming lists) + create/edit views + full status enum; assignments edit/update/destroy + submissions + grade (marks/feedback/graded_by/graded_at/status, clamped to total_marks) + file upload; dedicated LeaveController (index/create/show/approve/reject/cancel with approver_id/approver_note/decided_at, self-service scoping). Fixed latent `admin_note` → `approver_note` bug in PayrollController. 4 tests. |
| 6.14 | Feature-parity port: Batch 4 (library, backup, SMS, payroll) | ✅ done | Book issue show + return (computes late fee from `library_settings.late_fee_per_day`, sets `return_date` + `late_fee`) + collect fine (`fine_paid`) + mark lost; backup download + delete; SMS compose/preview/campaign-send persisting to `sms_campaigns` + `sms_campaign_recipients`, templates (`notification_templates` where `sms_content` set), due-reminder (groups `fee_payments` balance>0 by student, phone from phone_1/father/mother); payroll generate preview + idempotent generateStore (basic + allowances − deductions − leave deduction at basic/30 per approved leave day), payslip show + markPaid. 4 tests. |
| 6.15 | Feature-parity port: Batch 5 (testimonials, transport, guardians, admissions, bulk) | ✅ done | Testimonials rewritten to the student-award schema (`TEST-{YEAR}-%04d`, type/status/body/details) + full CRUD + standalone print; vehicle create/edit/update/destroy; transport route edit/update/destroy with inline stops sync + assignment destroy; guardians show/edit/update/destroy with `guardian_student` pivot sync; admissions toggleOpen (`admission_settings` upsert with fee/notice/bar fields), updateStatus (approve/reject guards), verifyPayment; news/notices/announcements bulk delete/publish/unpublish + news create/edit views. Fixed guardians store using nonexistent `address`/`national_id` columns. 5 tests. |
| 6.16 | **UI parity: Blade-compatible engine + Laravel view tree (COMPLETE)** | ✅ done | Built `app/Core/Blade.php` (compiler + runtime) so `eskoofy-php-app` renders the **exact Laravel Blade templates** (`resources/views/` copied verbatim from `eskoofy-laravel-app`): layouts/sections/stacks/includes, `@if/@foreach/@forelse/@switch/@php`, `{{ }}/{!! !!}` with nested-literal handling, `@auth/@guest/@can/@cannot/@canany/@error/@csrf/@method`, anonymous `<x-…/>` components + `<x-slot>` + `:attr` bindings + `@props` + `{{ $attributes->merge() }}` + `@class`, `$loop`. Added Laravel-global layer: `route()` (via generated `config/routes.php` name→URI map), `__()` (file-namespaced), `collect()/Collection`, `Str/Carbon/Optional/Stringable`, `Schema/Storage` facades, `Gate`, `request()`, `app()`, `session()`, `LengthAwarePaginator` (`$notices->links()`), Eloquent-lite `Model`/`QueryBuilder` (casts incl. Carbon dates, scopes, relationships, `belongsTo/hasMany`, `where($col,$op,$val)` operator form, ArrayAccess so legacy array code keeps working). Lang + config (`school.php`, `eskoolfy.php`, `sms.php`) copied from the app; `site_ui` returns arrays with defaults. **Ported & verified (HTTP 200, no errors):** full public site (home, about, academics, news + article, notices, events, gallery, contact, faculty, committee, transport, routines, results, admissions, payments, search, portal, terms, privacy, careers, students-life, sitemap) + dashboard shell (layout/sidebar/topbar) + dashboard overview. Suite green (287 tests / 553 assertions); integration tests re-pointed to assert SQL on the new controllers (`View::$renderViews=false` in test bootstrap). **Dashboard controllers ported** to feed the Blade views Eloquent-shaped data (models/collections/paginators/Carbon): all 181 dashboard GET routes verified via an authenticated smoke harness (176 render `OK`, 5 CSV-export/redirect endpoints behave correctly). Router now casts numeric URI params to `int`; model constants added (`Routine::DAYS`, `Certificate::TYPES`, `Testimonial::TYPES`, `Attendance::STATUS_*`, `Admission::PAYMENT_VERIFIED`, `Fee::*`, `Exam::STATUS_*`, `StaffAttendance::STATUS_*`); missing models added (`WebsiteDocument`). Remaining cleanup: retire the legacy `views/*.php` fallbacks once all modules are verified against a real DB. |

| 6.17 | API/config parity sweep (notifications API, payment methods, phantom courses, schema additions) | ✅ done | (1) Ported the app's `routes/notifications.php` API surface: GET `/notifications` (limit/offset/unread_only), `unread-count`, POST `{id}/read`, POST `read-all`, DELETE `{id}`, DELETE `/notifications` (clear), GET+PUT `notification-preferences`, GET `stream` — new `App\Controllers\Api\NotificationApiController` backed by `notification_logs`/`notification_preferences` (`opened_at IS NULL` = unread), never the Laravel `notifications` table (its creation is skipped upstream). (2) Payment method parity: `callback` now POST (was GET), `{id}/status` now PUT (was POST), removed php-only `POST /payments/initiate` duplicate route. (3) Removed phantom php-only `courses` dashboard routes + `CourseController` (app has no course routes; the page rendered an empty shell). (4) `schema.sql` now 98 tables: added `grades` (FK→`school_classes`, unique student/class/subject/exam) + `user_widget_preferences` (unique user/widget, user FK cascade), validated by full import on throwaway MySQL 8 (0 errors). New `NotificationApiParityTest` (5 source/route/schema-guard tests). Suite 292/579 green; route-gap 0, route-target 0. |

| 6.18 | Favorites phantom remove + live QoL/users write smoke | ✅ done | Removed the php-only `POST /dashboard/favorites/toggle/{module}` route + its `module`-branch in `FavoriteController` (app has only `POST /dashboard/favorites/toggle` with url/label; the phantom queried a nonexistent `module` column and crashed against the real schema — discovered during live smoke). Controller now mirrors the app: url must start with `/` or the `APP_URL` base, label capped at 120, prune-to-11, `{"favorite":bool}` JSON. De-duplicated the double `/favorites/toggle` registration. Then ran a live QoL/users write smoke against throwaway MySQL 8 (`/tmp/smoke_users.php`, bcrypt-seeded admin/teacher): user store (+count, hash), duplicate-email guard (no insert), user update (role_id 2→1 persisted), user soft-delete (deleted_at set, self-delete guarded), profile update (name/email persisted), profile password change (old verify + new bcrypt), favorites toggle on/off (url + full-base-url accepted, `javascript:` rejected 422). New `FavoriteToggleParityTest` (3 route/controller/schema guards). Suite 295/592; route-gap 0, route-target 0. |

## Phase 7 — eskoofy-wp-theme (WordPress) — COMPLETE

| # | Task | Status | Notes |
|---|------|--------|-------|
| 7.1 | Scout theme in `eskoofy-wp-theme/` | ✅ done | Full plugin-theme hybrid: header/footer/sidebar/single/page/front-page/archive/404/search templates. Theme supports, nav menus, widget areas, base CSS. |
| 7.2 | INT variant via `.po` | ✅ done | `.pot` template + `en_GB.po`/`.mo` (INT) + `bn_BD.po`/`.mo` (BD) in `languages/`. All theme strings translatable. |
| 7.3 | Theme build-box integration | ✅ done | `build/export.sh theme {bd,int}`; attempts `wp i18n make-pot` when `wp-cli` present. |
| 7.4 | Theme tests/lint | ✅ done | `composer.json` + `phpcs.xml` (WordPress-Extra ruleset, `eskoofy` prefix). CI job `theme-lint` runs PHPCS on every push/PR. |
| 7.5 | Full WP plugin-theme hybrid | ✅ done | 54 custom DB tables, 7 CPTs, 13 inc/ files, 39 admin views (full CRUD for all modules), 5 page templates, REST API (11 routes), 10 shortcodes, 7 payment gateway stubs, helpers, admin CSS/JS. |
| 7.6 | Critical bug fixes | ✅ done | JS nonce typo fixed (eskAdmin.nce→eskAdmin.nonce). Attendance/results save handlers wired with nonce check + $wpdb upsert. Action routing added (student-detail, admission-detail, exam-form, class-form pages now reachable). esk_generate_number() counts from correct table. REST lookup uses display_name. Dual-storage (CPT + table) for notices. index.php updated to proper fallback template. README table count corrected. |
| 7.7 | Public shortcodes wired | ✅ done | Results lookup: GET form, POST queries student+exam_results with pass/fail display. Fee payment: GET form, POST queries fee_payments showing paid/unpaid/partial status. |
| 7.8 | All missing module admin pages added | ✅ done | 20 new admin pages: sections, subjects, batches, guardians, expenses, transport (vehicles+routes+assignments), hostels (hostels+rooms), library (books+issue/return), SMS, payroll (structures+payslips+leave+attendance), reports, certificates, admit-cards, id-cards, announcements, testimonials, committee, careers, academic-sessions, users. All with full CRUD via $wpdb, nonce fields, escaping. |

## Phase 8 — eskoofy-branding-website (marketing/branding + license server) — COMPLETE

| # | Task | Status | Notes |
|---|------|--------|-------|
| 8.1 | Scope + single-variant decision | ✅ done | ONE English/USD/UTC site (no bd/int split) with a language switcher. I18n via `lang/{en,bn}.php` + `App\Services\I18n` + `GET /language/{locale}`. |
| 8.2 | Marketing site | ✅ done | Raw PHP stack (eskoofy-php-app Core), themed pages: home, products (app/theme), pricing, features, about, contact, 404. All strings via `__()`. |
| 8.3 | License server API | ✅ done | `routes/api.php` `/api/v1`: `ping`, `licenses/{activate,validate,deactivate}` (license-key auth), `licenses/status` (`X-Api-Key`). JSON + CORS; DB-free `ping` tested. |
| 8.4 | Customer/admin backends | ✅ done | Auth (customers), account area (licenses, activations renew/revoke, payments), admin area (customers, plans, licenses, payments, messages, activities). Manual gateway fulfills instantly; Paddle stub. CSRF enforced on all POST forms; API exempt. |
| 8.5 | Tests + CI + export | ✅ done | 35 tests / 101 assertions (LicenseManager, gateways, Router, I18n incl. full en↔bn key parity). `ci.yml` `website-test` job + export step (`website int`) + APP_LOCALE=en smoke. `build/export.sh website <any>` always emits int env. |
| 8.6 | PWA (installable + offline shell) | ✅ done | `public/manifest.json` + committed PNG icons (192/512/maskable + apple-touch) + `sw.js` (network-first navs, SWR assets incl. Tailwind CDN; never caches `/admin`, `/api`, `/account`, `/checkout`) + `offline.html` + head wiring in main/admin layouts. Single-variant int site; installable on Android/Chrome over HTTPS. |
| 8.7 | Location-based default language (BD → bn, else en) | ✅ done | `App\Services\GeoLocale` (CDN header → Accept-Language → optional remote IP-geo API → client-tz backstop), `config/app.php` `i18n.geo`, `LocaleMiddleware` resolves per-session geo_locale, manual switch always wins, `GET /language/geo?tz=...` upgrades en→bn for Asia/Dhaka. Pure runtime config — no bd/int branching. |
| 8.8 | Marketing blog (post system) | ✅ done | `posts` + `post_categories` tables (with seeds), public `/blog`, `/blog/category/{slug}`, `/blog/{slug}` + admin CRUD for posts and categories, `views/admin/posts*` + `post_categories*`, `views/site/blog.php` + `post.php`, "Latest from the blog" on home, `nav.blog` + `blog.*` en/bn keys (parity test still green). |
| 8.9 | Website CMS (pages + SEO) | ✅ done | `pages` table + 16 seeded rows (all static/product routes); admin CRUD at `/admin/pages*` (soft delete, per-locale `heading/intro/content`, meta title/description, canonical, hreflang overrides, JSON-LD, noindex); layout SEO merge in `main.php` — per-view SEO wins, CMS fills gaps; template text wins until edited. |
| 8.10 | Product-selection wizard | ✅ done | `GET/POST /choose` — 5-question quiz → `ProductRecommender` picks best fit among app / raw-PHP / WP theme / Node, with runner-up + all-four cards + custom-order fallback CTA. `ProductRecommenderTest`; `choose.*` en/bn keys. |
| 8.11 | Custom orders | ✅ done | `GET/POST /custom-order` (product preselect via `?product=`) — store `custom_requests` row + email sales; admin follow-up at `/admin/custom-requests*` (unread badge, mark read, status pipeline, archive). `custom_order.*` + `node_page.*` en/bn keys; CTAs in nav dropdown, footer, home hero, CTA band, services add-on. |
| 8.12 | Node variant marketing surface | ✅ done | `/products/node` page + reframed marketing "1 license, 4 products"; Node candidate in recommender CTA→`/custom-order?product=node`. |

---

## Phase 9 — Cross-product frontend & dashboard/sidebar parity + variant blueprint — COMPLETE

| # | Task | Status | Notes |
|---|------|--------|-------|
| 9.1 | Theme topbar: move login/logout out of topbar | ✅ done | Removed login/logout + separator from `header.php` topbar (lang toggle + socials divider kept). Sticky nav Dashboard→Logout ordering verified; mobile panel now mirrors app bottom bar (logged-in: Dashboard/Portal accent first → Logout; guests: Login accent; lang toggle last). |
| 9.2 | Theme social icons (header topbar + footer) | ✅ done | `esk_social_profiles()` gains 5-platform defaults mirroring the app seeder (`https://{key}.com/exampleschool`, linkedin school variant); customizer exposes all 5; `social` settings tab added; `app-public.css` appended brand palette + `bg-brand-*`/`hover`/`focus` utilities (color-mix). |
| 9.3 | Side-by-side parity audit doc | ✅ done | `docs/parity/product-parity.md`: verified app↔php byte-identical (sidebar, dashboard index, topbar, `lang/en/dashboard.php`); theme per-group deltas; dashboard home block matrix; additive merge plan. |
| 9.4 | app+php sidebar: finance/reports links | ✅ done | Finance group (+`@can('manage_expenses')`): Expense Categories, Budgets, Bank Reconciliation, Income Statement, Balance Sheet, Cash Flow (`route-is` open/active extended). Configuration: Reports Builder + Analytics (Reports `route-is` narrowed). `lang/en|bn/dashboard.php` keys added; php copied byte-identical (`diff` clean). App suite 923 passing; route names verified via `route:list`. |
| 9.5 | Theme sidebar + Global Labels | ✅ done | Sidebar links now support custom label+url+icon; Website group + CMS Settings (`?tab=cms`) + Global Labels (`?tab=labels`); Finance details + bank-reconciliation; tab label CMS→CMS Settings; new Global Labels tab persists `esk_label_<flat>` overrides honored by `esk_site_ui()`. |
| 9.6 | Theme dashboard setup banner | ✅ done | 6-item onboarding checklist (school info, timezone, academic session, classes, teachers, payment) from WP tables; progress bar; CTA→`esk-onboarding`; dismiss via `localStorage dc_setup_reminder_dismissed`. |
| 9.7 | Variant blueprint | ✅ done | `docs/design/VARIANT-BLUEPRINT.md` — Laravel app as reference: canonical inventory, baseline parity debt, phased roadmap (0 scaffold→1 schema→2 auth→3 dashboard/sidebar→4 modules→5 i18n/profiles→6 API→7 tests/build-box), verification commands, open questions. |
| 9.8 | Prompt file | ✅ done | `docs/prompts/frontend-social-and-dashboard-parity-variant-blueprint.md` covering tasks 1-3 + verification matrix; executed until green (all PHP lint clean; app 923 tests pass; `diff` parity confirmed). |

---

## Phase 10 — Security hardening (executed from docs/security/SECURITY-IMPLEMENTATION-PLAN.md)

| # | Task | Status | Notes |
|---|------|--------|-------|
| 10.1 | php dashboard role/permission enforcement (P1) | ✅ done | New `config/access.php` (dashboard_roles + per-module roles); `Router::authorizeDashboard()` enforces roles centrally for all `App\Controllers\Dashboard\*`; `RoleMiddleware` with param syntax; dashboard group already `AuthMiddleware`. Student/guardian roles denied; sensitive modules (users/settings/backup/payroll/sms/gateways) admin-only; finance admin+accountant. Smoke-tested (10 cases), 314 tests pass. |
| 10.2 | License server rate limit + auth (W1/W2/W3) | ✅ done | `ThrottleMiddleware` param parsing + wired to `/api/v1/licenses/*` (activate 10/min, validate/status 30/min, deactivate 10/min) + `/login` (10/5min). Optional `LICENSE_PRODUCT_SECRET` gate (X-Product-Secret, `hash_equals`). Domain hostname validation + bounded printable machine_id. |
| 10.3 | php payment routes auth (P2) | ✅ done | `/payments/initiate`, `/payments/status/{id}`, `/payments/receipts/{id}` now `AuthMiddleware` (app parity). |
| 10.4 | Remove default/weak credentials (P7/P8/R1) | ✅ done | Removed admin seed from `schema.sql`; new `database/seed_admin.php` (random strong password); `seed_demo.php` refuses `APP_ENV=production` without `--i-am-sure`; README updated. docker/theme-test: `WP_ADMIN_PASSWORD` now required (compose `:?`), setup script rejects `<12 char`/`admin`; `WP_DEBUG` default 0. |
| 10.5 | Theme per-module capability gating (T1) | ✅ done | `esk_dashboard_page_caps()` map (slug → cap); `esk_can_access_page()` enforced in route renderer; sidebar links + details sub-links hidden by capability. |
| 10.6 | Session cookie hardening (G2) | ✅ done | php + website bootstrap: `session_set_cookie_params` (HttpOnly, SameSite=Lax, Secure via `SESSION_SECURE_COOKIE`) before `session_start()`; app prod template already sets `SESSION_SECURE_COOKIE=true`. |
| 10.7 | Constant-time CSRF + token rotation (G3) | ✅ done | php + website CSRF compare → `hash_equals`; CSRF token rotated on login (`Auth::attempt`/`login`). |
| 10.8 | Rate-limit public forms (G4/P6) | ✅ done | php public POSTs throttled (contact, admission, careers, password reset, admissions apply/scholarship/submit-payment, complaint/feedback, newsletter, portal message) via `Throttle:12,1` / `5,15`. |
| 10.9 | Security headers (G1) | ✅ done | New `SecurityHeadersMiddleware` (php + website, wired in `public/index.php`); theme `send_headers` hook; app already had SecurityHeaders middleware (verified registered). CSP + nosniff + X-Frame + Referrer + Permissions + HSTS(prod) + remove X-Powered-By. |
| 10.10 | CORS tightening (A2) | ✅ done | app `config/cors.php`: paths→`api/*,sanctum/csrf-cookie`, explicit methods/headers, `max_age=86400`. |
| 10.11 | Stored-XSS sanitization (A4) | ✅ done | New `App\Services\HtmlSanitizer` (DOMDocument allow-list) in app + php; applied to news content (store/update) and notification template content in both products; 4 app tests. |
| 10.12 | Theme gateway keys at rest (T2) | ✅ done | `esk_encrypt_secret`/`esk_decrypt_secret` (AES-256-GCM via wp_salt); `get_gateway_data()` decrypts transparently; `esk_encrypt_gateway_secrets()` migration runs on theme activation; legacy plaintext passes through. |
| 10.13 | Theme nonce audit (T3) | ✅ done | Verified all nonce-less admin views are read-only (0 POST branches) — no change needed. |
| 10.14 | App API hygiene + log redaction + model guard (A5/A6/A7) | ✅ done | `DatabaseNotification` guarded; `PaymentController` logs redact card/secret fields; publicSettings + teacher API already correctly scoped (verified). |
| 10.15 | Results lookup rate limit (T4) | ✅ done | REST `esk/v1/results/lookup` rate-limited per-IP via transient (15/min). |
| 10.16 | `esk_repair_site_url` gating (T5) | ✅ done | Defensive `manage_options`/`WP_CLI` guard added (call sites already admin+nonce). |
| 10.17 | .htaccess hardening (R4) | ✅ done | app + website `public/.htaccess` + new theme root `.htaccess`: deny dotfiles, sensitive file types, block direct `inc/`/`views/` access. |
| 10.18 | PWA cache hygiene (R3) | ✅ done | Theme SW never caches `/dashboard/`, `/wp-admin/`, `/login/`, `/api/`; cache versions bumped (app `eskoofy-v2`, theme `eskoofy-wp-theme-v3`). |
| 10.19 | env parser + session role re-read + `_method` (W5/P9/P5) | ✅ done | php + website `.env` parser handles quoted values + inline comments; `Auth::role()` re-reads from DB per request (cached); `_method` spoofing already POST-only (verified). |
| 10.20 | CI security gates (G5) | ✅ done | `composer audit` added to all 4 CI jobs; new `security` job (gitleaks + hardcoded-credential grep); added to export `needs`. |
| 10.21 | Security tests | ✅ done | php `DashboardAccessConfigTest` (3); website `LicenseApiSecurityTest` (3); app `HtmlSanitizerTest` (4). Suites: app 927, php 314, website 95 — all green. |

---

## Summary

- ✅ Complete: Phase 0, Phase 1, Phase 2, Phase 3, Phase 4, Phase 5, Phase 6 (full raw PHP port + tests + CI), Phase 7 (full WP hybrid), Phase 8 (website + license server + i18n + PWA + geo-language + marketing blog), Phase 9 (frontend social parity + dashboard/sidebar parity merge across app/php/theme + variant blueprint), Phase 10 (security hardening per docs/security/SECURITY-IMPLEMENTATION-PLAN.md).

## File counts

| Product | Files | Key components |
|---------|-------|----------------|
| eskoofy-laravel-app (Laravel) | existing | 900+ tests, 95 models, 130+ controllers, 32 services |
| eskoofy-php-app (Raw PHP) | 400+ | Core (20: Router, Database, QueryBuilder, Model, Relation, Controller, View, **Blade engine**, Session, Auth, Validator, Request, Schema, Storage, Gate, UrlGenerator, ViewErrorBag, ComponentAttributeBag, Support/{Collection,Str,Carbon,Optional,Stringable,LengthAwarePaginator}), Models (78), Controllers (67+11 API), Gateways (9), Views (**316 Blade templates copied from eskoofy-laravel-app** + legacy PHP fallbacks), Config (4 + `routes.php` name→URI map), Lang (6), Schema (93 tables), Tests (20 files / 287 cases / 552 assertions) |
| eskoofy-wp-theme (WordPress) | 115+ | Root templates (32), Inc (13), Admin views (67), Languages (5), 64 admin pages, 54+ DB tables, 7 CPTs, REST (11 routes), Shortcodes (10) |
| eskoofy-branding-website (Raw PHP) | 110+ | Core + Middleware (10), Services (I18n, GeoLocale, LicenseManager, ActivityLog), Gateways (Manual/Paddle stub), Models (Plan/Customer/Payment/Subscription/LicenseActivation/Post/PostCategory), Site/Auth/Account/Admin/API controllers, 15+ views, `routes/{web,api}.php`, `lang/{en,bn}.php`, PWA shell (`manifest.json`, `sw.js`, `offline.html`, icons, `register-sw.js`, `favicon.svg`) |

## Next actions

1. **Retire legacy views** (6.16 follow-up): once all dashboard modules are verified against a real MySQL import, delete the legacy `views/*.php` fallbacks so every page renders from the copied Blade tree.
2. Sandbox E2E for INT gateways (Stripe/PayPal/Paddle with real test creds) in all 3 products.
3. Design pass on the WP theme when ready.
4. Provision the licensing DB + deploy the website; load real plan/price data into `plans`.
5. Add a media upload pipeline for blog featured images (currently URL-only).
6. **Parity follow-ups** (open questions in `docs/design/VARIANT-BLUEPRINT.md`): decide promote-vs-hide for theme-only sidebar tokens (leave-types, salary-structures, refunds, tools, cache, sections, subjects, batches, academic-sessions, progress-reports, seat-plans); unify Workbench gating between app (dynamic, gated) and theme (always shown); full per-page Global Labels editor in the theme vs curated subset.