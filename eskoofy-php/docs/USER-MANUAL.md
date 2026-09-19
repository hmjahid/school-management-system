# Eskoofy PHP — User Manual

> Product: `eskoofy-php` (raw PHP 8.2+, no framework at runtime) · Audience: school staff, administrators, accountants, librarians, teachers, students & guardians · Docs language: English

Eskoofy PHP is the **raw-PHP product** in the Eskoofy monorepo: a feature-equivalent port of
the Laravel app built to run on ordinary shared hosting where Composer/Laravel/VPS is not
available. It ships the **same public website, the same `/dashboard` admin modules, the same
student/guardian portals and the same JSON API** as `eskoofy-app`, rendering the **byte-identical
Blade templates** through a built-in Blade-compatible engine (`app/Core/Blade.php`). BD/INT are
config/`.env` profiles of one codebase.

- Setup, install and deployment: [`SETUP-GUIDE.md`](./SETUP-GUIDE.md)
- Demo accounts: [`../../docs/operations/DEMO-CREDENTIALS.md`](../../docs/operations/DEMO-CREDENTIALS.md)
- Run locally (all products): [`../../docs/guides/DEVELOPMENT.md`](../../docs/guides/DEVELOPMENT.md)

> **Status note:** the repo-root `AGENTS.md`/`WORKPLAN.md` still describe Phase 6 as "deferred"
> pending a hosting target, but the code in this folder is **complete and test-green** (see
> §8). Treat it as a shippable product; the only outstanding release item is a real-host
> deployment trial. There is no separate `/admin` panel — administration lives under
> `/dashboard` (e.g. `/dashboard/users`, `/dashboard/settings`).

---

## 1. Signing in

| Who | URL | Notes |
|---|---|---|
| Staff / admin (super admin, admin, teacher, accountant, librarian) | `/login` | Email + password |
| Students | `/student/login` | Email + password, or date of birth as a fallback password |
| Guardians / parents | `/guardian/login` | Email + password |

Students and guardians are **not** dashboard roles — they are redirected to their portal
(`/student/dashboard`, `/guardian/dashboard`). Password reset is available from the login
screen.

### Roles and dashboard access

`users.role` is a MySQL `ENUM('student','admin','teacher','guardian','super_admin','accountant','librarian')`.
The dashboard is gated by `config/access.php`, enforced in `app/Core/Router.php::authorizeDashboard()`:

| Role | Access |
|---|---|
| `super_admin`, `admin` | Everything, including users, roles, permissions, settings, backups, payroll, gateways |
| `teacher` | Academic modules (classes, students, attendance, exams, results, notices) |
| `accountant` | Finance (fees, payments, expenses, budgets, ledger, bank reconciliation, statements, refunds, report builder) |
| `librarian` | Library (books, categories, issues, reports) |
| `student`, `guardian` | Portal only — denied the `/dashboard` |

`dashboard_roles = ['super_admin','admin','teacher','accountant','librarian']`; per-module
overrides are listed in `config/access.php`.

---

## 2. Dashboard tour

The dashboard shell (`layouts.dashboard` + `partials/dashboard/{sidebar,topbar}`) mirrors the
Laravel app exactly, including dark mode, locale switch, favourites, live clock and the
command palette. Sidebar groups: **Main, Messages, Academic (People ›, Academics ›, Daily ›,
Finance ›, HR ›, Documents ›, Library ›), Admissions, System, Website CMS, Administration,
Configuration, Help.**

---

## 3. Module-by-module guide

Routes are all under `/dashboard` (grouped under one `AuthMiddleware` group in
`routes/web.php`).

### 3.1 People

| Module | Routes |
|---|---|
| Students | `/students`, `/students/create`, `/students/promote`, `/students/{id}`, `/students/{id}/{attendance,results,fees}` |
| Teachers / Staff | `/teachers`, `/staff` |
| Guardians / Parents | `/guardians`, `/parents` |
| Users / Roles / Permissions | `/users`, `/roles`, `/permissions` |
| Profile | `/profile` |

### 3.2 Academics

