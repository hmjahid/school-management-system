# Not-implemented tasks — `eskoofy-nodejs-app`

Snapshot of what is **not yet implemented** in the Node variant vs the Laravel
reference (`eskoofy-laravel-app`). Companion to `docs/PORTING-STATUS.md` and
`docs/MISMATCH-REPORT.md`; this list is the actionable backlog.

Last updated: 2026-09-22

---

## 1. Dashboard — non-page routes (JSON / export / redirect endpoints) — ✅ done

| Route | Laravel action | Node behavior |
|---|---|---|
| `GET /dashboard/search?q=` | `DashboardSearchController@search` | ✅ `app/(dashboard)/dashboard/search/route.ts` — students/teachers/classes/notices/fees/payments + keyword quick-links, `{data:[{id,type,name,subtitle,url}]}`, consumed by the command palette |
| `GET /dashboard/expenses-export` | `DashboardExpenseController@export` | ✅ `app/(dashboard)/dashboard/expenses-export/route.ts` — CSV via the shared `formatCsv` contract, gated by `manage_expenses` |
| `GET /dashboard/locale/{locale}` | `DashboardLocaleController@switch` | ✅ `app/(dashboard)/dashboard/locale/[locale]/route.ts` — sets the dashboard locale cookie + redirects to referer |
| `POST /dashboard/favorites/toggle` | `dashboard.favorites.toggle` | ✅ `app/(dashboard)/dashboard/favorites/toggle/route.ts` — pin/unpin `dashboard_favorites`; wired to the topbar star + sidebar favorites |
| `GET /dashboard/backup/download/{file}` | `DashboardBackupController@download` | ✅ has `route.ts` |
| `GET /dashboard/bulk/export/{resource}` + `import/{resource}` | bulk export/import | ✅ has `route.ts` |

## 2. Dashboard — remaining placeholder screens

Screens the app renders bespoke blades for, but the Node variant still serves the
generic `RoutePlaceholder` or generic CRUD.

- **Settings tabs** — ✅ done. `/dashboard/settings` now renders the app's full tab
  set (theme, localization, payment, library, academic, sms, mail) and writes to
  `website_settings` / `library_settings` via server actions; `/dashboard/settings/general`
  is the School Info form. `about` / `cms` / `global-labels` tabs remain.
- **Notifications inbox** — ✅ done. `/dashboard/notifications` is a real stream
  (unread highlight, mark-one / mark-all-read) over the `notifications` table.
- **Permissions / roles admin** — ✅ done. `/dashboard/permissions` renders the
  grouped permission→role matrix plus the role list with member counts; per-user
  spatie assignment still uses the generated role/permission forms.
- **Media library picker** — ✅ done. `/dashboard/media` has upload (writes
  `public/uploads/media` + `website_media`), category filter, copy/download/delete,
  and an `?select=1` iframe mode that `postMessage`s the picked URL to the parent.
- **Admissions review workflow** — ✅ done. `/dashboard/admissions/{id}` renders
  applicant details, payment verify, documents, status update and test
  scheduling/history (gated by `manage_students` / `manage_payments`).
- **CMS field editors** — partially done. `CmsEdit` already renders typed editors
  (text/textarea/list/repeater/hero/media URL). Not yet ported: the app's live
  preview iframe and the `openMediaBrowser()` modal integration inside the editor.

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
| Payroll runs | generates payslips, salary structures | generic CRUD + generate screen |
| SMS campaigns | send/compose with template variables, delivery logs | templates + preview only |
| AdmissionSubmitter service | multi-step wizard + document uploads + notifications | single-page form, no file upload |
| Attendance auto-marking / bulk flows | per-class/per-date workflows | basic bulk screen |
| Promotion (batch year-end) | PromoteStudents | ✅ implemented |
| Backups | scheduled + on-demand DB backup | manual action only |

## 5. Integrations

- **Payment gateways** (bKash/Nagad/Rocket/Bank + PayPal/Stripe/Paddle for `int`) — tables
  + config + settings form exist; no real initiate/callback/webhook handlers.
- **SMS providers** (Twilio/Vonage/bKash-message) — settings exist, no send path.
- **Mail** — `mail_enabled` + SMTP settings form exist; no queue/mailer.
- **Queues / scheduler** — Laravel `queue:monitor-failed`, backups, reminders not ported.

## 6. Auth depth

- Cookie session + role→permission map is in.
- **spatie/permission parity**: the permissions matrix and role admin are now bespoke,
  but per-model policies/abilities are still approximated with the `can()` role map;
  the app's `PermissionMiddleware` / `RoleMiddleware` route gates are not fully mirrored.
- Portal screens (`/portal`, `/portal/admission`, `/portal/progress`) are auth-gated but
  staff-role redirect + per-object 403 use a simplified role match.

## 7. Exact Blade markup (pixel parity)

The **dashboard chrome** now mirrors the app's markup and design system
(class-based sidebar sections, collapsible groups with icons, nav search, pinned
favorites, install/dark/logout footer, topbar with clock/search/locale/pin/help/
notifications/user menu, skip link, loading bar, toast + confirm-modal roots,
command palette, help modal, runtime theme vars + `theme-*` styles, print CSS).

Still not pixel-identical:

- Public-site pages: home hero slider fallback vs the app's live carousel markup.
- Faculty/committee cards use generic avatars; the app has richer photo/icon states.
- Print stylesheets differ between the app's print blades and the Node print views.
- Per-module Blade views (charts, wizards, editor partials) are engine-based, not
  byte-identical.

## 8. Runtime locale switching

- Profile is `bd` (bn+en) or `int` (en) at **build time**; `lang/*.ts` are pre-generated.
- ✅ Dashboard locale switch is now wired end to end: the topbar posts to
  `/dashboard/locale/{locale}`, which sets the dashboard locale cookie that
  `resolveRequestLocale` reads.
- The **public** site's per-request `?lang=bn` + session cookie path is still only
  partially wired.

## 9. Test coverage

- **102 Vitest unit tests** cover schema/route/resource/DB layers plus the new nav
  section layout contract.
- No **integration tests** against a live MySQL (tests use the SQLite harness).
- No E2E (Playwright) parity tests comparing Blade vs Node output.

---

## Known good (do not re-open)

- 585 routes, 107 Prisma tables, 95 sidebar keys, 742 lang keys ×2 — parity-gated.
- Dashboard chrome (sidebar/topbar/shell/CSS) mirrors the Laravel design system.
- Dashboard home (`/dashboard`), analytics, reports, reports/builder, sms,
  communications, progress-reports, seat-plans, profile, my-results — bespoke screens.
- Settings tabs, notifications inbox, permissions matrix, media library, admissions
  review — bespoke screens.
- Non-page endpoints: search, expenses export, dashboard locale switch, favorites toggle.
- Public site pages (28 real pages) — functionally aligned.
- Generic CRUD engine for all dashboard resources.
- Wrong-table resolutions fixed (transport/vehicles → `vehicles`, transport/assignments).
