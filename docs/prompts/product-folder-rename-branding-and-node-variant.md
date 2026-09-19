# Prompt — Product folder rename + branding-site upgrades + Node.js variant

> Status: **to execute** · Scope: monorepo root, `eskoofy-branding-website/`, new `eskoofy-nodejs-app/`
> Companion docs: `docs/design/VARIANT-BLUEPRINT.md` (phased roadmap),
> `docs/design/NODEJS-VARIANT.md` (stack feasibility), `docs/design/BRANDING-SITE-IMPROVEMENTS.md`.
> Reference product (source of truth): `eskoofy-laravel-app/`.

Work top to bottom and continue until every task is finished. Follow the repo conventions
in the root `AGENTS.md`, `eskoofy-laravel-app/AGENTS.md`, and `eskoofy-branding-website/AGENTS.md`.
The `bd` profile must stay byte-for-byte today's behaviour unless a task explicitly changes it.

---

## Task 1 — Rename the four product folders and update every reference

Rename (use `git mv`, preserve history):

| Old | New |
|---|---|
| `eskoofy-app` | `eskoofy-laravel-app` |
| `eskoofy-php` | `eskoofy-php-app` |
| `eskoofy-theme` | `eskoofy-wp-theme` |
| `eskoofy-website` | `eskoofy-branding-website` |

Then update **every** reference in tracked files (root docs, `build/`, `.github/workflows/`,
`docker/`, per-product `AGENTS.md`/`README.md`/docs, `.gitignore`, product code comments).
Notes:
- Do **not** edit `eskoofy-laravel-app/archive/**` (frozen legacy copies).
- Keep the `export.sh` CLI product keys (`app|php|theme|website`) — only folder paths change.
- Artifact names in `build/dist/` and CI must track the new folder names
  (e.g. `eskoofy-laravel-app-bd.zip`), derived consistently from the exported folder.
- Do not create names like `eskoofy-php-app-app` (guard against double replacement).

**Gate:** `bash -n build/export.sh` + `bash -n build/propagate/propagate-feature.sh` clean;
a website export produces `build/dist/eskoofy-branding-website-int.zip`.

---

## Task 2 — Node.js single-architecture variant (`eskoofy-nodejs-app`)

Read `docs/design/VARIANT-BLUEPRINT.md` and `docs/design/NODEJS-VARIANT.md` first.
Build the **4th product**: a single-architecture Node.js app that mirrors the Laravel app
(one framework, one codebase, server-rendered frontend + backend — **no split API/SPA**).

Chosen shape: **Next.js (App Router) + TypeScript + Prisma + Tailwind**, per Option A.

The variant is a **phased port of `eskoofy-laravel-app` as the reference product**, not a
from-scratch redesign. Deliver Phase 0–3 solidly and document what remains:

- **Phase 0 — scaffold & gate:** own `AGENTS.md`, `README.md`, `.env.example`, `package.json`,
  `tsconfig.json`, `next.config.*`, Tailwind, Vitest, and the BD/INT profile system in
  `config/eskoolfy.ts` (config/data only — never `if (variant)` branching).
- **Phase 1 — schema & data model:** port the app's MySQL schema to `prisma/schema.prisma`
  (keep the same table names; note the intentional legacy `classes` vs `school_classes` split).
- **Phase 2 — auth, roles, permissions:** login/logout, spatie-equivalent permissions,
  session guard.
- **Phase 3 — dashboard + sidebar parity:** sidebar groups/items/order literally mirroring
  `eskoofy-laravel-app/resources/views/partials/dashboard/sidebar.blade.php`; dashboard home
  blocks (stat cards, charts, quick actions, setup banner).
- **Phase 4 (start) — core modules:** students, teachers, classes/batches/sessions,
  attendance, fees/payments, exams/results — list + basic CRUD.
- **API parity:** `/api/v1/*` envelope `{success,message,data[,meta]}`.
- **Parity tooling:** a route-map parity checker (app `route:list` ↔ Node route map) and a
  test suite; wire the product into `build/` + propagation docs.
- Record unported modules/routes in `eskoofy-nodejs-app/docs/PORTING-STATUS.md`.

**Gate:** `npm run lint`, `npm run typecheck`, `npm test` pass; the app boots with `npm run dev`
(dev server stopped afterwards).

---

## Task 3 — Branding website: customer support widget (frontend)

Add a professional, self-contained support widget to `eskoofy-branding-website/` public pages:
a floating launcher opening a panel with a welcome message, quick links (Help centre,
Pricing FAQ, Book a demo), contact channels (email / phone / WhatsApp), and a deep link to
`/contact`. Accessible (keyboard, ARIA), dismissible with `localStorage`, zero new runtime
dependencies, Tailwind + the existing site JS conventions. Suggest the recommended approach
in a short doc (`docs/design/SUPPORT-WIDGET.md`).

---

## Task 4 — Branding website: visitor log in the admin dashboard

Add a professional **visitor log** under `/admin`:
- `visitors` table in `database/schema.sql` (+ live DB), a `Visitor` model, a small
  middleware/service that records page views (IP, user agent, path, referrer, country if
  cheaply available, timestamp) — skip admin/static/asset requests, honour a
  `visitor_logging` setting, and respect DNT.
- `/admin/visitors` page: KPIs (today / 7-day / 30-day, unique visitors), a trend chart using
  the existing lightweight SVG chart helper, a filterable/paginated table, and a top-pages
  breakdown. Register the route + admin sidebar entry, add lang strings (en + bn), and cover
  it with tests.

---

## Task 5 — Branding website: richer contact page

Expand `/contact`: real email address(es) (sales + support), phone number(s), WhatsApp,
office address + hours, a map/embed block, per-topic routing (sales/support/billing),
response-time expectations, and a small FAQ. Keep the existing form working; add all strings
to `lang/en.php` + `lang/bn.php`.

---

## Task 6 — Branding website: enterprise-grade footer

Rebuild the footer into an enterprise-grade, responsive multi-column footer: brand block with
logo + short positioning line, grouped link columns (Product / Solutions / Company /
Resources / Legal), contact block, newsletter or status/trust strip (compliance badges),
social icons, language switcher, and a bottom bar with copyright, legal links, and
"not affiliated" clarity where relevant. All strings in `lang/en.php` + `lang/bn.php`;
dark-mode aware; accessible landmarks and headings.

---

## Verification (every task)

| Product | Command |
|---|---|
| branding website | `cd eskoofy-branding-website && composer test`; `php -l` each touched file |
| laravel app | `cd eskoofy-laravel-app && composer test` (unchanged behaviour) |
| node variant | `cd eskoofy-nodejs-app && npm run lint && npm run typecheck && npm test` |
| build box | `bash -n build/export.sh`; `./build/export.sh website int` |

Finish with an item-by-item summary (task → files → evidence), and flag anything not
completed and why.
