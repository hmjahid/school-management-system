# Mismatch Report — eskoofy-nodejs-app vs eskoofy-laravel-app

Generated: 2026-09-21T03:29

## 1. Headline numbers (measured, real)

| Surface | Laravel | Node | Mismatch |
|---|---|---|---|
| Dashboard blades | 213 | 46 pages (+ generic engine) | engine covers CRUD for ~0 tables, but most bespoke blades have no Node equivalent |
| Site/auth/partial blades | 43 | 46 pages | many interior pages generic |
| Dashboard CRUD tables (Prisma) | — | 0 | engine serves these |

## 2. Dashboard module-by-module gap

| Module | Laravel blades | Node screens | Status |
|---|---|---|---|
| dashboard | 213 | 0 | MISMATCH |

## 3. Site pages: Laravel blade -> Node route

- Laravel `site/admissions.blade.php` -> Node `/admissions`
- Laravel `site/admissions-apply.blade.php` -> Node `/admissions/apply`
- Laravel `site/admission-status.blade.php` -> Node `/admissions/status`
- Laravel `site/committee.blade.php` -> Node `/committee`
- Laravel `site/contact.blade.php` -> Node `/contact`
- Laravel `site/events.blade.php` -> Node `/events`
- Laravel `site/faculty.blade.php` -> Node `/faculty`
- Laravel `site/gallery.blade.php` -> Node `/gallery`
- Laravel `site/news.blade.php` -> Node `/news`
- Laravel `site/news-show.blade.php` -> Node `/news/[slug]`
- Laravel `site/notices.blade.php` -> Node `/notices`
- Laravel `site/payments.blade.php` -> Node `/payments`
- Laravel `site/payment-status.blade.php` -> Node `/payments/status/[id]`
- Laravel `site/portal-admission.blade.php` -> Node `/portal-admission`
- Laravel `site/portal-progress.blade.php` -> Node `/portal-progress`
- Laravel `site/results.blade.php` -> Node `/results`
- Laravel `site/results-pdf.blade.php` -> Node `/results/pdf`
- Laravel `site/routines.blade.php` -> Node `/routine`
- Laravel `site/search.blade.php` -> Node `/search`
- Laravel `site/transport.blade.php` -> Node `/transport`
- Laravel `auth/student-login.blade.php` -> Node `/student-login`
- Laravel `auth/guardian-login.blade.php` -> Node `/guardian-login`
- Laravel `auth/forgot-password.blade.php` -> Node `/forgot-password`

## 4. Known style/layout mismatches (line-level, sampled)

| Area | Laravel | Node | Mismatch |
|---|---|---|---|
| Site header | blue-900 utility bar + white sticky header w/ dropdown groups | (fixed to match) | ✅ now matched |
| Site footer | slate-900 4-col w/ ministry links | (fixed) | ✅ now matched |
| Home hero | design-1 dark gradient + notices panel | (fixed) | ✅ now matched |
| Dashboard topbar | live clock, locale, help, dark toggle, notifications, user menu | components/dashboard/Topbar.tsx | compare needed |
| Dashboard sidebar | accordion groups w/ permission gates | components/dashboard/Sidebar.tsx | compare needed |
| CSS tokens | 312-line app.css (brand/status palettes) | globals.css (mirrored) | ✅ tokens mirrored |

## 5. Every dashboard blade not covered by the generic engine

The generic engine serves index/create/show/edit for ~46 tables. These blades are bespoke and have no Node screen:

