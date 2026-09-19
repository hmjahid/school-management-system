# Eskoofy App — User Manual

> Product: `eskoofy-laravel-app` (Laravel 12 school management software) · Audience: school staff, administrators, principals, accountants, librarians, teachers, students & guardians · Docs language: English (Bengali is a product UI language, not a docs language)

Eskoofy App is the **Laravel product** in the Eskoofy monorepo — a complete school
management system with a staff **admin dashboard**, a **public website with CMS**, and
**student & guardian portals**, plus a JSON API under `/api/v1`. Its two sibling products
share the same UI and feature set on different stacks: `eskoofy-php-app` (raw PHP) and
`eskoofy-wp-theme` (WordPress). BD/INT are **build-time variants of one codebase**, not forks.

- Setup, install and deployment: [`SETUP-GUIDE.md`](./SETUP-GUIDE.md)
- Demo accounts: [`../../docs/operations/DEMO-CREDENTIALS.md`](../../docs/operations/DEMO-CREDENTIALS.md)
- Run locally (all products): [`../../docs/guides/DEVELOPMENT.md`](../../docs/guides/DEVELOPMENT.md)

---

## 1. Signing in

Eskoofy App has three login entry points:

| Who | URL | Notes |
|---|---|---|
| Staff / admin (super admin, principal, accountant, librarian, teacher) | `/login` | Email + password |
| Students | `/student/login` | Email + password, or date of birth as a fallback password |
| Guardians / parents | `/guardian/login` | Email + password |

After signing in, staff land on **`/dashboard`**; students and guardians are redirected to
the **portal** (`/portal`, `/student/dashboard`, `/guardian/dashboard`). Password reset is
available from the login screen (`/forgot-password`).

### Roles and access

| Role | Typical access |
|---|---|
| `admin` (Administrator) | Everything, including users, roles, settings, backups |
| `teacher` | Classes, students, attendance, marks, results, notices |
| `accountant` | Fee collection, expenses, financial reports, payment approval |
| `librarian` | Books, issues, dues, library reports |
| `student` / `parent` | Portal only — own/child attendance, results, receipts, messages |
| `user` | Base role with no elevated permission by default |

Permissions are managed with Spatie Laravel Permission (`/dashboard/roles`,
`/dashboard/permissions`). The administrator role holds all permissions.

---

## 2. Dashboard tour

The dashboard sidebar is grouped to match the other Eskoofy products:

| Group | What it holds |
|---|---|
| **Main** | Dashboard home, search, onboarding |
| **Academics** | Classes, sections, subjects, batches, sessions, routines, assignments, attendance, exams, results |
| **People** | Students, teachers, guardians, staff directory, users, roles, permissions |
| **Admissions** | Applications, documents, tests, payment verification |
| **Daily** | Attendance, notices, announcements, messages, events, calendar |
| **Finance** | Fees, fee payments, refunds, expenses, budgets, ledger, bank reconciliation, statements |
| **HR** | Payroll, payslips, salary structures, leaves, staff attendance |
| **Documents** | Certificates, admit cards, ID cards, progress reports, media, documents |
| **Library** | Books, categories, issues, reports |
| **Facilities** | Transport (vehicles, routes, assignments), hostel |
| **Communications** | SMS campaigns, notification templates & preferences |
| **Website CMS** | News, gallery, notices, announcements, events, careers, testimonials, committee, contact submissions |
| **Administration** | Users, roles, permissions |
| **Configuration** | All settings, store, analytics, reports |
| **System** | Activity log, visitor logs, backups, bulk import/export, cache |

**Topbar features:** global search, notifications dropdown, dark/light mode, language
switcher (English/Bengali), command palette, and favourite (pinned) menu items. Dashboard
write actions are rate-limited (120 requests/minute per user/IP).

---

## 3. Module-by-module guide

### 3.1 Students — `/dashboard/students`

- **Add a student:** Students → Add Student. Required identity, class (`class_id`), batch
  (`batch_id`) and roll number.
- **View/edit/delete:** open the student row; the detail page also links attendance,
  results and fees for that student.
- **Promote students:** Students → Promote — bulk-move a batch to the next class/session.
- **Bulk import/export:** System → Bulk (`/dashboard/bulk`).
- **Export data** and per-student **ID cards** are under Documents.

### 3.2 Teachers & staff — `/dashboard/teachers`, `/dashboard/staff`

- Create teacher/staff records with employee IDs and subjects; the **Staff Directory**
  (`/dashboard/staff-directory`) lists all staff.
- **Staff attendance:** HR → Staff Attendance (`/dashboard/staff-attendance`) with daily
  marking and reports.