| Module | Routes |
|---|---|
| Classes / Sections / Subjects / Batches | `/classes`, `/sections`, `/subjects`, `/batches` |
| Attendance | `/attendance`, `/attendance/mark`, `/attendance/bulk` |
| Exams & Results | `/exams`, `/exams/{id}/{publish,unpublish,visibility,results}`, `/my-results`, marksheet |
| Routines / Assignments | `/routines`, `/assignments` (+ submissions/grade) |

**Exams flow:** create the exam → enter marks → *publish* results → set visibility so
students/guardians can see them. Admit cards, seat plans, ID cards, certificates and
progress reports live under Documents (`/certificates`, `/admit-cards`, `/id-cards`,
`/student-id-cards`).

### 3.3 Admissions — `/admissions`

Review applications, **verify payment**, run admission **tests**, mark **documents**, and
change status (approve/reject/enroll). The public apply flow is at `/admissions/apply` with
status lookup at `/admissions/status`.

### 3.4 Finance

| Module | Routes |
|---|---|
| Fees | `/fees` |
| Fee payments | `/fee-payments` |
| Online payments | `/payments`, `/payment-gateways` |
| Expenses / Categories | `/expenses`, `/expense-categories` |
| Budgets | `/budgets` |
| Ledger | `/ledger` (journal, cashbook, bankbook, income statement, balance sheet, cash flow) |
| Bank reconciliation | `/bank-reconciliation` |
| Refunds | via refunds routes |

Gateways: bKash / Rocket / Nagad (BD) and Stripe / PayPal / Paddle (INT) plus offline
bank transfer; all seven adapters live in `app/Gateways/`. Test/live mode is per gateway.

### 3.5 Library — `/library`

Books (`/library/books`), categories (`/library/categories`), issues/returns/fines
(`/library/issues`), and reports (`/library/reports`, `/library-reports`).

### 3.6 Transport & Hostel

- Transport: `/vehicles`, `/transport-routes`, `/transport-assignments`, and
  `/transport/{vehicles,routes,assignments}`.
- Hostel: `/hostels`, `/hostel-rooms`, `/hostel-assignments` (+ per-hostel sub-routes).

### 3.7 HR & payroll

| Module | Routes |
|---|---|
| Salary structures | `/salary-structures` |
| Payslips / payroll | `/payslips`, `/payroll/{generate,payslips,structures}` |
| Leaves | `/leave-requests`, `/leaves`, `/leave-types` |
| Staff attendance | `/staff-attendance` |

### 3.8 Communications — `/sms`, `/notifications`

- **SMS:** `/sms` (+ compose, preview, send, campaign, templates, due-reminder).
- **Notifications:** `/notifications` (+ templates, preferences, mark-read, stream).
- Drivers: `log`, Twilio, Nexmo, Vonage, TextLocal, Africa's Talking (`config/sms.php`).

### 3.9 Reports & system

- Reports: `/reports/{students,fees,attendance,exams}`, `/reports/builder`, `/reports/export/{type}`, `/analytics`.
- Documents: `/certificates`, `/admit-cards`, `/id-cards`, `/student-id-cards`.
- System: `/activity`, `/visitor-logs`, `/backups`, `/bulk`, `/documents`, `/media`,
  `/communications`, `/search`, `/onboarding`, `/help`, `/favorites/toggle`.

### 3.10 Website CMS — `/cms`

Edit CMS pages at `/cms`, `/cms/pages`, `/cms/edit/{page}`. News, notices, events,
galleries, announcements, careers, testimonials, committee and contact submissions each have
their own module.

### 3.11 Settings — `/settings/*`

Website, school, locale, theme, payment, mail, library, global labels, about, CMS, general
and localization. Users/roles/permissions are under the Administration group.

---

## 4. Public website & portals

Public routes (from `routes/web.php`): `/` (home), `/about`, `/academics`, `/students-life`,
`/faculty`, `/transport`, `/committee`, `/news` (+ `/news/{slug}`), `/notices`, `/events`,
`/gallery`, `/contact`, `/results`, `/routine`, `/admission` (+ `/admissions/apply`,
`/admissions/status`, receipts, approval letters), `/payments`, `/portal`, `/search`,
`/careers`, `/terms`, `/privacy`, plus `/sitemap.xml`, `/robots.txt`, `/manifest.json`.