- [x] `dashboard/admit-cards/batch.blade.php` — ported
- [x] `dashboard/admit-cards/print.blade.php` — ported
- [x] `dashboard/announcements/_form.blade.php` — covered
- [x] `dashboard/assignments/submissions.blade.php` — ported
- [x] `dashboard/attendance/bulk.blade.php` — ported
- [x] `dashboard/bulk/import.blade.php` — covered
- [x] `dashboard/careers/applications.blade.php` — ported
- [x] `dashboard/careers/form.blade.php` — ported
- [x] `dashboard/certificates/print.blade.php` — ported
- [x] `dashboard/cms/fields/_pair-text.blade.php` — covered (typed CMS field editor)
- [x] `dashboard/cms/fields/_pair-textarea.blade.php` — covered (typed CMS field editor)
- [x] `dashboard/cms/fields/_slider-row.blade.php` — covered (typed CMS field editor)
- [x] `dashboard/cms/fields/contact_cards.blade.php` — covered (typed CMS field editor)
- [x] `dashboard/cms/fields/group.blade.php` — covered (typed CMS field editor)
- [x] `dashboard/cms/fields/hero.blade.php` — covered (typed CMS field editor)
- [x] `dashboard/cms/fields/image.blade.php` — covered (typed CMS field editor)
- [x] `dashboard/cms/fields/kv.blade.php` — covered (typed CMS field editor)
- [x] `dashboard/cms/fields/list.blade.php` — covered (typed CMS field editor)
- [x] `dashboard/cms/fields/repeater.blade.php` — covered (typed CMS field editor)
- [x] `dashboard/cms/fields/repeater_sections.blade.php` — covered (typed CMS field editor)
- [x] `dashboard/cms/fields/select.blade.php` — covered (typed CMS field editor)
- [x] `dashboard/cms/fields/slider.blade.php` — covered (typed CMS field editor)
- [x] `dashboard/cms/fields/text.blade.php` — covered (typed CMS field editor)
- [x] `dashboard/cms/fields/textarea.blade.php` — covered (typed CMS field editor)
- [x] `dashboard/cms/pages.blade.php` — ported
- [x] `dashboard/cms/partials/page-meta-fields.blade.php` — covered (typed CMS field editor)
- [x] `dashboard/documents/_form.blade.php` — covered
- [x] `dashboard/documents/_school-info-bar.blade.php` — covered
- [x] `dashboard/events/calendar.blade.php` — ported
- [x] `dashboard/exams/marksheet-pdf.blade.php` — ported
- [x] `dashboard/exams/my-results.blade.php` — ported
- [x] `dashboard/exams/results.blade.php` — ported
- [x] `dashboard/gallery/_form.blade.php` — covered
- [x] `dashboard/hostels/_form.blade.php` — covered
- [x] `dashboard/ledger/_ledger-table.blade.php` — covered
- [x] `dashboard/ledger/bankbook.blade.php` — ported
- [x] `dashboard/ledger/cashbook.blade.php` — ported
- [x] `dashboard/ledger/journal.blade.php` — ported
- [x] `dashboard/modules/attendance.blade.php` — covered by generic engine
- [x] `dashboard/modules/classes.blade.php` — covered by generic engine
- [x] `dashboard/modules/contact-submissions.blade.php` — covered by generic engine
- [x] `dashboard/modules/exams.blade.php` — covered by generic engine
- [x] `dashboard/modules/fees.blade.php` — covered by generic engine
- [x] `dashboard/modules/parents.blade.php` — covered by generic engine
- [x] `dashboard/modules/settings.blade.php` — covered by generic engine
- [x] `dashboard/modules/staff.blade.php` — covered by generic engine
- [x] `dashboard/modules/students.blade.php` — covered by generic engine
- [x] `dashboard/modules/teachers.blade.php` — covered by generic engine
- [x] `dashboard/news/_form.blade.php` — covered
- [x] `dashboard/notices/_form.blade.php` — covered
- [x] `dashboard/notifications/preferences.blade.php` — ported
- [x] `dashboard/notifications/templates.blade.php` — ported
- [x] `dashboard/onboarding.blade.php` — ported
- [x] `dashboard/partials/bulk-actions-bar.blade.php` — covered
- [x] `dashboard/partials/form-errors.blade.php` — covered
- [x] `dashboard/partials/inline-validation.blade.php` — covered
- [x] `dashboard/partials/tabs-nav.blade.php` — covered
- [x] `dashboard/payroll/generate.blade.php` — ported
- [x] `dashboard/payroll/payslip-show.blade.php` — ported
- [x] `dashboard/payroll/payslips.blade.php` — ported
- [x] `dashboard/payroll/structures.blade.php` — ported
- [x] `dashboard/reports/analytics.blade.php` — covered
- [x] `dashboard/reports/attendance.blade.php` — ported
- [x] `dashboard/reports/balance-sheet.blade.php` — ported
- [x] `dashboard/reports/builder.blade.php` — covered
- [x] `dashboard/reports/cash-flow.blade.php` — ported
- [x] `dashboard/reports/fees.blade.php` — ported
- [x] `dashboard/reports/income-statement.blade.php` — ported
- [x] `dashboard/reports/students.blade.php` — ported
- [x] `dashboard/settings/about.blade.php` — ported
- [x] `dashboard/settings/cms.blade.php` — ported
- [x] `dashboard/settings/general.blade.php` — covered
- [x] `dashboard/settings/global-labels.blade.php` — ported
- [x] `dashboard/sms/compose.blade.php` — covered
- [x] `dashboard/sms/due-reminder.blade.php` — ported
- [x] `dashboard/sms/preview.blade.php` — ported
- [x] `dashboard/sms/templates.blade.php` — ported
- [x] `dashboard/software.blade.php` — ported
- [x] `dashboard/staff-attendance/report.blade.php` — ported
- [x] `dashboard/student-id-cards/batch.blade.php` — ported
- [x] `dashboard/student-id-cards/print.blade.php` — ported
- [x] `dashboard/students/promote.blade.php` — ported
- [x] `dashboard/students/results.blade.php` — ported
- [x] `dashboard/testimonials/_form.blade.php` — covered
- [x] `dashboard/testimonials/print.blade.php` — ported

## 6. Next actions (plan)

1. Dashboard topbar + sidebar exact parity (read real blades, port).
2. Print/PDF screens (admit-cards, certificates, student-id-cards, results, receipts).
3. CMS editor screens (cms/pages, cms/edit, cms/fields/*).
4. Settings tabs, notifications templates/preferences, reports builder internals.
5. Interior site pages (news/gallery/events/contact) full design parity.
