# QA Parity Audit — eskoofy-php & eskoofy-theme vs eskoofy-app (Laravel)

**Audit date:** 2026-09-16
**Auditor:** Senior QA (10yrs) — automated inspection of file/route/schema inventory with
code-level spot inspection of high-risk areas.
**Scope:** Source-of-truth `eskoofy-app` (Laravel 12) vs the raw-PHP port
(`eskoofy-php`) and the WordPress theme port (`eskoofy-theme`).
**Method:** mechanical diffing (MD5 byte-parity, route tree extraction, schema table
diff) + targeted code review. No runtime E2E was executed (no DB lifted), so route-*count*
facts are exact; *behavioral parity* verdicts are code-inspection based.

**Severity scale used below**
- **P0** — broken feature / data-affecting defect that ships to users.
- **P1** — feature present in app but missing or reduced in a variant (parity gap).
- **P2** — naming/UX/schema drift, incomplete API surface, dead code.
- **P3** — hygiene / debt, no user-visible impact.

---

## Executive summary

| Variant | Overall parity | Verdict |
|---|---|---|
| **eskoofy-php** (raw PHP) | **High** — near 1:1 web UI + full DB schema | P1: API/admin surface reduced, push-notifications + scheduler missing, 5 model classes absent, ~34 legacy views orphaned |
| **eskoofy-theme** (WP) | **Medium** — page inventory ~80% matched, but "dashboard-lite" | P1: finance/RBAC/ledger/budgets/admission-test/notifications subsystems absent; several modules are add+list only |

**Headline positives (parity that holds):**
- All **317 app Blade views exist byte-identical in eskoofy-php** (MD5, 0 differences).
- php DB schema mirrors the app: **98 tables vs 101** (the 3 gap tables are Laravel framework
  tables: `cache`-family / framework internals; all product tables present).
- Theme public-site templates match the app `site.*` route set, and the 7 recently rebuilt
  templates were validated feature-complete.
- App has **zero** views missing from php (app ⊆ php).

---

## Part A — eskoofy-php vs eskoofy-app

### A1. Views (byte parity) — ✅ PASS with over-delivery

| Measure | Count |
|---|---|
| App Blade views (`resources/views/**`) | 317 |
| php copies byte-identical (MD5) | **317 / 317** (0 content diffs) |
| php-only views (no app counterpart) | 71 |

**Finding [P2]:** ~35 of the 71 php-only views are route-backed extra pages (php exposes
more than the app: `attendance/mark`, `certificates/generate`, `exams/*`, `id-cards/*`,
`ledger/balance_sheet|cash_flow|income_statement`, `library_reports/*`, `payments/index`,
`messages/*` …). The remaining ~34 look like **legacy snake_case duplicates**
(`dashboard/admit_cards/*`, `dashboard/fee_payments/receipt`, `dashboard/academic_sessions/index`,
`dashboard/courses/index`, …) that pre-date the kebab-case app views and are largely
unreachable. One counter-example verified route-backed: `dashboard.library.issue_show`.

**Recommendation:** prune or archive the dead snake_case views (run a view-unreferenced
scan = grep `view('dashboard.<x>` across `app/Controllers/**`).

### A2. Routes

| Area | App | php | Verdict |
|---|---|---|---|
| Dashboard web routes | 352 | 350 defs (in `/dashboard` group) | ~1:1; php consolidates (`books`→`library`, `exam-results`→`exams`, `school-classes`→`classes`) |
| API `v1` | 149 routes / ~115 paths | 81 defs / ~57 unique paths | **P1 — 60% of API surface missing** |
| Public site | ~72 site.* routes | superset (+careers archive/single, +a few) | ✅ |

