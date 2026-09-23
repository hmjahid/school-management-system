# Eskoofy Node.js Variant — Setup Guide

> Product: `eskoofy-nodejs-app` (Next.js 15 + React 19 + TypeScript + Prisma) · This guide
> covers install, database, configuration and first run. Deployment lives in
> [`DEPLOYMENT-GUIDE.md`](./DEPLOYMENT-GUIDE.md); sizing in
> [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md).

- Day-to-day usage: [`USER-MANUAL.md`](./USER-MANUAL.md)
- Port ledger: [`PORTING-STATUS.md`](./PORTING-STATUS.md) · [`NOT-IMPLEMENTED.md`](./NOT-IMPLEMENTED.md)

---

## 1. Prerequisites

| Requirement | Version | Why |
|---|---|---|
| Node.js | **20.9+** (`engines` requires `>=20.9.0`) | Next.js 15 runtime + build |
| npm | 10+ | Dependency management |
| MySQL / MariaDB | 8.x / 10.x | Same schema as the Laravel app (107 tables) |
| Docker (optional) | Engine + Compose v2 | Local DB container (`docker-compose.yml`) |

> The schema is **introspected from the Laravel app** — the database is the shared contract
> with `eskoofy-laravel-app`. Prisma expects a MySQL/MariaDB (the SQLite harness is for tests).

---

## 2. Quick start (local)

```bash
cd eskoofy-nodejs-app

# 1. Local database (optional but recommended) — shared esk-mariadb on host port 3307:
docker compose up -d db

# 2. Environment
cp .env.example .env
#    edit DATABASE_URL + AUTH_SECRET (defaults point at the container: 127.0.0.1:3307/eskoofy_node)

# 3. Install + generate Prisma client
npm install                 # postinstall runs `prisma generate`

# 4. Schema + seed
npm run prisma:push         # creates the 107 tables (or `npm run prisma:migrate`)
npm run db:seed             # admin@school.com / ChangeMe!2026$Tr0ng (same as the app)

# 5. Run
npm run dev                 # http://localhost:3000
```

Open **http://localhost:3000** → `/login` → `/dashboard`.

---

## 3. Database

- Schema lives in `prisma/schema.prisma` — **GENERATED** (introspected) from the app's
  migrated database; edit the app, then re-introspect. Do not hand-edit.
- `npm run prisma:push` syncs the schema to the DB (no migration files needed for dev).
- `npm run prisma:migrate` uses `prisma migrate dev` if you prefer migration files.
- `npm run db:seed` seeds the admin (`admin@school.com` / `ChangeMe!2026$Tr0ng`) matching the
  app's own seed account.

### Local DB (Docker)

`docker-compose.yml` starts **`esk-mariadb`** (MariaDB 11) on host port **3307**, database
`eskoofy_node`, user `esk`/`eskpw`. Data persists in the `esk-mariadb-data` volume.

```bash
docker compose up -d db     # start
docker compose down         # stop (data persists)
```

> If the app refuses DB connections on a fresh checkout, the `db` container is almost always
> the cause — start it first.

---

## 4. Configuration (`.env`)

| Key | Purpose |
|---|---|
| `NODE_ENV` | `development` / `production` |
| `APP_NAME`, `APP_URL` | App identity; `APP_URL` should match the served URL |
| `APP_TIMEZONE` | `Asia/Dhaka` (`bd`) or `UTC` (`int`) |
| `ESKOOFY_VARIANT` | `bd` or `int` — build-time profile (data, never `if (variant === "bd")` branching) |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` | `bn`/`en` for `bd`; `en`/`en` for `int` |
| `DATABASE_URL` | Prisma connection string (`mysql://user:pass@host:port/db`) |
| `AUTH_SECRET` | Cookie-session JWT signing secret — **long random string, keep private** |
| `SESSION_COOKIE` / `SESSION_TTL_HOURS` | Cookie name / lifetime (24h default) |
| `ESKOOFY_MINISTRY_LINKS` / `ESKOOFY_MINISTRY_BADGE` | BD government/ministry link + badge flags |
| `PAYMENT_CURRENCY` | `BDT` (`bd`) / `USD` (`int`) |
| `SMS_DRIVER` | `log` (default) — carrier drivers not yet wired |

Variant profiles mirror `config/eskoolfy.ts` and the monorepo's `build/profiles/profiles.php`.

---

## 5. Running the checks

| Command | What |
|---|---|
| `npm run dev` | Next.js dev server (http://localhost:3000) |
| `npm run build` / `npm start` | Production build / serve |
| `npm run lint` | ESLint |
| `npm run typecheck` | `tsc --noEmit` |
| `npm test` | Vitest unit suite |
| `npm run route:parity` | App ↔ node sidebar/route parity gate |
| `npm run prisma:generate` | Generate the Prisma client |
| `npm run prisma:push` / `prisma:migrate` | Sync / migrate the schema |

---

## 6. Troubleshooting

| Symptom | Fix |
|---|---|
| DB connection refused | Start the `db` container (`docker compose up -d db`) and confirm `DATABASE_URL` host/port |
| Prisma client errors | `npm run prisma:generate` (postinstall normally does this) |
| Stale route registry | `npm run route:parity` fails → regenerate `lib/routes.generated.ts` from the app (`php artisan route:list --json`) |
| Login fails | Re-run `npm run db:seed` (admin `admin@school.com` / `ChangeMe!2026$Tr0ng`) |
| Build errors about native engines | Reinstall on the target platform (`npm ci` — Prisma engines are platform-specific) |

---

## Related docs

- [`USER-MANUAL.md`](./USER-MANUAL.md)
- [`DEPLOYMENT-GUIDE.md`](./DEPLOYMENT-GUIDE.md)
- [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md)
- [`../README.md`](../README.md) · [`../AGENTS.md`](../AGENTS.md)
- [`PORTING-STATUS.md`](./PORTING-STATUS.md) · [`NOT-IMPLEMENTED.md`](./NOT-IMPLEMENTED.md)
- [`../../docs/guides/DEVELOPMENT.md`](../../docs/guides/DEVELOPMENT.md) — monorepo dev guide