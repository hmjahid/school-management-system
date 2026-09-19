# Node.js Variant — Feasibility & Implementation Proposal

> Date: September 2026 · Status: **proposal / not started** · Owner: TBD
>
> **Terminology reminder (important):** The `eskoofy-branding-website/` folder is **NOT a product** —
> it is our **branding website + license server**. Eskoofy **ships 3 products**
> (`eskoofy-laravel-app`, `eskoofy-php-app`, `eskoofy-wp-theme`). A Node.js variant would become the
> **4th product** (`eskoofy-node`). The website only *markets* and *sells* the products, so
> it is a parity *destination for sales copy* — never a product itself.

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

- One project: `eskoofy-node/` (App Router + Prisma + Tailwind). No separate API server.
- Public-site SEO via SSR matches current products.
- Layout:
  ```
  eskoofy-node/
    app/            Next.js App Router (pages + route handlers tie to https://)
    prisma/         schema.prisma (port of the 98-table MySQL schema)
    lib/            services, auth, middleware helpers
    components/     React/TS UI (dashboard + site)
    config/         bd/int profiles + env maps
    public/         sw.js, offline.html, manifest.json
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
| 98-table MySQL schema | Port via Prisma schema; keep the same DB so data could be shared/misd during migration |
| Payments (bKash/Rocket/Nagad/Stripe/PayPal/Paddle) | All REST APIs with Node SDKs — clean ports |
| SMS (Twilio/Vonage) | Node SDKs |
| PDFs (certificates, ID cards, admit cards, receipts) | pdfkit / puppeteer |
| PWA / offline | service worker already a JS pattern — near-copy |
| 42 dashboard modules, 200+ routes, 200+ views | **The real effort** — weeks-to-months of porting, then permanent parity upkeep |

## 4. Parity rules that apply (from `docs/design/FEATURE-PROPAGATION.md`)

- Default scope for any feature change = **ALL products** (`eskoofy-laravel-app`, `eskoofy-php-app`,
  `eskoofy-wp-theme`, **and once shipped `eskoofy-node`**) + sales/marketing copy on the
  **branding website** (`eskoofy-branding-website`).
- Confirmation gate before cross-product implementation
  (`build/propagate/propagate-feature.sh`).
- BD/INT stays **build-time profiles** (`config/eskoolfy.ts` + `build/profiles/*`), never
  hardcoded `if (variant)` branching.
- Route/view parity checkers: app ↔ node (and existing app ↔ php, app ↔ theme) must be
  asserted in CI.

## 5. Suggested path

1. **Add a gated phase** to `WORKPLAN.md` (e.g. Phase 10, gated like Phase 6 was) — do not
   start until the gate is approved.
2. **Feature matrix first**: export module × route × view × permission matrix from
   `eskoofy-laravel-app` (`route:list`, `docs/design/FEATURE-PROPAGATION.md` maps). This is the Node
   port's acceptance checklist.
3. **Backend/API first**: in the chosen single app (Option A/B) — Prisma + MySQL against the
   same schema; reach API parity with `eskoofy-laravel-app` `/api/v1` (same envelope + middleware
   semantics) before building UI.
4. **Reuse the license contract** from `eskoofy-branding-website` (`/api/v1/licenses/*`) so the Node
   product is monetisable via the branding site's license server from day one.
5. **Then the dashboard**, then the public site (UI parity bar is highest on dashboard).
6. **Extend propagation maps**: add `eskoofy-node` rows to `docs/design/FEATURE-PROPAGATION.md`
   and update `build/propagate/propagate-feature.sh` product list.

## 6. Honest caveats

- A full port is **large** (comparable to one of the earlier product ports) — plan months.
- Every feature change now touches **4 products + website copy** — the propagation gate is
  now more important, not less.
- **Strategic question to decide up front:** is Node the *int-only flagship* (new target
  market, modern cloud) while php/theme are maintenance-only? Or a pure parity port? That
  decision changes scoping, staffing, and onboarding a lot.
- Team skills: if there is no TypeScript/Node experience, budget for a learning ramp.

## 7. Open questions for the owner

- [ ] Node variant name/folder: `eskoofy-node`?
- [ ] Is Node the flagship (int) or a parity clone of an existing variant?
- [ ] Same DB schema (shared data possible) or independent schema?
- [ ] Who owns it — same team as the php/theme ports, or a TS hire?
- [ ] Does the branding website get a new `/products/node` (or rename) page?