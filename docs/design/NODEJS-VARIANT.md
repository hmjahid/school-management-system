# Node.js Variant — Feasibility & Implementation

> Date: September 2026 · Status: **implemented** — Option A (Next.js single app) shipped as
> **`eskoofy-nodejs-app/`** · Live ledger:
> [`../../eskoofy-nodejs-app/docs/PORTING-STATUS.md`](../../eskoofy-nodejs-app/docs/PORTING-STATUS.md)
>
> **Terminology reminder (important):** The `eskoofy-branding-website/` folder is **NOT a product** —
> it is our **branding website + license server**. Eskoofy **ships 4 products**
> (`eskoofy-laravel-app`, `eskoofy-php-app`, `eskoofy-wp-theme`, `eskoofy-nodejs-app`). The website
> only *markets* and *sells* the products, so it is a parity *destination for sales copy* — never a
> product itself.
>
> **What shipped:** the folder is `eskoofy-nodejs-app/` (not `eskoofy-node`); the schema port is
> 107 Prisma models (same table/column names as the app); the route surface (585 routes), the
> sidebar (95 keys) and the strings (742 × en/bn) are parity-gated; the dashboard has generic
> CRUD for all resources and the public site has real pages. The remaining work is
> pixel-level Blade parity, print/PDF, non-CRUD admin screens, business-logic depth and
> integrations — see the porting ledger.

---

## 1. Is it possible?

Yes. A full-featured school management system is CRUD + workflow heavy (students,
attendance, exams, results, fees, admissions, payroll, library, transport, hostels,
notifications, certificates…) — all of which Node.js handles comfortably. Both the
frontend **and** backend can be Node.js.

The hard part is **not feasibility** — it is **maintaining feature parity across 4 products
permanently** (the existing app/php/theme parity rules extend to the Node product).

## 2. Recommended stack

> **Preferred shape: a single-architecture Node app**, mirroring the Laravel variant —
> one framework, one codebase, server-rendered frontend + backend API, one `npm run dev`.
> A split two-app architecture (API + SPA) is *possible* but is NOT the Laravel-style shape.

### Option A — Next.js single app — **recommended**

Next.js is "frontend + backend in one project", the modern equivalent of Laravel+Blade:

| Laravel (existing) | Node.js equivalent |
|---|---|
| Controllers | Route Handlers / API routes (in the same app) |
| Eloquent models | Prisma or Drizzle models |
| Blade views → public site + dashboard (server-rendered) | React Server Components (SSR + RSC) |
| Middleware | Next.js middleware / server guards |
| `config/eskoolfy.php` + `build/profiles/*` | `config/eskoolfy.ts` (keep BD/INT build-time profile rule) |
| `route:list` parity check | App Router route map parity checker (same principle) |
| `vite` dev server | `next dev` / built-in server |
| `composer test` / PHPUnit | Vitest / Playwright |
| PWA service worker | same `public/sw.js` pattern — near-copy |

- One project: `eskoofy-nodejs-app/` (App Router + Prisma + Tailwind). No separate API server.
- Public-site SEO via SSR matches current products.
- Layout (as shipped):
  ```
  eskoofy-nodejs-app/
    app/(site)/     public site + login        app/(dashboard)/  dashboard
    app/api/v1/     JSON API route handlers
    prisma/         schema.prisma (port of the app's 107-table MySQL schema)
    lib/            auth, permissions, api-response, i18n, nav, schema, db-query
    components/     React/TS UI (ui primitives + dashboard + site)
    config/         eskoolfy.ts (bd/int profiles)
  ```

### Option B — AdonisJS single app (paradigm match to Laravel)

- The literal "Laravel for Node.js": one framework with **Edge template engine**
  (reads like Blade: `@if`, `@each`, components), **Lucid ORM** (Eloquent-like), sessions,
  auth, CSRF, validation, middleware.
- Vite built-in → Tailwind + vanilla JS works just like the current Laravel fold.
- Smallest mental-mapping cost for the existing team; less mainstream than Next.js.

### Option C — NestJS API + Next.js web (two apps)

- Split architecture: an API server + a web frontend. More moving parts than the Laravel
  variant. Only preferred if you want a separately deployable API consumed by future
  clients (native apps, third parties).

## 3. What maps well / what bites

| Area | Status |
|---|---|
| 100+ table MySQL schema | ✅ **done** — 107 Prisma models, same table/column names, same DB |
| Payments (bKash/Rocket/Nagad/Stripe/PayPal/Paddle) | All REST APIs with Node SDKs — clean ports |
| SMS (Twilio/Vonage) | Node SDKs |
| PDFs (certificates, ID cards, admit cards, receipts) | pdfkit / puppeteer |
| PWA / offline | service worker already a JS pattern — near-copy |
| 42 dashboard modules, 585 routes, 213 dashboard views | ✅ route surface parity-gated; dashboard CRUD generic for all resources; ⚠️ per-view Blade fidelity still pending |

## 4. Parity rules that apply (from `docs/design/FEATURE-PROPAGATION.md`)

- Default scope for any feature change = **ALL products** (`eskoofy-laravel-app`, `eskoofy-php-app`,
  `eskoofy-wp-theme`, `eskoofy-nodejs-app`) + sales/marketing copy on the
  **branding website** (`eskoofy-branding-website`).
- Confirmation gate before cross-product implementation
  (`build/propagate/propagate-feature.sh`).
- BD/INT stays **build-time profiles** (`config/eskoolfy.ts` + `build/profiles/*`), never
  hardcoded `if (variant)` branching.
- Route/view parity checkers: app ↔ node (and existing app ↔ php, app ↔ theme) must be
  asserted in CI.

## 5. Suggested path (what was done)

1. **Gated phase**: added as `WORKPLAN.md` **Phase 9** (see that file for the task table).
2. **Feature matrix first**: ✅ the app's `route:list` (585 routes) is checked in as
   `lib/routes.generated.ts` — the port's acceptance checklist.
3. **Backend/API first**: ✅ Prisma + MySQL against the same schema; `/api/v1` uses the app's
   `{success,message,data[,meta]}` envelope, with generic REST over every table.
4. **License contract**: the branding site's `/api/v1/licenses/*` is untouched and reusable.
5. **Then the dashboard, then the public site**: ✅ dashboard chrome + generic CRUD, then 19 real
   public pages.
6. **Extend propagation maps**: ✅ `eskoofy-nodejs-app` rows added to
   `docs/design/FEATURE-PROPAGATION.md`, the `build/propagate/propagate-feature.sh` product list,
   the root `AGENTS.md`/`README.md`, and CI (`.github/workflows/ci.yml`).

## 6. Honest caveats

- A full port is **large** (comparable to one of the earlier product ports) — plan months.
- Every feature change now touches **4 products + website copy** — the propagation gate is
  now more important, not less.
- **Strategic question to decide up front:** is Node the *int-only flagship* (new target
  market, modern cloud) while php/theme are maintenance-only? Or a pure parity port? That
  decision changes scoping, staffing, and onboarding a lot.
- Team skills: if there is no TypeScript/Node experience, budget for a learning ramp.

## 7. Open questions — resolved / still open

- [x] Node variant folder: **`eskoofy-nodejs-app`** (not `eskoofy-node`).
- [x] Parity clone of the app (a single app; the int/bd profile rule is preserved via
      `config/eskoolfy.ts`).
- [x] **Same DB schema** — Prisma models map to the app's exact tables, so data can be shared.
- [ ] Ownership: same team as the php/theme ports, or a TS hire?
- [ ] Does the branding website get a new `/products/node` page? (Sales copy is out of scope
      until the clone reaches pixel parity.)