### 3.3 Classes, sections, subjects, batches, sessions

- **Classes** `/dashboard/classes` (create/edit/delete, show), **Sections**
  `/dashboard/sections`, **Subjects** `/dashboard/subjects`, **Batches**
  `/dashboard/batches`, **Academic Sessions/Years** under Academics.
- **Routines** `/dashboard/routines` — build the weekly timetable per class/section.

### 3.4 Attendance — `/dashboard/attendance`

- Mark daily attendance per class/section, or use **Bulk** attendance to mark a whole
  class at once. Reports and exports are under Reports → Attendance.

### 3.5 Exams & results — `/dashboard/exams`

1. **Create an exam** (Exam → Create) — an exam ties to a `batch_id`,
   `academic_session_id` and `section_id`.
2. **Enter marks** on the results screen; marks use `obtained_marks`.
3. **Publish/unpublish** results, then set **visibility** for students/guardians.
4. Print **marksheets**, **progress reports**, **admit cards**, **seat plans** and
   **certificates** from the Documents/Exams menus.

> An exam counts as *fully published* only when both the publish flag and the workflow
> status are set (`isFullyPublished()`). If students cannot see results, check both.

### 3.6 Fees, payments & refunds — `/dashboard/fees`, `/dashboard/fee-payments`

- **Fee structure:** define fees per class/type; **collect** payments and generate
  **receipts/invoices**.
- **Fee payments:** approve or cancel pending payments (`/dashboard/fee-payments`).
- **Online gateways:** configure bKash/Nagad/Rocket (BD) or Stripe/PayPal/Paddle (INT)
  under Configuration → Payment, or `/dashboard/payment-gateways`.
- **Refunds:** raise and track refunds; approved refunds update the related payment.

### 3.7 Admissions — `/dashboard/admissions`

- Review applications, **verify admission payment**, schedule **admission tests**, mark
  documents received, and change application status (approve/reject/enroll).
- The public apply flow lives on the website at `/admissions/apply`; applicants can check
  status at `/admissions/status` and download receipts/approval letters.

### 3.8 Library — `/dashboard/library`

- **Books** and **Book Categories**, then **Issues** (issue → return, with fine/lost
  handling). Reports: issued, overdue and history (`/dashboard/library/reports`).

### 3.9 Transport & hostel

- **Transport:** Vehicles, Routes (with stops) and student Assignments.
- **Hostel:** Hostels, Rooms and occupant Assignments, plus per-hostel sub-pages.

### 3.10 HR & payroll — `/dashboard/payroll`

- Define **salary structures**, **generate payslips**, then **mark paid**.
- **Leaves:** leave types and requests — approve/reject/cancel.

### 3.11 Accounts & accounting — `/dashboard/ledger`

- **Expenses** and **Expense Categories**, **Budgets**, **Chart of Accounts**, **Ledger**
  (journal, cashbook, bankbook), **Bank Reconciliation**, and financial statements:
  **Income Statement**, **Balance Sheet**, **Cash Flow**.
- These modules require the `manage_expenses` permission (admin + accountant by default).

### 3.12 SMS & notifications — `/dashboard/sms`, `/dashboard/notifications`

- **SMS:** compose, preview and send campaigns; manage templates and send **fee due
  reminders**. Bulk SMS requires the `send_bulk_sms` permission.
- **Notifications:** in-app notifications, templates and per-user **preferences**;
  scheduled notifications run from the scheduler.

### 3.13 Reports & analytics — `/dashboard/reports`

- Reports for **students, fees, attendance and exams**, an **export** endpoint per type,
  and a **Report Builder** for custom reports. **Analytics** gives the live-stat overview.

### 3.14 Website CMS — `/dashboard/cms`

- Manage **News**, **Gallery**, **Notices**, **Announcements**, **Events** (+ calendar),
  **Careers** and job applications, **Testimonials**, **Committee** members, **Media** and
  **Contact Submissions** (with export). CMS pages are edited at `/dashboard/cms/pages`.

### 3.15 Users, roles & permissions

- **Users** `/dashboard/users`, **Roles** `/dashboard/roles`, **Permissions**
  `/dashboard/permissions`. Assign roles and permissions; the administrator role bypasses
  checks.

### 3.16 Settings — `/dashboard/settings`

General, Theme, Localization, Payment, Mail (with test send), Library, CMS,
Global Labels, and About. **Backups** live at `/dashboard/backup` (see the setup guide).

### 3.17 System tools

Activity log, Visitor logs, Bulk import/export, Media, Documents, Favourites, Help and
Onboarding are all reachable from the System group.