**P1 API gaps (app has, php doesn't):**
- Admin/CMS: `v1/admin/**` (26 paths — cms blocks/menus/pages/media/settings, analytics,
  notifications templates/preferences, quick-actions, widgets, website-content/settings)
- Notifications: `v1/notifications`, `/stream`, `/read-all`, preferences
- Auth: `v1/auth/login|logout|register|refresh-token` (php API is effectively public-only;
  app API is Sanctum-secured — **security parity concern** [P1])
- Payments/refunds admin: `v1/payments`, `/callbacks`, `v1/payment-gateways`,
  `v1/refunds/{process,cancel,statistics}`
- Fees/payments, admissions (documents/enroll), teacher portal, academics
  (curriculum/programs), careers, search, sitemap/terms/privacy/home, students detail
  (attendance/results/fees)

php-only API extras: `classes`, `exams`, `notices`, `results/lookup`, `dashboard`,
`admission-filters`, `import/export admissions` (some are name variants of app paths).

### A3. Models & schema

| Measure | Count |
|---|---|
| App models | 86 |
| php models | 81 |
| **Missing in php** | 5: `ClassModel`, `Course`, `DatabaseNotification`, `Grade`, `UserWidgetPreference` |
| php `schema.sql` tables | 98 |

- `Course` / `Grade` / `UserWidgetPreference` **tables exist** in php schema but have **no
  php model class** → the php port has no course / grade (marksheet grading) features
  surfaced, even though the app ships them. **[P1]**
- `DatabaseNotification` has no table in either schema (app uses in-memory/stream
  notifications) — not a regression.
- `ClassModel` is the app's legacy alias class; canonical is `SchoolClass` (present in php).
  Safe P3.

### A4. Services

App `app/Services/**` (17 entries) vs php `app/Services/**` (2: `AdmissionSubmitter`, `Sms/`).

| App service | php status |
|---|---|
| `PaymentService`, `RefundService`, `LedgerService`, `RecurringPaymentService` | Inline in `Api/PaymentController` + helpers (verified full gateway/webhook/idempotency logic present) |
| `MailSettingsService`, `NotificationService`/`Delivery`, `Sms` | Partially inline |
| **`Push/` (FCM) + `LogPushNotificationService`** | **MISSING — push notifications absent in php** [P1] |
| `SetupChecklistService`, `DashboardService`, `LogSmsService` | Missing (minor) [P3] |

**P1:** No scheduled/cron subsystem in php for recurring payments, scheduled
notifications, or due-date jobs (app has the scheduler). This is the biggest behavioral gap
behind the API surface.

### A5. Auth & security

- php: session-only auth + `Core/Gate` (Spatie-style role gate, admin bypass), payment
  webhook signature verification present. **[P1]** No token auth (`RefreshToken` model
  exists but unused for API; app API rides Sanctum-style tokens) — API endpoints in php
  are effectively public/limited, which narrows exposure but breaks parity with app's
  guarded API.

---

## Part B — eskoofy-theme (WP) vs eskoofy-app

### B1. Admin pages

App dashboard routes = 352 across ~80 `Dashboard*` Web controllers. Theme maps **70 slugs**
in `inc/front-dashboard.php` → 68 registered wp-admin pages (`inc/admin-pages.php`, gated
by `ESK_ENABLE_WPADMIN_MENU`) → 74 templates in `views/admin/**`.

**P1 — app modules with NO theme page:**
`roles`, `permissions`, `ledger` (journal/cashbook/bankbook), `budgets`,
`expense-categories`, `communications`, `staff` (directory), `events/calendar`,
`library/categories`, `admissions` tests management, notification
templates/preferences, `sms` compose/due-reminders (merged into one bulk-sms page),
`dashboard/modules/*` (10 pages) — many consolidated by design.

**P2 — theme pages with no app route (dashboard-lite extras):**
`sections`, `subjects`, `batches`, `academic-sessions`, `refunds`, `leave-types`,
`student-add`/`teacher-add` (naming), `software` (≈ app `about`).

### B2. Sidebar parity (`inc/admin-shell.php` vs app sidebar)

16 groups / 56 items (theme) vs ~20 groups / ~40 items (app). **Missing in theme:**
`communications`, `staff`, `ledger`, `library > categories`, `events > calendar`,
`cms-settings` + `global-labels`, `roles`, `permissions`. Theme ADDs: income-statement /
balance-sheet / cash-flow as their own items (app nests them under ledger/reports) — a UX
drift, not a gap.

### B3. Public site

App `site.*` set ↔ theme `front-page.php` + `template-*.php` + `archive-*/single-*`.
Matched: about, academics, admission, committee, contact, events, faculty, gallery, news,
notices, privacy, results, routine, search, students, terms, transport, portal, login.
Theme extra: careers archive/single. App extra: `sitemap.xml`, guardian-login split.

**Validated (recently rebuilt, feature-complete):** `single-esk_news`,
`archive-esk_news`, `template-admission`, `template-contact`, `archive-esk_events`,
`archive-esk_galleries`, `archive-esk_notices` — all present, use correct
`esk_*` hooks/pagination/lightbox/CSRF. ✅

### B4. Database (`inc/database.php` esk_ tables vs app migrations)

Theme **68 tables** vs app **88 product tables** (101 incl. framework).

**P1 app tables missing in theme:** `budgets`, `chart_of_accounts`, `invoices`,
`recurring_payment_profiles`, `payment_webhook_events`, `admission_tests`,
`event_attendees`, `courses`, `grades`, `notification_logs`, `notification_preferences`,
`notification_templates`, `scheduled_notifications`, `device_tokens` (PWA),
`school_class_subject`, `class_subject_teacher`, `section_teacher`, `guardian_student`,
`roles` (theme uses WP roles — acceptable design, but permission granularity differs),
`website_documents`/`website_media` (theme merges into `documents`/`media`).

Theme-only: `bank_statements`, `seat_plans`, `sms_logs`, `class_subject` (naming drift).

### B5. Feature depth (spot inspection)

| Module | Theme depth | App depth | Verdict |
|---|---|---|---|
| Fees | add-form + list (92 L) | full CRUD + filters/search/print/RBAC | P1 reduced |
| Admissions | list + status filter + View (69 L) | toggle, EN/BN bar, payment verification, tests mgmt | P1 reduced |
| Library | add/list/issue/return | + categories CRUD, reports, fine/status workflows | P1 reduced |
| Finance whole | — | budgets, ledger, invoices, recurring | P1 missing |
| RBAC | WP roles | app roles/permissions matrix | P1 (design) |

Theme renders WP `esk-admin-wrap` shell (faithful copy of app topbar/sidebar/palette/
dark-mode) rather than app's Tailwind data-table chrome — consistent visual language,
different component stack. [P3]

---

## Consolidated findings register

| ID | Variant | Sev | Finding | Evidence |
|---|---|---|---|---|
| P-A1 | php | P2 | ~34 legacy snake_case views likely orphaned | `docs` + `/tmp` inventory; only 1/34 verified referenced |
| P-A2 | php | P1 | ~60 app API paths missing (admin/CMS 26, auth 4, payments/refunds 16, notifications 7, teacher 3, academics/careers/search/website rest) | api.php (57 paths) vs app route:list (115 paths) |
| P-A3 | php | P1 | Push/FCM notifications + scheduler (recurring, scheduled notifs, due-jobs) absent | `app/Services/Push/` missing in php |
| P-A4 | php | P1 | No Course / Grade / UserWidgetPreference model (tables exist in schema.sql) | model diff 86 vs 81 |
| P-A5 | php | P1 | API not token-secured (session-only); app API is Sanctum-guarded | api.php + Core/Gate inspection |
| P-A6 | php | P3 | Services consolidated inline instead of ported as classes (except Sms, AdmissionSubmitter) | Services dirs |
| P-B1 | theme | P1 | Finance subsystem (budgets, ledger, invoices, recurring, chart-of-accounts) absent | front-dashboard/admin-pages + database.php |
| P-B2 | theme | P1 | RBAC roles/permissions absent (WP roles), plus `roles` table not created | admin-shell/database.php |
| P-B3 | theme | P1 | communications, staff directory, expense-categories, events calendar, library categories, admissions tests, notification templates/preferences pages absent | route slug diff |
| P-B4 | theme | P1 | Modules ships reduced depth (fees/admissions/library add+list) | template spot-inspection |
| P-B5 | theme | P2 | Name drifts: guardians↔parents, id-cards↔student-id-cards, software↔about, income/balance/cash-flow as sidebar items | admin-shell |
| P-B6 | theme | P3 | Screen structure differs from app (WP list-table vs Tailwind), PWA device_tokens absent | templates |

---

## Positive confirmations worth preserving

1. View byte-parity app→php: **317/317** — the strongest invariant in the monorepo. Guard it
   with the existing checksum check in CI.
2. php DB schema: **98/101** tables — all product tables ported.
3. Dashboard route counts are ~balanced (352 app / 350 php).
4. Theme public site: `site.*` parity + rebuilt templates validated.
5. php payment webhook signature/idempotency logic verified present (matches app).

---

## Recommended remediation (priority order)

1. **[P1] Theme finance + RBAC** — highest revenue-adjacent parity gap. Add budgets/
   ledger/invoices/recurring + roles/permissions pages (or explicitly carve them from
   scope with owner sign-off).
2. **[P1] php API expansion** — port admin/CMS, notifications, payments/refunds, auth
   (token), teacher-portal endpoints to reach ~parity.
3. **[P1] php scheduler + push** — cron entry point for recurring payments / scheduled
   notifications; FCM push via a ported service.
4. **[P1] php model gaps** — add Course/Grade/UserWidgetPreference models to surface
   grades/courses.
5. **[P2] Dead php views** — prune legacy snake_case views (or archive to
   `eskoofy-php/archive/dashboard/`), then lock parity with an inventory test.
6. **[P2] Theme sidebar/name alignment** — align section keys with app sidebar groups
   (per AGENTS theme-parity rule), resolve guardians/id-cards naming.
7. Every fix ships through `build/propagate/propagate-feature.sh` with the app
   `composer test` gate, php suite, and theme `php -l` / phpcs gate.

---

## Re-audit commands

```bash
# view byte parity (app vs php)
python3 - <<'PY'
import os,hashlib
def m(root):
    o={}
    for dp,_,fs in os.walk(root):
        for f in fs:
            p=os.path.join(dp,f); r=os.path.relpath(p,root)
            o[r]=hashlib.md5(open(p,'rb').read()).hexdigest()
    return o
a=m('eskoofy-app/resources/views'); p=m('eskoofy-php/resources/views')
print('identical',sum(1 for k in a if k in p and a[k]==p[k]),'/',len(a))
print('php-only',len(set(p)-set(a)))
PY
# app route inventory
cd eskoofy-app && php artisan route:list --json > /tmp/routes.json
# theme page slugs
rg -n "'esk-[a-z-]+'" eskoofy-theme/inc/front-dashboard.php
# theme tables
rg -o "CREATE TABLE.*?\`([a-z_]+)\`" eskoofy-theme/inc/database.php
```