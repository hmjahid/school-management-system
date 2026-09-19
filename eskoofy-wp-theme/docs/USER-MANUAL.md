# Eskoofy Theme — User Manual

> Product: `eskoofy-wp-theme` (WordPress theme, version 0.2.0, text domain `eskoofy`) · Audience: school administrators, teachers, accountants, librarians + site visitors · Docs language: English

Eskoofy Theme is the **WordPress product** in the Eskoofy monorepo — a **plugin-theme hybrid**
that ships a public marketing site **and** a full app-style management dashboard at
**`/dashboard/`**, mirroring `eskoofy-laravel-app`. Its two sibling products share the same UI and
feature set on different stacks: `eskoofy-laravel-app` (Laravel) and `eskoofy-php-app` (raw PHP). BD/INT
are build-time profiles (translations + branding), never forks.

- Setup, install and deployment: [`SETUP-GUIDE.md`](./SETUP-GUIDE.md)
- Demo accounts: [`../../docs/operations/DEMO-CREDENTIALS.md`](../../docs/operations/DEMO-CREDENTIALS.md)
- Theme test environment: [`../../docker/theme-test/README.md`](../../docker/theme-test/README.md)

> **Key concept:** the management dashboard is the **frontend `/dashboard/`**, not the
> WordPress admin. The legacy wp-admin menu is disabled unless the
> `ESK_ENABLE_WPADMIN_MENU` constant is enabled.

---

## 1. Signing in

Eskoofy Theme provides its **own login system at `/login/`**, separate from `wp-login.php`:

| What | URL | Who |
|---|---|---|
| System login | `/login/` | School staff (teachers, accountant, librarian) and administrators; lands on `/dashboard/` |
| WordPress admin | `/wp-admin/` (`wp-login.php`) | WordPress platform administrators only |

The `/login/` form (`template-login.php`, plus the `[eskoofy_login_form]` shortcode) accepts a
**username or email** and signs the user in via `wp_signon()`. Anonymous visitors hitting any
`/dashboard/…` page are redirected to `/login/?redirect_to=…`.

### Roles and capabilities

The theme registers three extra WordPress roles (each with `read`):

| Role | Default capability group |
|---|---|
| `teacher` | Academic access (students, attendance, exams, results, notices) |
| `accountant` | Finance access (fees, payments, expenses, reports) |
| `librarian` | Library access (books, issues, dues, reports) |

Capabilities are keyed `dashboard, students, teachers, guardians, academics, admissions,
attendance, finance, hr, documents, library, events, transport, hostels, sms, content, users,
settings, reports` and are stored in the **`esk_role_caps`** option, editable on the
**Roles & Permissions** page (`/dashboard/roles/`). WordPress administrators always pass every
check. A dashboard page is allowed when the user holds **any** of the capabilities its slug
requires; slugs without an explicit entry require the base `dashboard` capability.

---

## 2. Management dashboard at `/dashboard/`

Routing lives in `inc/front-dashboard.php` (rewrite rules + `template_redirect`); the shell
(sidebar, topbar, command palette, badges, icons) lives in `inc/admin-shell.php`; each page is a
template under `views/admin/` (85 files). Admin-only groups — System, Website, Administration,
Configuration and SMS — are hidden for non-administrators.

| Sidebar group | Modules |
|---|---|
| **Main** | Dashboard, Search, Onboarding, Help |
| **Communications** | Messages, SMS, Announcements, Notices |
| **Academic** | Students (+ add), Teachers (+ add), Guardians, Classes, Sections, Subjects, Batches, Sessions, Attendance (+ mark), Staff attendance, Exams, Results, Routines, Assignments, Progress reports, Seat plans, Fees, Fee payments, Expenses, Expense categories, Ledger, Budgets, Payroll, Payslips, Salary structures, Leave types, Leave requests, Library, Library reports, Certificates, Admit cards, ID cards |
| **Admissions** | Admissions, Admission detail |
| **Facilities** | Transport, Hostels |
| **Website CMS** | CMS, News, Gallery, Events (+ calendar), Documents, Media, Testimonials, Committee, Careers, Career applications, Contact submissions |
| **Reports** | Reports, Reports builder, Analytics, Income statement, Balance sheet, Cash flow, Bank reconciliation |
| **System** | Activity, Visitor logs, Notifications, Notification templates, Notification preferences, Refunds, Backup, Cache, Tools |
| **Administration** | Users, Staff directory, Roles |
| **Configuration** | Settings |
| **Help** | Help, Profile, Software |

The topbar provides a live clock, website link, `Ctrl+K` command palette, language toggle,
favourite pin, help modal, dark mode, notifications dropdown, PWA install button and user
dropdown (logout → `/login/`).

---

## 3. Module-by-module guide

