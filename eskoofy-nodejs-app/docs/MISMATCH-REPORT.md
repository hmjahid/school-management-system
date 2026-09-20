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

- [ ] `dashboard/admit-cards/batch.blade.php` — NO NODE SCREEN
- [ ] `dashboard/admit-cards/print.blade.php` — NO NODE SCREEN
- [ ] `dashboard/announcements/_form.blade.php` — NO NODE SCREEN
- [ ] `dashboard/assignments/submissions.blade.php` — NO NODE SCREEN
- [ ] `dashboard/attendance/bulk.blade.php` — NO NODE SCREEN
- [ ] `dashboard/bulk/import.blade.php` — NO NODE SCREEN
- [ ] `dashboard/careers/applications.blade.php` — NO NODE SCREEN
- [ ] `dashboard/careers/form.blade.php` — NO NODE SCREEN
- [ ] `dashboard/certificates/print.blade.php` — NO NODE SCREEN
- [ ] `dashboard/cms/fields/_pair-text.blade.php` — NO NODE SCREEN
- [ ] `dashboard/cms/fields/_pair-textarea.blade.php` — NO NODE SCREEN
- [ ] `dashboard/cms/fields/_slider-row.blade.php` — NO NODE SCREEN
- [ ] `dashboard/cms/fields/contact_cards.blade.php` — NO NODE SCREEN
- [ ] `dashboard/cms/fields/group.blade.php` — NO NODE SCREEN
- [ ] `dashboard/cms/fields/hero.blade.php` — NO NODE SCREEN
- [ ] `dashboard/cms/fields/image.blade.php` — NO NODE SCREEN
- [ ] `dashboard/cms/fields/kv.blade.php` — NO NODE SCREEN
- [ ] `dashboard/cms/fields/list.blade.php` — NO NODE SCREEN
- [ ] `dashboard/cms/fields/repeater.blade.php` — NO NODE SCREEN
- [ ] `dashboard/cms/fields/repeater_sections.blade.php` — NO NODE SCREEN
- [ ] `dashboard/cms/fields/select.blade.php` — NO NODE SCREEN
- [ ] `dashboard/cms/fields/slider.blade.php` — NO NODE SCREEN
- [ ] `dashboard/cms/fields/text.blade.php` — NO NODE SCREEN
- [ ] `dashboard/cms/fields/textarea.blade.php` — NO NODE SCREEN
- [ ] `dashboard/cms/pages.blade.php` — NO NODE SCREEN
- [ ] `dashboard/cms/partials/page-meta-fields.blade.php` — NO NODE SCREEN
- [ ] `dashboard/documents/_form.blade.php` — NO NODE SCREEN
- [ ] `dashboard/documents/_school-info-bar.blade.php` — NO NODE SCREEN
- [ ] `dashboard/events/calendar.blade.php` — NO NODE SCREEN
- [ ] `dashboard/exams/marksheet-pdf.blade.php` — NO NODE SCREEN
- [ ] `dashboard/exams/my-results.blade.php` — NO NODE SCREEN
- [ ] `dashboard/exams/results.blade.php` — NO NODE SCREEN
- [ ] `dashboard/gallery/_form.blade.php` — NO NODE SCREEN
- [ ] `dashboard/hostels/_form.blade.php` — NO NODE SCREEN
- [ ] `dashboard/ledger/_ledger-table.blade.php` — NO NODE SCREEN
- [ ] `dashboard/ledger/bankbook.blade.php` — NO NODE SCREEN
- [ ] `dashboard/ledger/cashbook.blade.php` — NO NODE SCREEN
- [ ] `dashboard/ledger/journal.blade.php` — NO NODE SCREEN
- [ ] `dashboard/modules/attendance.blade.php` — NO NODE SCREEN
- [ ] `dashboard/modules/classes.blade.php` — NO NODE SCREEN
- [ ] `dashboard/modules/contact-submissions.blade.php` — NO NODE SCREEN
- [ ] `dashboard/modules/exams.blade.php` — NO NODE SCREEN
- [ ] `dashboard/modules/fees.blade.php` — NO NODE SCREEN
- [ ] `dashboard/modules/parents.blade.php` — NO NODE SCREEN
- [ ] `dashboard/modules/settings.blade.php` — NO NODE SCREEN
- [ ] `dashboard/modules/staff.blade.php` — NO NODE SCREEN
- [ ] `dashboard/modules/students.blade.php` — NO NODE SCREEN
- [ ] `dashboard/modules/teachers.blade.php` — NO NODE SCREEN
- [ ] `dashboard/news/_form.blade.php` — NO NODE SCREEN
- [ ] `dashboard/notices/_form.blade.php` — NO NODE SCREEN
- [ ] `dashboard/notifications/preferences.blade.php` — NO NODE SCREEN
- [ ] `dashboard/notifications/templates.blade.php` — NO NODE SCREEN
- [ ] `dashboard/onboarding.blade.php` — NO NODE SCREEN
- [ ] `dashboard/partials/bulk-actions-bar.blade.php` — NO NODE SCREEN
- [ ] `dashboard/partials/form-errors.blade.php` — NO NODE SCREEN
- [ ] `dashboard/partials/inline-validation.blade.php` — NO NODE SCREEN
- [ ] `dashboard/partials/tabs-nav.blade.php` — NO NODE SCREEN
- [ ] `dashboard/payroll/generate.blade.php` — NO NODE SCREEN
- [ ] `dashboard/payroll/payslip-show.blade.php` — NO NODE SCREEN
- [ ] `dashboard/payroll/payslips.blade.php` — NO NODE SCREEN
- [ ] `dashboard/payroll/structures.blade.php` — NO NODE SCREEN
- [ ] `dashboard/reports/analytics.blade.php` — NO NODE SCREEN
- [ ] `dashboard/reports/attendance.blade.php` — NO NODE SCREEN
- [ ] `dashboard/reports/balance-sheet.blade.php` — NO NODE SCREEN
- [ ] `dashboard/reports/builder.blade.php` — NO NODE SCREEN
- [ ] `dashboard/reports/cash-flow.blade.php` — NO NODE SCREEN
- [ ] `dashboard/reports/fees.blade.php` — NO NODE SCREEN
- [ ] `dashboard/reports/income-statement.blade.php` — NO NODE SCREEN
- [ ] `dashboard/reports/students.blade.php` — NO NODE SCREEN
- [ ] `dashboard/settings/about.blade.php` — NO NODE SCREEN
- [ ] `dashboard/settings/cms.blade.php` — NO NODE SCREEN
- [ ] `dashboard/settings/general.blade.php` — NO NODE SCREEN
- [ ] `dashboard/settings/global-labels.blade.php` — NO NODE SCREEN
- [ ] `dashboard/sms/compose.blade.php` — NO NODE SCREEN
- [ ] `dashboard/sms/due-reminder.blade.php` — NO NODE SCREEN
- [ ] `dashboard/sms/preview.blade.php` — NO NODE SCREEN
- [ ] `dashboard/sms/templates.blade.php` — NO NODE SCREEN
- [ ] `dashboard/software.blade.php` — NO NODE SCREEN
- [ ] `dashboard/staff-attendance/report.blade.php` — NO NODE SCREEN
- [ ] `dashboard/student-id-cards/batch.blade.php` — NO NODE SCREEN
- [ ] `dashboard/student-id-cards/print.blade.php` — NO NODE SCREEN
- [ ] `dashboard/students/promote.blade.php` — NO NODE SCREEN
- [ ] `dashboard/students/results.blade.php` — NO NODE SCREEN
- [ ] `dashboard/testimonials/_form.blade.php` — NO NODE SCREEN
- [ ] `dashboard/testimonials/print.blade.php` — NO NODE SCREEN

## 6. Next actions (plan)

1. Dashboard topbar + sidebar exact parity (read real blades, port).
2. Print/PDF screens (admit-cards, certificates, student-id-cards, results, receipts).
3. CMS editor screens (cms/pages, cms/edit, cms/fields/*).
4. Settings tabs, notifications templates/preferences, reports builder internals.
5. Interior site pages (news/gallery/events/contact) full design parity.
