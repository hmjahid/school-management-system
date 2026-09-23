# Eskoofy Node.js Variant — User Manual

> Product: `eskoofy-nodejs-app` (Next.js 15 + TypeScript + Prisma) · Audience: the same users
> as the Laravel app — school staff, administrators, teachers, students & guardians. The UI,
> modules and JSON API mirror `eskoofy-laravel-app`; this manual documents what is available
> **today** in the Node variant.

- Setup & install: [`SETUP-GUIDE.md`](./SETUP-GUIDE.md)
- Deploy: [`DEPLOYMENT-GUIDE.md`](./DEPLOYMENT-GUIDE.md)
- Requirements: [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md)
- Full port ledger: [`PORTING-STATUS.md`](./PORTING-STATUS.md) + [`NOT-IMPLEMENTED.md`](./NOT-IMPLEMENTED.md)

---

## 1. Signing in

| Who | URL | Notes |
|---|---|---|
| Staff / admin (administrator, principal, accountant, librarian, teacher) | `/login` | Email + password |
| Students | `/login` | Email + password |
| Guardians / parents | `/login` | Email + password |

After signing in, staff land on **`/dashboard`**. The cookie session carries a role→permission
map mirroring the app; the `admin` role holds all permissions.

**Default dev account:** `admin@school.com` / `ChangeMe!2026$Tr0ng` (created by `npm run db:seed`).

---

## 2. Dashboard tour

The dashboard chrome mirrors the Laravel app: the same sidebar sections, topbar
(live clock, global search/command palette, notifications, locale switch, pin/favourites,
theme/dark mode, help), the shell, toasts, confirm modal and print CSS.

| Section | What it holds |
|---|---|
| **Main** | Dashboard home, messages |
| **Academics** | Classes, sections, subjects, batches, sessions, routines, assignments, exams, results, my-results |
| **People** | Students, teachers, parents/guardians, staff directory, all users |
| **Admissions** | Applications, review workflow |
| **Daily** | Attendance, attendance mark, staff attendance |
| **Finance** | Fees, fee payments, refunds, expenses, budgets, ledger, bank reconciliation, income statement, balance sheet, cash flow |
| **HR** | Leaves, leave types, payroll, payslips, salary structures, staff attendance |
| **Documents** | Admit cards, ID cards, certificates, progress reports, testimonial, committee, media |
| **Library** | Books, categories, issues, reports |
| **Facilities** | Events, calendar, transport, hostel |
| **Communications** | Messages, bulk SMS, notification templates, notification preferences |
| **Website CMS** | Pages, CMS settings, global labels, news, events, gallery, announcements, notices, documents, media, form submissions, careers, job applications |
| **Administration** | Users, roles, permissions |
| **Configuration** | Settings (theme/localization/payment/library/academic/sms/mail), reports, reports/builder, analytics, bulk import/export |
| **System** | Activity log, visitor logs, backups, cache/tools |

**Generic CRUD engine:** every resource listed above has working **list** (search +
pagination + status badges), **show**, **create** and **edit** forms (with typed fields and
foreign-key dropdowns) and **delete** — rendered from the Prisma schema rather than
hand-written screens each.

**Bespoke screens** already beyond CRUD: dashboard home, analytics, reports + reports/builder
(CSV export), SMS campaign workflow (compose → recipients → preview → send/due reminder),
communications, settings tabs, notifications inbox, permissions matrix, media library
(upload + picker), admissions review, activity log, dashboard search/command palette.

---

## 3. Module-by-module guide

The workflows match the Laravel app (see `eskoofy-laravel-app/docs/USER-MANUAL.md` for the
procedural detail). Highlights in this variant:

- **Students / Teachers / Parents** — full CRUD; student detail links attendance, results and fees.
- **Classes, Sections, Subjects, Batches, Academic Sessions, Routines** — CRUD per module.
- **Attendance** — daily marking + bulk screen.
- **Exams & results** — create exams, enter marks (`obtained_marks`), publish/visibility.
- **Fees & payments** — fee structure, collect payments, generate receipts/invoices, refunds.
- **Admissions** — review applications, verify admission payment, schedule tests, change status.
- **Library, Transport, Hostel, Leaves, Payroll** — CRUD + report screens where the app ships them.
- **SMS** — compose, recipient resolution, preview, send campaign / fee-due reminder
  (carrier delivery is not yet wired — see `NOT-IMPLEMENTED.md` §5).
- **Reports & analytics** — fees/attendance/students CSV export + live-stat overview.

> **Not yet ported** (see `NOT-IMPLEMENTED.md`): downloadable PDF files (print views exist),
> payment-gateway callbacks/webhooks, live SMS/mail delivery, queues & scheduler, full
> business-logic depth (recurring invoices, ledger posting automation, payroll run
> automation, full exam `isFullyPublished()` semantics).

---

## 4. Public website

Server-rendered React pages wired to the app's tables:

`/`, `/about`, `/academics`, `/students`, `/faculty`, `/committee`, `/news`, `/news/{slug}`,
`/notices`, `/events`, `/gallery`, `/transport`, `/admissions`, `/admissions/apply`,
`/admissions/status`, `/results`, `/routine`, `/search`, `/contact`, `/portal`,
`/terms`, `/privacy`, plus the payments family and `/sitemap.xml`.

The result lookup (`/results`) queries real exam results by roll/batch, matching the app's
public behaviour.

---

## 5. Student & guardian portal

- `/portal` — authenticated portal landing (admission + progress views wired to real tables).
- Portal screens are auth-gated; staff-role redirect and per-object 403 use a simplified
  role match compared to the app's full middleware stack.

---

## 6. JSON API

- **Base path:** `/api/v1` (`/api/v1/ping`, `/api/v1/academics/results/lookup`, plus generic
  REST over every table).
- **Envelope:** every response is `{ success, message, data[, meta] }`
  (`lib/api-response.ts`); gateway `*/webhook/*` + `*/callback/*` paths are never rewrapped.
- **Auth:** cookie session JWT (`jose`), bcrypt password hashes — mirroring the app's session.

---

## 7. Verification status

| Gate | Command | Result |
|---|---|---|
| TypeScript | `npm run typecheck` | ✅ |
| Lint | `npm run lint` | ✅ (0 errors) |
| Unit tests | `npm test` | ✅ 102 Vitest tests |
| Route/sidebar parity | `npm run route:parity` | ✅ 585/585 routes, 95/95 sidebar keys, 107 tables |
| Production build | `npm run build` | ✅ |

End-to-end against the app's own migrated database: dashboard CRUD, public pages and the API
return real rows (0 Prisma errors). See `PORTING-STATUS.md` for the full ledger.

---

## Related docs

- [`SETUP-GUIDE.md`](./SETUP-GUIDE.md) — install & configure
- [`DEPLOYMENT-GUIDE.md`](./DEPLOYMENT-GUIDE.md) — production deployment
- [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md) — sizing
- [`PORTING-STATUS.md`](./PORTING-STATUS.md) · [`NOT-IMPLEMENTED.md`](./NOT-IMPLEMENTED.md) — port ledger
- [`../README.md`](../README.md) · [`../AGENTS.md`](../AGENTS.md)