### People
- **Students** `/dashboard/students/` (+ `/dashboard/student-add/`, `/dashboard/student-detail/`)
- **Teachers** `/dashboard/teachers/` (+ `/dashboard/teacher-add/`)
- **Guardians** `/dashboard/guardians/`
- **Staff directory** `/dashboard/staff-directory/`
- **Users** `/dashboard/users/`, **Roles** `/dashboard/roles/`

### Academics
- **Classes** `/dashboard/classes/`, **Sections** `/dashboard/sections/`, **Subjects**
  `/dashboard/subjects/`, **Batches** `/dashboard/batches/`, **Sessions**
  `/dashboard/academic-sessions/`.
- **Attendance** `/dashboard/attendance/` (+ `/dashboard/attendance-mark/`); staff attendance at
  `/dashboard/staff-attendance/`.
- **Exams** `/dashboard/exams/`, **Results** `/dashboard/results/`, **Routines**
  `/dashboard/routines/`, **Assignments** `/dashboard/assignments/`.
- **Progress reports** `/dashboard/progress-reports/`, **Seat plans** `/dashboard/seat-plans/`.

### Admissions — `/dashboard/admissions/`
Review applications and details; the public apply flow uses the `Admission Page` /
`Admissions Apply` templates and shortcodes.

### Finance
- **Fees** `/dashboard/fees/`, **Fee payments** `/dashboard/fee-payments/`,
  **Refunds** `/dashboard/refunds/`.
- **Expenses** `/dashboard/expenses/`, **Expense categories** `/dashboard/expense-categories/`,
  **Budgets** `/dashboard/budgets/`, **Ledger** `/dashboard/ledger/`,
  **Bank reconciliation** `/dashboard/bank-reconciliation/`.
- **Statements:** `/dashboard/income-statement/`, `/dashboard/balance-sheet/`,
  `/dashboard/cash-flow/`.
- Gateways (`inc/payment-gateways.php`): **bKash, Rocket, Nagad, Stripe, PayPal, Paddle,
  Offline**. Callback endpoint: `esk/v1/payments/callback/{gateway}`.

### HR & payroll
- **Payroll** `/dashboard/payroll/`, **Payslips** `/dashboard/payslips/`,
  **Salary structures** `/dashboard/salary-structures/`, **Leave types**
  `/dashboard/leave-types/`, **Leave requests** `/dashboard/leave-requests/`.

### Facilities
- **Transport** `/dashboard/transport/` (vehicles, routes, stops, assignments) and
  **Hostels** `/dashboard/hostels/` (hostels, rooms, assignments).
- **Library** `/dashboard/library/` + **Library reports** `/dashboard/library-reports/`.

### Communications
- **Messages** `/dashboard/messages/`, **SMS** `/dashboard/sms/` (drivers: Twilio, Vonage,
  log), **Notifications** `/dashboard/notifications/` (+ templates/preferences),
  **Announcements** `/dashboard/announcements/`, **Notices** `/dashboard/notices/`.

### Documents
- **Certificates** `/dashboard/certificates/`, **Admit cards** `/dashboard/admit-cards/`,
  **ID cards** `/dashboard/id-cards/`.

### Website CMS
- `/dashboard/cms/`, **News**, **Gallery**, **Events** (+ calendar), **Documents**, **Media**,
  **Testimonials**, **Committee**, **Careers**, **Career applications**,
  **Contact submissions**.

### System & configuration
- **Activity** `/dashboard/activity/`, **Visitor logs** `/dashboard/visitor-logs/`,
  **Backup** `/dashboard/backup/`, **Cache** `/dashboard/cache/`, **Tools**
  `/dashboard/tools/` (re-run demo install / URL repair), **Settings** `/dashboard/settings/`.

---

## 4. Public website

The public site uses the standard WordPress template hierarchy plus page templates:

| Template | Purpose |
|---|---|
| `front-page.php` | Homepage (hero, statistics, sections, sliders) |
| `page.php`, `single.php`, `archive.php`, `search.php`, `404.php` | Standard pages/posts/archives/search |
| `template-about.php`, `template-academics.php`, `template-faculty.php`, `template-committee.php`, `template-students.php`, `template-transport.php` | Static school pages |
| `template-admission.php`, `template-admissions.php` | Admission information & apply |
| `template-news.php`, `template-notices.php`, `template-events.php`, `template-gallery.php` | Content listings |
| `template-results.php`, `template-fees.php` | Result lookup & fee payment |
| `template-portal.php`, `template-login.php` | Student/parent portal & system login |
| `template-contact.php`, `template-search.php`, `template-privacy.php`, `template-terms.php` | Contact, search, legal |

**Custom post types** (`inc/custom-post-types.php`): `esk_news`, `esk_events`, `esk_notices`,
`esk_galleries`, `esk_testimonials`, `esk_committee` (admin-only, `public=false`) and
`esk_careers` (public, with archive at `/careers/`).

