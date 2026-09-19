# AGENTS.md — Eskoofy Node.js Variant

This is the **`eskoofy-nodejs-app`** product folder inside the Eskoofy monorepo
(root `AGENTS.md`). It is a **phased port of `eskoofy-laravel-app`** — the Laravel app is the
reference product and the source of truth for routes, sidebar, schema, and behaviour.

## Golden rules

- **Single architecture.** One Next.js project holds the public site (RSC/SSR), the dashboard,
  and the `/api/v1/*` route handlers. There is no separate API server and no SPA client.
- **App is the reference.** When the app changes, this variant follows. The sidebar parity
  contract lives in `lib/nav.ts` and is checked by `npm run route:parity`.
- **BD/INT are build-time profiles.** All variant differences are data in `config/eskoolfy.ts`
  (+ env). Never write `if (variant === "bd")` in feature code.
- **API envelope.** Every `/api/*` JSON response is `{success, message, data[, meta]}` via
  `lib/api-response.ts`. Gateway `*/webhook/*` + `*/callback/*` paths are never rewrapped.
- **Schema names match the app.** Prisma models `@@map` to the same table names; keep the
  legacy `classes` vs `school_classes` split and the `exams`/`exam_results` column rules.

## Layout

| Path | Purpose |
|---|---|
| `app/(site)/` | Public site + login (`[...path]` catch-all covers app web routes) |
| `app/(dashboard)/` | Authenticated dashboard (`[...segments]` catch-all covers app dashboard routes) |
| `app/api/v1/` | JSON API (`[...path]` catch-all = generic REST over every table) |
| `components/` | React UI (site + dashboard) |
| `lib/routes.generated.ts` | **GENERATED** app route surface (parity contract) |
| `lib/schema.ts` / `lib/resources.ts` | Prisma DMMF metadata + path→table resolution |
| `lib/route-registry.ts` / `lib/db-query.ts` | request→route matching + generic CRUD |
| `lib/nav.ts` | app sidebar contract |
| `config/eskoolfy.ts` | BD/INT profile system |
| `prisma/schema.prisma` | **INTROSPECTED** app schema (107 tables) |
| `lang/{en,bn}.ts` | **GENERATED** app strings |
| `scripts/route-parity.ts` | Route + sidebar parity gate |

## Commands

| Command | What |
|---|---|
| `npm run dev` | Next.js dev server (http://localhost:3000) |
| `npm run build` / `npm start` | production build / serve |
| `npm run lint` | ESLint |
| `npm run typecheck` | `tsc --noEmit` |
| `npm test` | Vitest unit suite |
| `npm run route:parity` | Compare `lib/nav.ts` with the Laravel sidebar |
| `npm run prisma:generate` / `db:push` / `db:seed` | Prisma workflow |

## Gotchas

- `lib/nav.ts` is the parity contract — every sidebar change in the app must land there too,
  or `npm run route:parity` fails.
- DB-backed pages are `force-dynamic` and degrade to an empty state when the database is
  unreachable, so a fresh checkout still boots.
- See `docs/PORTING-STATUS.md` for what is ported vs pending.
