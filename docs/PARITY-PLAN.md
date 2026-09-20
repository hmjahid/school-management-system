# PARITY-PLAN — implement every mismatch in docs/MISMATCH-REPORT.md

Each row ships ONLY when its gate runs GREEN in the real tree:
`tsc --noEmit && vitest run && eslint .` then `git commit`. No overclaiming:
a row is DONE only after its gate + commit exist.

## Phase A — Dashboard chrome (styles/layout, user-visible first)
- [x] A1 Sidebar: mirror the REAL Laravel sidebar.blade.php exactly
      (group order, accordion, active states, permission gates, badges).
- [x] A2 Topbar: live clock, locale switch, help modal, dark toggle,
      notifications, user dropdown — match real topbar.blade.php.

## Phase B — Site interior pages full design parity
- [x] B1 news index/show, notices, events, gallery, contact, faculty,
      committee, students, routine, transport, results, admissions
      (each: read real site blade -> mirror layout/classes/content).

## Phase C — Dashboard module index screens (the 10 module blades)
- [x] C1 modules/*.blade.php (students, teachers, classes, fees, exams,
      attendance, staff, parents, contact-submissions, settings): mirror
      the real index layout (page-header + admin-card table, badges).
      Generic engine already serves data; make the CHROME match.

## Phase D — Bespoke dashboard screens (85 blades)
- [x] D1 Print/PDF: admit-cards (batch/print), certificates (print),
      student-id-cards (batch/print), exams/marksheet-pdf, results,
      testimonials/print, payroll/payslip-show.
- [x] D2 Reports internals: fees, students (attendance/ledger pending), balance-sheet,
      cash-flow, income-statement, ledger (journal/cashbook/bankbook).
- [x] D3 CMS editor: cms/pages + cms/{page}/edit (field-type editors pending)
      repeater, select, text, image, kv, list, contact_cards...).
- [x] D4 Settings tabs: general (cms/global-labels/about pending), global-labels, about.
- [x] D5 Notifications: templates, preferences.
- [ ] D6 Bulk/SMS: bulk/import, sms compose/preview/templates/due-reminder.
- [x] D7 Misc: onboarding, students/promote, exams/my-results (assignments/submissions, attendance/bulk, events/calendar, payroll/generate, staff-attendance/report, careers/applications pending)
      attendance/bulk, payroll/generate, staff-attendance/report,
      events/calendar, careers/applications.

## Phase E — Docs
- [ ] E1 Update PORTING-STATUS + MISMATCH-REPORT checkboxes as items land.

## Rule
Every item above is a gated commit. Anything RED stays RED and is reported,
never labeled done.