---

## 4. Public website & CMS

The public site is server-rendered Blade with a CMS-backed content model. Key routes:

| Page | Route |
|---|---|
| Home | `/` |
| About / Academics / Students / Faculty / Transport / Committee | `/about`, `/academics`, `/students`, `/faculty`, `/transport`, `/committee` |
| News (+ detail) | `/news`, `/news/{slug}` |
| Notices / Gallery / Events | `/notices`, `/gallery`, `/events` |
| Results lookup | `/results` |
| Routine | `/routine` |
| Admissions apply / status | `/admissions/apply`, `/admissions/status` |
| Payments | `/payments` (+ status, receipts) |
| Careers | `/careers` |
| Contact | `/contact` |
| Search | `/search` |
| Legal | `/terms`, `/privacy` |
| SEO / PWA | `/sitemap.xml`, `/robots.txt`, `/manifest.json` |

Language: `/locale/{locale}` switches between English and Bengali. Navigation and page copy
come from `lang/{en,bn}/site_frontend.php`, overridable via CMS.

---

## 5. Student & guardian portals

- **Portal landing:** `/portal` (`/portal/admission`, `/portal/progress`, `/portal/register`,
  `/portal/message`).
- **Student dashboard:** `/student/dashboard` — attendance, results, fees/receipts, routine,
  messages, profile.
- **Guardian dashboard:** `/guardian/dashboard` — children's attendance, results, fees,
  teacher messages.

Access is enforced by the `student_guardian` middleware; non-staff roles are redirected to
the portal from `/dashboard`.

---

## 6. JSON API (overview)

- **Base path:** `/api/v1` (mounted groups: `payments`, `refunds`, `admissions`, `students`,
  `notifications`; admin routes under `/api/v1/admin`).
- **Envelope:** every JSON response is normalised to
  `{ "success": bool, "message": string, "data": …, "meta"?: { "pagination": … } }`
  by `StandardizeApiResponse`. Gateway webhook/callback paths are **never** re-wrapped.
- **Auth:** Laravel Sanctum tokens (`Authorization: Bearer <token>`). Login:
  `POST /api/v1/login`.
- **Domains:** `auth/*`, `academics/*` (incl. `results/lookup`), `news/*`, `website-content/*`,
  `careers/*`, `events/*`, `fees/*`, `payments/*`, `refunds/*`, `admissions/*`, `teacher/*`,
  `search/*`, `notifications/*`, `admin/*`.

Full endpoint docs: [`../../docs/operations/API-PAYMENTS.md`](../../docs/operations/API-PAYMENTS.md).

---

## 7. Verification status

Verified on 2026-09-19 against this working tree:

| Check | Command | Result |
|---|---|---|
| Test suite | `cd eskoofy-laravel-app && composer test` | ✅ **927 passed** (2372 assertions) |
| Code style | `./vendor/bin/pint --test` | ✅ **PASS** (1052 files; 4 style issues fixed in this pass) |
| Route registration | `php artisan route:list` | ✅ 585 routes registered, no errors |
| Controller→view integrity | static scan of `app/Http/Controllers/**` | ✅ 131 controller files, 215 distinct views, **0 missing** |
| Seeder chain | covered by test suite | ✅ `RolePermissionSeeder` → `AdminUserSeeder` → `DemoUsersSeeder` |

No functional breakage was found in this pass beyond the code-style fix above. Known
intentional quirks (documented, not "fixed"): duplicated/guarded legacy migrations (e.g. two
`create_exams_table`) and the separate legacy `ClassModel`/`classes` table (see
`AGENTS.md`).

---

## Related docs

- [`SETUP-GUIDE.md`](./SETUP-GUIDE.md) — install, configure, deploy
- [`../README.md`](../README.md) — product overview
- [`../AGENTS.md`](../AGENTS.md) and [`../CLAUDE.md`](../CLAUDE.md) — architecture & conventions
- [`../../docs/operations/DEMO-CREDENTIALS.md`](../../docs/operations/DEMO-CREDENTIALS.md)
- [`../../docs/operations/DASHBOARD_TROUBLESHOOTING.md`](../../docs/operations/DASHBOARD_TROUBLESHOOTING.md)
- [`../../docs/operations/ADMISSIONS.md`](../../docs/operations/ADMISSIONS.md)
- [`../../docs/operations/API-PAYMENTS.md`](../../docs/operations/API-PAYMENTS.md)
- [`../../docs/guides/DEVELOPMENT.md`](../../docs/guides/DEVELOPMENT.md)
