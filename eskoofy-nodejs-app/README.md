# Eskoofy — Node.js Variant (`eskoofy-nodejs-app`)

A **single-architecture Node.js edition** of the Eskoofy school management system, built as a
phased port of the Laravel reference product (`eskoofy-laravel-app`).

One project holds everything — the public marketing site (server-rendered React), the admin
dashboard, and the JSON API. There is no separate API server and no SPA client.

## Stack

| Concern | Choice |
|---|---|
| Framework | Next.js (App Router) + React 19 + TypeScript |
| Data | Prisma ORM on MySQL (same schema/table names as the app) |
| Styling | Tailwind CSS v4 utility classes |
| Auth | Cookie session JWT (`jose`) + bcrypt |
| Validation | zod |
| Tests | Vitest |

## Quick start

```bash
cp .env.example .env
# set DATABASE_URL + AUTH_SECRET in .env

npm install
npm run prisma:generate
npm run prisma:migrate    # or: npm run prisma:push (creates the 107 tables)
npm run db:seed           # admin@school.com / ChangeMe!2026$Tr0ng (same as the app)

npm run dev               # http://localhost:3000
```

> **Local database:** the default `DATABASE_URL` in `.env.example` points at the
> `esk-mariadb` container shipped in `docker-compose.yml` (host port **3307**, user
> `esk`/`eskpw`, database `eskoofy_node`). Start it with `docker compose up -d db`;
> stop it with `docker compose down` (data persists in the `esk-mariadb-data` volume).
> If the DB refuses connections this is almost always the cause.

## Scripts

| Command | What |
|---|---|
| `npm run dev` | Dev server |
| `npm run build` / `npm start` | Production build / serve |
| `npm run lint` | ESLint |
| `npm run typecheck` | `tsc --noEmit` |
| `npm test` | Vitest unit suite |
| `npm run route:parity` | App↔node sidebar parity check |

## Architecture

The variant reproduces the app **schema-first and route-first**: generated artifacts mirror
the app, and a generic engine serves them.

```
lib/routes.generated.ts   the app's route:list (585 routes)      — generated
prisma/schema.prisma      the app's tables (107 models)          — introspected
lang/{en,bn}.ts           the app's strings (742 keys x 2)       — generated
lib/schema.ts             Prisma DMMF metadata per table
lib/resources.ts          path segment -> table
lib/route-registry.ts     request -> app route
lib/resource-route.ts     dashboard path -> CRUD mode + record
lib/db-query.ts           generic list/get/create/update/delete + FK options
lib/nav.ts                app sidebar contract
components/ui/*           card / badge / button / table / page-header / empty-state
components/dashboard/*    sidebar (accordion) + topbar + resource table/form/detail
app/(dashboard)/dashboard/[...segments]/   dashboard catch-all (CRUD + placeholder)
app/(site)/**             real public pages (home, news, admissions, results, …)
app/(site)/[...path]/     public-site catch-all
app/api/v1/[...path]/     generic REST over every table
config/eskoolfy.ts        BD/INT profile system (data, not branching)
scripts/route-parity.ts   route + sidebar parity gate
```

## Parity with the Laravel app

The Laravel app is the source of truth. `npm run route:parity` runs
`php artisan route:list --json` in the reference app and fails if the generated route
registry has drifted or if the app's sidebar renders a key `lib/nav.ts` does not list.

**Status:** the clone covers the **schema, route surface, sidebar, i18n, API envelope,
dashboard chrome and CRUD** — every dashboard resource has working list/show/create/edit
screens, and the public site has 19 real pages wired to the app's tables. Verified end to
end against the app's own migrated database (0 Prisma errors). Remaining: pixel-identical
Blade markup, print/PDF, non-CRUD admin screens, business-logic depth and integrations. See
`docs/PORTING-STATUS.md` for the full ledger.

## Variants

`bd` = Bangladeshi (Bengali+English, bKash/Rocket/Nagad, BDT, Asia/Dhaka).
`int` = International (English-only, Stripe/PayPal/Paddle, USD, UTC).
Selected by `ESKOOFY_VARIANT`; every difference is data in `config/eskoolfy.ts`.