**Shortcodes** (`inc/shortcodes.php`): `[eskoofy_results_lookup]`, `[eskoofy_admission_form]`,
`[eskoofy_fees_payment]`, `[eskoofy_student_profile]`, `[eskoofy_class_schedule]`,
`[eskoofy_news_list]`, `[eskoofy_events_list]`, `[eskoofy_gallery]`, `[eskoofy_contact_form]`,
`[eskoofy_payment_gateway]`, `[eskoofy_bright_students]`, `[eskoofy_login_form]`.

**Widget areas**: `sidebar-1` (Main Sidebar), `footer-1/2/3`, `home-hero`, `home-features`.
**Menus**: *Primary* and *Footer*. **PWA**: dynamic `/manifest.json`, `/sw.js`, `/offline`.

Localization: `.pot` + `bn_BD` / `en_GB` translations in `languages/`; language toggle sets
the `esk_locale` option.

---

## 5. Data model & REST API

- Custom tables are created on theme activation by `inc/database.php` with prefix `esk_*`
  (students, teachers, guardians, classes, subjects, exams, results, attendance, fees,
  payments, refunds, expenses, ledger, budgets, payroll, leaves, admissions, transport,
  hostels, library, CMS and system tables) and tracked by the `esk_db_version` option.
- **REST API** namespace `esk/v1` (`inc/rest-api.php`): `GET/POST` for `students`, `teachers`,
  `classes`, `exams`, `results`, `fees`, `payments`, `admissions`, `notices`, `news`;
  a rate-limited `GET /results/lookup` (published results only); `GET /notifications` +
  `POST /notifications/mark-all`; and the payment callback route.
- **AJAX** (`inc/admin-ajax.php`): student search, mark attendance, save results, students by
  class, toggle favourite — nonce `esk_ajax_nonce`; REST uses the `wp_rest` nonce. They are
  **different nonces** — do not mix them.

---

## 6. Known behaviours

- Only **4 slugs** without an explicit icon (`esk-sms`, `esk-notices`, `esk-announcements`,
  `esk-messages`) fall back to the generic gear icon — cosmetic and intentional.
- **5 slugs** (`esk-search`, `esk-notifications`, `esk-profile`, `esk-help`, `esk-software`)
  have no explicit capability entry and therefore require the base `dashboard` capability —
  intentional.
- The public `style.css` global reset is scoped with
  `:where(body:not(.esk-admin-shell)) *` so it does not affect the dashboard shell.
- The dashboard CSS (`inc/app-dashboard.css`) is compiled Tailwind — regenerate it from the
  app's Tailwind build if the dashboard markup gains new utility classes.

---

## 7. Verification status

Verified on 2026-09-19 against this working tree:

| Check | Command / method | Result |
|---|---|---|
| PHP syntax | `php -l` across `functions.php`, `inc/*.php`, `template-*.php` | ✅ all pass |
| Dashboard slug integrity | static audit of `inc/front-dashboard.php` + `inc/admin-shell.php` | ✅ **81 slugs**: all callbacks exist, all titles present, all referenced views exist (85 view files) |
| Capability coverage | `esk_dashboard_page_caps()` | ✅ 76/81 explicit + 5 base-cap fallback (intentional) |
| Icon coverage | `esk_admin_shell_icon()` | ✅ 77/81 explicit + 4 generic fallback (intentional) |
| Code style | `composer run lint` (PHPCS, WordPress-Extra) | ⚠️ **pre-existing backlog**: 3190 errors + 1437 warnings across 147 files (see note) |

> **Quality-gate note:** `eskoofy-wp-theme/AGENTS.md` states "PHPCS is not a gating check (the
> committed codebase already has violations)", yet the monorepo CI workflow runs
> `./vendor/bin/phpcs` in a `theme-lint` job. The codebase currently fails that job. Most errors
> are the `WordPress.NamingConventions.PrefixAllGlobals` sniff — `phpcs.xml` declares the only
> allowed prefix as `eskoofy` while the code uses `esk_` throughout. This is a **pre-existing
> policy/config mismatch**, not a functional defect, and was deliberately not mass-fixed in
> this pass (it would be a sweeping, unconfirmed change). Fixing it is a separate decision
> (either add `esk` to the allowed prefixes and run `phpcbf`, or make the CI job non-blocking).

---

## Related docs

- [`SETUP-GUIDE.md`](./SETUP-GUIDE.md) — install, activate, configure, deploy
- [`../README.md`](../README.md) — product overview
- [`../AGENTS.md`](../AGENTS.md) — conventions & commands
- [`../../docker/theme-test/README.md`](../../docker/theme-test/README.md) — local WP test env
- [`../../docs/operations/DEMO-CREDENTIALS.md`](../../docs/operations/DEMO-CREDENTIALS.md)
- [`../../docs/guides/DEVELOPMENT.md`](../../docs/guides/DEVELOPMENT.md)
