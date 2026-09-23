# Master prompt — Product documentation, Node build integration, and feature trackers

> Status: **EXECUTING** — this prompt is the single source of truth for the work below.
> Execute every task in order. Do not stop until all acceptance criteria pass.

---

## Context

Eskoofy is a 4-product monorepo plus a branding/licensing website:

| Folder | Product | Stack |
|---|---|---|
| `eskoofy-laravel-app/` | School management app (reference product) | Laravel 12 |
| `eskoofy-php-app/` | Raw PHP rewrite (shared hosting) | Raw PHP 8.2 |
| `eskoofy-wp-theme/` | WordPress theme (plugin-hybrid) | WP theme |
| `eskoofy-nodejs-app/` | Node.js clone of the app | Next.js + Prisma |
| `eskoofy-branding-website/` | Marketing site + license server (NOT a product) | Raw PHP |

BD/INT are build-time profiles produced by `build/export.sh` + `build/profiles/*` —
never separate forks.

---

## Task 1 — Documentation in every product folder

Every folder below must contain, **in its own `docs/` folder**, these four files:

1. `docs/USER-MANUAL.md` — day-to-day usage (modules, dashboards, public site, API)
2. `docs/SETUP-GUIDE.md` — local install, database, configuration, first run
3. `docs/DEPLOYMENT-GUIDE.md` — production deployment (web server, build, go-live,
   rollback, cron/workers, SSL, backups)
4. `docs/SERVER-REQUIREMENTS.md` — OS, runtime versions, PHP extensions / Node version,
   database versions, hardware sizing (CPU/RAM/disk), web server, optional services

Folders and current state:

| Folder | USER-MANUAL | SETUP-GUIDE | DEPLOYMENT-GUIDE | SERVER-REQUIREMENTS |
|---|---|---|---|---|
| `eskoofy-laravel-app/docs/` | exists | exists | **create** | **create** |
| `eskoofy-php-app/docs/` | exists | exists | **create** | **create** |
| `eskoofy-wp-theme/docs/` | exists | exists | **create** | **create** |
| `eskoofy-nodejs-app/docs/` | **create** | **create** | **create** | **create** |
| `eskoofy-branding-website/docs/` | exists | exists | **create** | **create** |

Rules:

- Match the existing house style of `SETUP-GUIDE.md` (numbered sections, tables, a
  "Related docs" section at the end, cross-links to the sibling docs).
- Every fact must be accurate for that stack (e.g. Node needs Node 20+, not PHP 8.2).
- Cross-link the four docs to each other and to the product README's Documentation list.
- Do not duplicate the whole setup guide in the deployment guide — deployment guide
  covers production concerns only; link back to setup for install steps.

## Task 2 — Integrate `eskoofy-nodejs-app` into the BD/INT export/build system

The Node variant is currently missing from `build/export.sh`. Requirements:

1. `./build/export.sh node bd` and `./build/export.sh node int` must work and emit
   `build/dist/eskoofy-nodejs-app-{bd,int}.zip` (+ raw tree under `build/artifacts/`).
2. Stage the source tree with `rsync`, excluding dev/local files: `.git/`,
   `node_modules/`, `.next/`, `.env`, `tests/`, `*.tsbuildinfo`, Docker files,
   `.dockerignore`, `.gitignore`, `tsconfig.tsbuildinfo`.
3. Apply the variant profile to `.env` (copy `.env.example` → `.env`, then merge the
   profile's `env` overrides, translating the generic `TIMEZONE` key to `APP_TIMEZONE`
   exactly like the `php` case does).
4. Variant resource handling: for `int` (when the profile strips `bn`), remove
   `lang/bn.ts` from the artifact.
5. Add an INT smoke assertion: fail if `lang/bn.ts` leaks into the int artifact.
6. Update `build/README.md` (product table + usage).
7. Update `.github/workflows/ci.yml`: export `node bd` + `node int`, verify both zips
   exist, run the bn-leak smoke check for int, and upload both artifacts.
8. Update the root `AGENTS.md` only if it contradicts the new behaviour.

Acceptance: both exports run green locally and the staged `.env` contains the correct
`ESKOOFY_VARIANT` / `APP_LOCALE` / `PAYMENT_CURRENCY` / `APP_TIMEZONE` values.

## Task 3 — Master feature tracker XLSX for the 4 products

Create **one XLSX** (openpyxl) that tracks every feature across all 4 products and
shows which features are implemented / missing / working per product.

- Location: `docs/feature-tracking/products-feature-matrix.xlsx`
- Sheets:
  1. `Feature Matrix` — one row per feature. Columns:
     `ID`, `Module`, `Feature`, `Laravel App – Implemented`, `Laravel App – Working`,
     `PHP App – Implemented`, `PHP App – Working`, `WP Theme – Implemented`,
     `WP Theme – Working`, `Node.js – Implemented`, `Node.js – Working`, `Notes`.
  2. `Summary` — per product: counts of Implemented / Partial / Not implemented, and
     Working Yes/No/Not-tested; plus a gap list (features not implemented somewhere).
  3. `Legend` — meaning of every allowed value + how the sheet was derived.
- Allowed values for **Implemented**: `Yes`, `Partial`, `No`.
- Allowed values for **Working**: `Yes`, `No`, `Not tested`, `N/A` (N/A only when
  Implemented = `No`).
- Derive the status from the repo's own evidence, not guesses: sidebar/parity docs
  (`docs/parity/product-parity.md`), `eskoofy-nodejs-app/docs/PORTING-STATUS.md` +
  `NOT-IMPLEMENTED.md`, each product's README/AGENTS/USER-MANUAL, and test/CI status.
  Record the evidence source in `Notes`.
