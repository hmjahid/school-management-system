# Not-implemented tasks — `eskoofy-nodejs-app`

Snapshot of what is **not yet implemented** in the Node variant vs the Laravel
reference (`eskoofy-laravel-app`). Companion to `docs/PORTING-STATUS.md` and
`docs/MISMATCH-REPORT.md`; this list is the actionable backlog.

Last updated: 2026-09-22

---

## 1. Dashboard — non-page routes (JSON / export / redirect endpoints)

These Laravel routes are real controllers but are **not pages**; the Node catch-all
either falls to a placeholder or has no handler at all.

| Route | Laravel action | Current Node behavior | Effort |
|---|---|---|---|
| `GET /dashboard/search?q=` | `DashboardSearchController@search` (JSON search-as-you-type: students/teachers/notices/classes) | no handler (topbar search button not wired) | medium |
| `GET /dashboard/expenses-export` | `DashboardExpenseController@export` (CSV download) | generic placeholder | low |
| `GET /dashboard/locale/{locale}` | `DashboardLocaleController@switch` (redirect + cookie) | catch-all placeholder (topbar uses public `/locale/{loc}` instead) | low |
| `GET /dashboard/backup/download/{file}` | `DashboardBackupController@download` | ✅ has `route.ts` | done |
| `GET /dashboard/bulk/export/{resource}` + `import/{resource}` | bulk export/import | ✅ has `route.ts` | done |

## 2. Dashboard — remaining placeholder screens

Screens the app renders bespoke blades for, but the Node variant still serves the
generic `RoutePlaceholder` or generic CRUD.

- **Settings tabs** beyond `about` / `cms` / `global-labels` (e.g. general, academic,
  fees, payments, library, transport, notifications, sms) — `SettingsTab` only covers 3.
- **Notifications inbox** (`/dashboard/notifications` generic CRUD of the template table;
  the app has a real stream/inbox page).
- **CMS field editors** — `/dashboard/cms/{page}/edit` is a generic editor; the app has
  per-type field editors (hero/paragraph/bullets/cards/faq, media picker).
- **Media library picker** (upload + browse inside forms) — table exists, UI not ported.
- **Admissions review workflow** (`/dashboard/admissions/{id}`) — generic CRUD show only;
  the app has status changes, documents, tests, payment verify actions.
- **Permissions / roles admin** — generic CRUD over `roles`; spatie assignment UI (user →
  roles/permissions matrix) not ported.

## 3. Print / PDF generation

Admit cards, ID cards, certificates, marksheets, receipts, progress reports and seat
plans render **printable HTML** in the Node variant but do **not** produce a downloadable
PDF file.

- Laravel uses `barryvdh/laravel-dompdf`; the Node side has no PDF pipeline.
- Currently affected screens render a `window.print()` view (parity in content, no file).
- Candidates: adopt `@react-pdf/renderer`, Puppeteer, or a headless-Chrome route.

## 4. Business-logic depth (schema + APIs exist, workflow not ported)

| Area | Laravel | Node |
|---|---|---|
| Exam publish semantics | `isFullyPublished()` (status + column) | boolean column only |
| Recurring fee / payment plans | scheduler + invoices | table only, no generation |
| Ledger postings (double-entry) | auto-posting on payments/expenses | generic CRUD |
| Payroll runs | generates payslips, salary structures, P45-ish | generic CRUD + print view |
| SMS campaigns | send/compose with template variables, delivery logs | templates + preview only |
| AdmissionSubmitter service | multi-step wizard + document uploads + notifications | single-page form, no file upload |
| Attendance auto-marking / bulk flows | per-class/per-date workflows | basic bulk screen |
| Promotion (batch year-end) | PromoteStudents | ✅ implemented |
| Backups | scheduled + on-demand DB backup | manual action only |

## 5. Integrations

- **Payment gateways** (bKash/Nagad/Rocket/Bank + PayPal/Stripe/Paddle for `int`) — tables
  + config exist; no real initiate/callback/webhook handlers.
- **SMS providers** (Twilio/Vonage/bKash-message) — settings exist, no send path.
- **Mail** — `mail_enabled` setting exists; no queue/mailer.
- **Queues / scheduler** — Laravel `queue:monitor-failed`, backups, reminders not ported.

## 6. Auth depth

- Cookie session + role→permission map is in.
- **spatie/permission parity**: per-model policies/abilities are approximated with the
  `can()` role map; the app's `PermissionMiddleware` / `RoleMiddleware` route gates are
  not fully mirrored (most dashboard routes are behind a simple role check).
- Portal screens (`/portal`, `/portal/admission`, `/portal/progress`) are auth-gated but
  staff-role redirect + per-object 403 use a simplified role match.

## 7. Exact Blade markup (pixel parity)

The public site pages are functionally and structurally aligned with the app's blades,
but **not pixel-identical** in every case. Known gaps:

- Home hero slider / photo slider fallback vs the app's live carousel markup.
- Faculty/committee cards use generic avatars; the app has richer photo/icon states.
- Print stylesheets differ between the app's print blades and the Node print views.
- Dark-mode variants are Tailwind-only (no `dark:` parity pass on every site page).

## 8. Runtime locale switching

- Profile is `bd` (bn+en) or `int` (en) at **build time**; `lang/*.ts` are pre-generated.
- The app's **per-request** language switch (`?lang=bn` + session cookie, dashboard locale
  cookie) is only partially wired (`resolveRequestLocale` reads cookies; the dashboard
  locale toggle is stubbed).

## 9. Test coverage

- 97 Vitest unit tests cover schema/route/resource/DB layers.
- No **integration tests** against a live MySQL (tests use the SQLite harness).
- No E2E (Playwright) parity tests comparing Blade vs Node output.

---

## Known good (do not re-open)

- 585 routes, 107 Prisma tables, 95 sidebar keys, 742 lang keys ×2 — parity-gated.
- Dashboard home (`/dashboard`), analytics, reports, reports/builder, sms,
  communications, progress-reports, seat-plans, profile, my-results — bespoke screens.
- Public site pages (28 real pages) — functionally aligned.
- Generic CRUD engine for all dashboard resources.
- Wrong-table resolutions fixed (transport/vehicles → `vehicles`, transport/assignments).