Portals: `/student/login` → `/student/dashboard`; `/guardian/login` → `/guardian/dashboard`;
portal sub-pages `/portal/admission`, `/portal/progress`, `/portal/message`, `/portal/register`.

Language: English/Bengali via `lang/{en,bn}/`, driven by `config/app.php` locale + variant.

---

## 5. JSON API (overview)

- **Base path:** `/api/v1` (`routes/api.php`). Errors/JSON are forced by
  `ForceJsonMiddleware`; state-changing browser requests are CSRF-protected, API paths are
  exempt.
- **Auth:** Bearer tokens via `PersonalAccessToken` (`ApiTokenMiddleware`); `?api_token=` is
  also accepted. **Login is `POST /api/v1/auth/login`** (note: the Laravel app uses
  `POST /api/v1/login`).
- **Public endpoints:** `/api/v1` (info), `/results/lookup`, `/news*`, `/notices`, `/events*`,
  `/academics/*`, `/website-content/*`, `/website/gallery*`, `/careers*`, `/terms`,
  `/privacy`, `/sitemap`, `/home`, `/payments/gateways`, `/website-settings`.
- **Protected CRUD:** `/students`, `/teachers`, `/classes`, `/exams`, `/fees`, `/admissions`,
  `/payments`, `/refunds`, `/payment-gateways`, `/notifications`.
- **Admin:** `/api/v1/admin/*` (dashboard, analytics, activity, quick actions, CMS,
  widgets, website settings).
- **Webhooks:** `POST /api/webhooks/{gateway}/refund`,
  `POST /api/v1/payments/{webhook,callback}/{gateway}`.

Route parity rule: this file equals the Laravel app's `route:list`, and `config/routes.php`
is the generated route-name → URI map used by `route()` in the copied Blade templates.

---

## 6. Known behaviours & quirks

Documented intentionally — these are **not** defects to "fix" in a normal change:

- **No user is seeded by `database/schema.sql`.** A fresh database has zero users until you
  run `php database/seed_admin.php` (and optionally `seed_demo.php`). See the setup guide.
- **Duplicate route registrations** exist for parity with the app (e.g. `/staff-attendance`,
  `/backups` vs `/backup`, `/reports/income-statement`). Recognised, harmless aliases.
- **`RoleMiddleware` is defined but unused** — dashboard role gating runs inside
  `Router::authorizeDashboard()` via `config/access.php`.
- **Legacy `views/*.php` fallbacks remain** (and `archive/dashboard/*`) as a safety net for
  any view without a Blade copy; they are retired once a real-DB pass confirms coverage. The
  active UI is `resources/views/**` (Blade).
- **Blade caching:** templates compile to `storage/framework/views/`. After changing the
  Blade *compiler* (not a template), clear that directory and re-touch the templates — opcache
  can otherwise serve stale bytecode.

---

## 7. Verification status

Verified on 2026-09-19 against this working tree:

| Check | Command | Result |
|---|---|---|
| Test suite | `cd eskoofy-php && composer test` | ✅ **314 tests passed** (807 assertions) |
| Route→controller→method integrity | static scan of `routes/web.php` + `routes/api.php` | ✅ **743 targets, 0 broken** |
| Dashboard auth gating | `config/access.php` + `Router::authorizeDashboard()` | ✅ students/guardians denied; staff roles per module |

The test suite includes integration coverage for the front controller, API parity, schema
alignment, notifications and favourites. Note: some in-folder docs carry stale counts
(`README.md` says "287 tests / 553 assertions", "93 tables", "42 dashboard modules");
the observed numbers are 314 tests / 807 assertions, 99 tables and 100 controllers.

---

## Related docs

- [`SETUP-GUIDE.md`](./SETUP-GUIDE.md) — install, configure, deploy
- [`../README.md`](../README.md) — product overview
- [`../AGENTS.md`](../AGENTS.md) — conventions & commands
- [`../../docs/parity/product-parity.md`](../../docs/parity/product-parity.md) — app↔php parity
- [`../../docs/operations/DEMO-CREDENTIALS.md`](../../docs/operations/DEMO-CREDENTIALS.md)
- [`../../docs/guides/DEVELOPMENT.md`](../../docs/guides/DEVELOPMENT.md)