- Cover the full surface: dashboard shell features, every sidebar module (People,
  Academics, Admissions, Finance, HR, Documents, Library, Facilities, Communications,
  Website CMS, Administration, Configuration, System), the public website, portals,
  JSON API, auth/roles, payments/gateways, SMS/email/push, i18n, PWA, PDF/print,
  backups/scheduler/queue, reports/analytics. Aim for a complete, reviewable ledger
  (~80+ feature rows).
- Freeze the header row, add autofilter, column widths, and conditional colouring
  (Implemented: green=Yes, amber=Partial, red=No; Working: green=Yes, red=No,
  grey=Not tested/N/A).

## Task 4 — Master feature tracker XLSX for the branding website

Create **one XLSX** for `eskoofy-branding-website`.

- Location: `docs/feature-tracking/branding-website-feature-matrix.xlsx`
- Sheets:
  1. `Feature Matrix` — columns: `ID`, `Area`, `Feature`, `Implemented`, `Working`,
     `Notes`.
  2. `Summary` — counts + the gap list.
  3. `Legend` — value meanings + evidence sources.
- Same `Implemented` / `Working` value vocabulary and same styling rules as Task 3.
- Cover: public pages (home, products, pricing, features, compare, about, contact,
  blog + categories, terms/privacy/refund), language switcher + geo default, support
  widget, enterprise footer, PWA/offline, customer portal (register/login/account,
  licenses/activations/renewals/payments), the `/api/v1` license API
  (activate/validate/deactivate/status/ping + product-secret + activity log),
  admin backend (customers, plans, licenses, payments, posts, post-categories,
  messages, activity, visitor log, settings, gateways, backups, cache, packages,
  client documents, email templates, push notifications, license reminders),
  gateways (manual/stripe/paypal/paddle/bkash/rocket/nagad + BDT rate),
  i18n (en/bn), security (CSRF, .htaccess hardening, secret headers), and tests.

## Task 5 — Execution & verification

- Work through Task 1 → Task 4 in order.
- Verify as you go:
  - `./build/export.sh node bd` and `./build/export.sh node int` succeed.
  - Both XLSX files open (openpyxl load) and contain the expected sheets/rows.
  - Every required docs file exists in all 5 folders (16 files total: 4 for node +
    2 each × 4 other folders).
- When everything passes, append a short "Status: DONE" note with the verification
  results to this file.

---

## Out of scope

- No feature code changes in any product (this prompt is documentation + build +
  tracking only).
- No commits unless the user explicitly asks.
- No changes to `build/profiles/profiles.php` semantics for existing products.

---

## Status: DONE

All tasks completed and verified.

**Task 1 — Docs (16/16 files)**
- `eskoofy-nodejs-app/docs/` → `USER-MANUAL.md`, `SETUP-GUIDE.md`, `SERVER-REQUIREMENTS.md`, `DEPLOYMENT-GUIDE.md`
- `eskoofy-{laravel-app,php-app,wp-theme,branding-website}/docs/` → `SERVER-REQUIREMENTS.md` + `DEPLOYMENT-GUIDE.md` each
- Each product README's Documentation section links the new docs.

**Task 2 — Node in the build/export system**
- `build/export.sh`: added `node` case (rsync excludes, `.env.example`→`.env`, profile-env merge, int strips `lang/bn.ts`, updated smoke checks).
- `build/profiles/profiles.php`: added `ESKOOFY_MINISTRY_LINKS`/`ESKOOFY_MINISTRY_BADGE` to the `bd` profile (fixes node bd overriding the `.env.example` defaults); no regression for existing products.
- `build/README.md` + `.github/workflows/ci.yml`: node export documented and CI-wired (export + artifact checks + bn-leak smoke).
- Verified: `./build/export.sh node bd` and `node int` succeed; bd zip contains `lang/bn.ts`, int zip has none; int `.env` = `ESKOOFY_VARIANT=int`, `APP_LOCALE=en`.

**Task 3 — Products feature matrix**
- `docs/feature-tracking/products-feature-matrix.xlsx` (117 features × 4 products; sheets: Feature Matrix / Summary / Legend).
- Summary snapshots: Laravel 117 Yes (100%); PHP 110 Yes + 4 Partial + 3 No; Theme 97 Yes + 10 Partial + 10 No; Node 88 Yes + 16 Partial + 13 No.
- Notable gaps surfaced: onboarding banner, dashboard write throttle, year-end promotion, student-role permission matrix, advanced exam publish semantics, double-entry auto-postings, live gateways, recurrences/cron, SMS carrier delivery, CMS settings/global labels, PDF generation, scheduled jobs, queue, push — all marked `Partial`/`No` with evidence notes.

**Task 4 — Branding-website matrix**
- `docs/feature-tracking/branding-website-feature-matrix.xlsx` (87 features; sheets: Feature Matrix / Summary / Legend).
- All features implemented (87/87); live integrations (Stripe/PayPal/Paddle/bKash/Rocket/Nagad drivers + gateway webhooks) marked `Not tested` pending real credentials.

**Task 5 — Verification**
- XLSX: both load via openpyxl with the expected sheets + row counts (products 117, branding 87); no duplicate feature rows.
- Docs: all 16 files present (checked via `ls`).
- Node exports: re-run bd + int, green (bn file present in bd / absent in int).
- Fixed a stray backtick in `eskoofy-nodejs-app/docs/USER-MANUAL.md` §4.
- Generator scripts committed alongside the XLSX for reproducibility: `docs/feature-tracking/build-{product,branding}-matrix.py`.
- No commits made (per out-of-scope rule).
