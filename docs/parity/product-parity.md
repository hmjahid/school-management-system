# Side-by-side parity: dashboard, sidebar & dashboard contents (app / php / theme)

Status as of this review. "app" = `eskoofy-app` (Laravel), "php" = `eskoofy-php`
(raw PHP), "theme" = `eskoofy-theme` (WordPress). Merge strategy = **additive
only** (nothing deleted). php is pinned byte-identical to app for views/lang.

## 1. Sidebar navigation

### app vs php — byte-identical (verified via `diff`)

Both ship these groups; identical items, order, labels:

| Group | Items |
|---|---|
| Main | Dashboard |
| Messages | Messages, Communications (Bulk SMS, Notification templates, Notification preferences) |
| Academic → People | Students, Teachers, Parents, Staff directory, All Users |
| Academic → Academics | Classes, Exams, My results, Assignments, Class Routine |
| Admissions | Admissions |
| Daily | Attendance, Bulk mark, Staff attendance |
| Finance | Fees, Payments, Expenses, Ledger |
| HR | Leaves, Payroll, Staff directory |
| Documents | Admit Cards, Student ID Cards, Certificates, Testimonials, Committee Members |
| Library | Books, Book Categories, Book Issues, Library Reports |
| Flat | Events, Calendar, Transport, Hostel |
| System | Activity log, Visitor Logs, Backups |
| Website | Website CMS: All Pages, **CMS Settings**, **Global Labels**, News & Events, Gallery, Announcements, Notices, Documents, Media, Form submissions, Careers, Job applications |
| Administration | Users, Roles, Permissions |
| Configuration | Settings, Reports, Bulk Import/Export |
| Help | About, Help & Documentation |

> Note: app sidebar does **not** currently list its own finance/reports routes
> (Expense Categories, Budgets, Bank Reconciliation, Income Statement, Balance
> Sheet, Cash Flow, Reports Builder, Analytics) — those routes exist but were
> never linked. Merge adds them (see §4).

### theme — deltas vs app/php

| Group | Status | Detail |
|---|---|---|
| Main | mismatch | Dashboard only (app has Messages/Communications first) |
| Communications | EXTRA (flat) | Messages, Bulk SMS, Announcements, Notices, Notification templates, Notification preferences (app keeps only Messages flat; rest under sub-groups) |
| Academic | same items | People + Academics subgroups; Admissions flat |
| Daily | same | Attendance, Mark Attendance, Staff attendance |
| Finance | EXTRA | + Expense Categories, Budgets, Income Statement, Balance Sheet, Cash Flow (app/php missing these) |
| HR | EXTRA | + Leave Types, Payslips, Salary Structures (app/php: Leaves, Payroll, Staff directory only) |
| Documents | same | Admit Cards, ID Cards, Certificates, Testimonials, Committee |
| Library | same | Library, Library Reports |
| Facilities | EXTRA group name | Transport, Hostels, Library (app calls it flat items) |
| System | EXTRA | + Cache, Tools (app: Activity log, Visitor Logs, Backups) |
| Website | MISSING | app's **CMS Settings** and **Global Labels** absent in theme sidebar |
| Administration | same | Users & Roles |
| Configuration | same | Settings, Reports, Bulk Import/Export |
| Help | EXTRA | + Profile (app/php: About, Help & Documentation) |

Theme-only tokens that have **no app/php route**: `leave-types`,
`salary-structures`, `refunds`, `tools`, `cache` (app POST-only),
`sections`, `subjects`, `batches`, `academic-sessions`, `progress-reports`,
`seat-plans` — richer than app; kept (do not delete).

## 2. Dashboard contents (dashboard home)

| Block | app | php | theme |
|---|---|---|---|
| Header (title + welcome) | ✅ | ✅ | ✅ |
| Import / Reports buttons | ✅ | ✅ | ✅ |
| Setup completion banner | ✅ | ✅ | **MISSING** → port |
| Stat cards (Students/Teachers/Parents/Attendance/Revenue) | ✅ | ✅ | ✅ |
| Pending admissions badge | ✅ | ✅ | ✅ |
| Pending dues badge | ✅ | ✅ | ✅ |
| Revenue vs Expenses (12 mo) | ✅ | ✅ | ✅ |
| Today's Attendance | ✅ | ✅ | ✅ |
| Attendance Trend (7 d) | ✅ | ✅ | ✅ |
| Quick Actions (5) | ✅ | ✅ | ✅ |
| Workbench | ✅ dynamic, gated | ✅ | ✅ always shown |

## 3. Footer / topbar socials (Task 1 regression)

Topbar + footer social icons now read `esk_social_profiles()` with everything
5-platform (facebook/instagram/twitter/youtube/linkedin); header/footer icon row
no longer depends on `social-links` theme mod. Topbar login/logout was removed;
auth lives in sticky nav + mobile bar (Dashboard → Logout), matching app's
mobile bottom bar.

## 4. Planned additive merges

1. **app + php sidebar (Finance)** — under `@can('manage_expenses')` add:
   Expense Categories, Budgets, Bank Reconciliation, Income Statement, Balance
   Sheet, Cash Flow (routes `dashboard.expense-categories*`,
   `dashboard.budgets*`, `dashboard.bank-reconciliation.*`,
   `dashboard.reports.income-statement|-balance-sheet|-cash-flow` already reg'd).
2. **app + php sidebar (Configuration)** — Reports Builder
   (`dashboard.reports.builder`) + Analytics (`dashboard.analytics`).
3. **app + php lang** — `lang/en/dashboard.php` keys for the above (+ `bn` file).
4. **theme sidebar (Website)** — add CMS Settings + Global Labels links via
   Settings-tab deep links (`esk-settings` + `tab=cms`/`tab=labels`);
   new Global Labels settings tab added.
5. **theme dashboard** — port app setup/onboarding banner (6-item checklist:
   school info, timezone, academic session, classes, teachers, payment);
   CTA → `esk-onboarding`, dismiss via localStorage.
6. **php** — sidebar + lang copied byte-identical from app.

## 5. Regeneration note

App sidebar CSS additions are Tailwind utilities only (`clear-cache`, no new
tokens); theme uses the compiled `inc/app-dashboard.css` — regenerate from the
app Tailwind build if any new utility class is introduced.