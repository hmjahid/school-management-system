# Clone status — `eskoofy-nodejs-app` vs `eskoofy-laravel-app`

Reference product: `eskoofy-laravel-app`. Roadmap: `docs/design/VARIANT-BLUEPRINT.md` §5.

## The clone, in numbers

| Surface | Laravel app | Node variant | Status |
|---|---|---|---|
| Database tables | 101 migrations / 108 applied | **107 models** in `prisma/schema.prisma` | ✅ same table + column names |
| HTTP routes | **585** (`route:list`) | **585** in `lib/routes.generated.ts` | ✅ parity-gated |
| Dashboard sidebar | groups/items/gates | `lib/nav.ts` | ✅ parity-gated |
| Strings (en + bn) | `lang/{en,bn}/*` | **742 keys × 2** | ✅ same keys |
| API envelope | `{success,message,data[,meta]}` | `lib/api-response.ts` | ✅ same |
| Dashboard CRUD screens | 213 views | **generic index/create/show/edit/delete for all 34 resources** | ✅ functional |
| Public site pages | ~70 routes | **19 real pages** + catch-all | ✅ functional |
| Components | `<x-card>`, `<x-badge>`, `<x-button>`, `<x-admin-data-table>`, `<x-page-header>`, `<x-empty-state>` | `components/ui/*` (1:1) | ✅ same variants |
| Dashboard chrome | topbar (search/clock/locale/help/dark/notifications/user), sidebar accordion | `components/dashboard/*` | ✅ same features |

## What now works end to end

**Dashboard (authenticated)**

- Sidebar mirrors the app exactly — group order, item order, permission gates, accordion
  groups, active state, `planned` chips for un-ported screens.
- Topbar: live clock, website link, search, locale indicator, help, **dark mode**
  (class-based, persisted), notifications, user dropdown with logout.
- **Generic CRUD for every resource**: list (search + pagination + status badges),
  show, create and edit with generated forms, and delete — driven by Prisma DMMF so all
  ~34 dashboard resources (`students`, `teachers`, `classes`, `fees`, `exam-results`,
  `books`, `hostels`, …) work without a hand-written screen each.
- Forms render field-type widgets (date, number, boolean, enum select) and resolve
  foreign keys into lookup selects with real options.

**Public site (19 real pages)**

`/`, `/about`, `/academics`, `/students`, `/faculty`, `/committee`, `/news`,
`/news/{slug}`, `/notices`, `/events`, `/gallery`, `/transport`, `/admissions`,
`/admissions/apply`, `/admissions/status`, `/results`, `/routine`, `/search`,
`/contact`, `/portal`, `/terms`, `/privacy` — wired to the app's tables (`news`,
`notices`, `events`, `galleries`, `testimonials`, `committee_members`, `students`,
`routines`, `admissions`, `contact_submissions`, `website_contents`).

**API** — generic REST over every table plus typed endpoints
(`/api/v1/ping`, `/api/v1/academics/results/lookup`), all in the app's envelope.

## Verified end to end

Run against the Laravel app's own migrated database (SQLite copy, JWT session minted with
the app's session secret):

| Check | Result |
|---|---|
| `GET /api/v1/students` | real rows, snake_case columns, paginated envelope |
| `GET /api/v1/academics/results/lookup?roll=1` | real student + exam results |
| `/dashboard/students`, `/students/1`, `/students/1/edit`, `/students/create` | 200, real rows in the table |
| `/dashboard/classes`, `/fees`, `/users` | 200 |
| `/`, `/notices`, `/events`, `/gallery`, `/faculty`, `/routine` | 200 |
| Prisma errors during the run | **0** |

Gates: `tsc --noEmit` ✅ · ESLint ✅ · **48 Vitest tests** ✅ · `route:parity` ✅
(585/585 routes, 95/95 sidebar keys, 107 tables) · `next build` ✅.

> Note: the SQLite test copy needed its `DATETIME` strings normalised to ISO-8601 because
> the Laravel SQLite driver stores `"2024-09-30 00:00:00"` and Prisma's SQLite connector
> expects ISO. This is a **test-harness artifact only** — the production target is MySQL
> (same as the app), where `DATETIME` is native and no normalisation is needed.

## What is still not a 1:1 clone

- **Exact Blade markup.** Screens are functionally equivalent, not pixel-identical: the
  generic engine renders one consistent table/form rather than the app's per-module
  hand-tuned views (charts, print/PDF layouts, CMS field editors, media picker, wizards).
- **Print/PDF.** Admit cards, ID cards, certificates, marksheets and receipts are not
  generated yet.
- **Some dashboard screens** that are not simple CRUD (settings tabs, reports builder,
  analytics, backups, bulk import/export, notifications templates/preferences, CMS
  editors) render the parity placeholder instead of a bespoke screen.
- **Business-logic depth.** Exam publish semantics, recurring payments, ledger postings,
  payroll runs, SMS campaigns, backup/restore: the schema and APIs exist, the workflow
  logic is not ported.
- **Integrations.** Payment gateways, SMS (Twilio/Vonage), mail, queues and the scheduler.
- **Runtime locale switching.** The variant honours the profile locale (bd → bn, int → en);
  the app's per-request language switch is not wired.
- **Auth depth.** Cookie session + role→permission map is in; full spatie middleware parity
  (policies, per-model abilities) is approximated.

## Regenerating the generated artifacts

| Artifact | Source | Regenerate |
|---|---|---|
| `prisma/schema.prisma` | app's migrated DB | `php artisan migrate:fresh` in the app, then Prisma introspection |
| `lib/routes.generated.ts` | `php artisan route:list --json` | re-run the route export |
| `lang/{en,bn}.ts` | `lang/{en,bn}/{dashboard,site_frontend}.php` | lang-gen script |

Generated files carry a "GENERATED — do not edit by hand" header; edit the app and regenerate.
