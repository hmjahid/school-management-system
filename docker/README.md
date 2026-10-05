# Eskoofy — Docker dev harnesses

One dev harness per product, each with **its own Compose project**, **globally
unique container names** and **non-overlapping host ports**, so any subset — or
all of them — can run at the same time.

All harnesses **bind-mount the product code directly into the container**, so
edits you make in `eskoofy-*/` apply on the next page refresh / hot-reload via
the container's own dev server (no image rebuild to see changes).

| Product | Compose file | Project / containers | URL | DB host port |
|---|---|---|---|---|
| WP theme | `docker/theme-test/` | `eskoofy-wp-theme-test-{db,wp,setup}-1` | <http://localhost:8080> | — (internal) |
| Laravel | `docker/laravel-dev/` | `esk-laravel-web`, `esk-laravel-assets`, `esk-laravel-db` | <http://localhost:8090> (Vite HMR :5173) | `33068` |
| Node.js | `docker/node-dev/` | `esk-node-app`, `esk-node-db` | <http://localhost:3000> | — (internal) |
| Raw PHP | `docker/php-dev/` | `esk-php-app`, `esk-php-db` | <http://localhost:8051> | `33069` |
| Branding website | `docker/website-dev/` | `esk-website-app`, `esk-website-db` | <http://localhost:8052> | `33070` |

## Commands

The single entry point is `docker/dev.sh` (each product also works standalone
via `docker compose -f docker/<product>-dev/docker-compose.yml`).

```bash
./docker/dev.sh up                # start ALL products (docker-compose up -d)
./docker/dev.sh up laravel        # start one product: theme | laravel | node | php | website
./docker/dev.sh up node --build   # rebuild that product's image first
./docker/dev.sh build node        # build image(s) without starting
./docker/dev.sh down              # stop all products (keeps database data)
./docker/dev.sh down node         # stop one product
./docker/dev.sh down --volumes    # stop all AND delete database data (fresh start)
./docker/dev.sh restart website   # restart, re-running the entrypoint (schema, deps)
./docker/dev.sh verify-live       # prove source edits are live, per product
./docker/dev.sh logs [-f] node    # follow logs of one product (default: all)
./docker/dev.sh ps [node]         # container status (default: all)
./docker/dev.sh seed laravel      # seed database after first start
./docker/dev.sh seed all          #  laravel | node | php | website
./docker/dev.sh exec node sh      # open a shell in the product's app container
./docker/dev.sh urls              # print the URL of every product
./docker/dev.sh help
```

> First run pulls/builds images, so `./docker/dev.sh up` may take a while.
> Containers start as the calling `UID:GID` so bind-mounted files never become
> root-owned on the host.

## How code changes reach each product

The contract is: **edit a file in `eskoofy-*/`, refresh the browser, see the
change.** No rebuild, no restart. That holds because three separate mechanisms
have to work, and they fail for unrelated reasons — so they are specified and
tested separately instead of being assumed.

| Product | Live reload mechanism |
|---|---|
| WP theme | `eskoofy-wp-theme` bind-mounted into WordPress; PHP re-reads it per request |
| Laravel | `eskoofy-laravel-app` bind-mounted; `php -S` re-reads PHP per request + **Vite dev server (:5173) for CSS/JS hot-reload**; migrations auto-run on boot, demo data auto-seeded on a fresh DB, and **`RolePermissionSeeder` re-runs every boot** (so a new `@can(...)` permission in the sidebar appears on an existing DB volume) |
| Node.js | `eskoofy-nodejs-app` bind-mounted; **Next.js dev server hot-reload** (`node_modules`/`.next` live in container volumes). The entrypoint **watches `prisma/schema.prisma` and, on change, regenerates the Prisma client, re-pushes the DDL and restarts the dev server** so the new models are actually loaded — see below |
| Raw PHP | `eskoofy-php-app` bind-mounted; PHP built-in server re-reads per request |
| Branding website | `eskoofy-branding-website` bind-mounted; PHP built-in server re-reads per request |

### The three mechanisms

1. **Bind mount, not a copy.** Every product folder is mounted read-write at
   `/var/www` (or `/app`, or the WordPress themes dir). If a source edit is not
   visible in the container, the mount is broken, not the app.
2. **`no-store` on static assets.** `docker/static-dev.php`, shared by all three
   `php -S` routers, serves CSS/JS/images with `Cache-Control: no-store`.

   This one is easy to get wrong and it is the usual reason a live-reload
   harness looks broken. A router that answers a static request with
   `return false` makes PHP's built-in server emit a response with **no
   `Cache-Control`, no `Expires`, no `ETag` and no `Last-Modified`** — and
   `header()` calls before `return false` are silently discarded (verified
   against `php:8.3-cli`). A response like that is precisely the case where
   browsers and proxies fall back to *heuristic* caching (RFC 9111 §4.2.2: 10%
   of the time since `Last-Modified`). The container re-reads your file
   correctly and the client hands you the previous stylesheet. No devtools
   cache-bypass needed to explain it — the response never said how long it was
   good for.
3. **Re-read or recompile.** PHP re-reads a template on every request, so a
   template edit shows up in the very next response. Next.js and Vite hold
   compiled modules in memory and recompile on change.

### `verify-live` — prove it, don't assume it

```bash
./docker/dev.sh verify-live            # every product
./docker/dev.sh verify-live website    # one product
```

It checks each mechanism independently and reports `SKIP` (not `FAIL`) for
products that are not running. For PHP products it appends a harmless HTML
comment to the base layout, curls the page, asserts the marker came back, and
reverts the file — so it fails if a template edit is not live. For Node it
touches a source file and asserts `.next` actually rebuilt, because a
recompile is the only observable that proves the dev server is still watching.

### What still needs `restart`

```bash
./docker/dev.sh restart <product>       # or <product> --volumes for a clean DB
```

A plain `up` does **not** re-run an entrypoint on an already-running service, so
these boot-time-only items need an explicit `restart`:

* **`schema.sql` / migrations** (Raw PHP, website, Laravel). The MariaDB init
  script runs **only on a fresh (empty) data volume**, and the entrypoints then
  re-apply the schema on every boot — which a `restart` triggers.
  `--volumes` gives you a clean rebuild.
* **Dependency changes** — `composer.json`, `package.json` (the Laravel and Node
  entrypoints install only when `vendor/` / `node_modules` is missing).

The **Node** harness needs neither: it watches `prisma/schema.prisma` while
running. `ESK_WATCH_SCHEMA=0` restores the old boot-time-only behaviour, and
`ESK_WATCH_INTERVAL` (default `3`) tunes the poll.

### Laravel assets: `public/hot` is the switch

Blade's `@vite` resolves in this order: `public/hot` → the dev server on :5173,
otherwise `public/build/manifest.json` → those prebuilt files, otherwise it
throws. `public/build` is gitignored but is routinely left over from an
`npm run build`, and it **silently wins whenever the dev server is not running**,
so CSS/JS edits appear to do nothing while Blade serves a bundle that predates
them. `docker/laravel-dev/assets.sh` therefore owns that file: it deletes any
stale `public/hot` on entry, lets the plugin recreate it, and removes it again on
exit so a killed dev server cannot leave Blade pointing at a dead port. The
`web` entrypoint prints a loud warning if it finds a `manifest.json` with no
`hot`.

## Seeding

* **Laravel** — migrations run automatically on container boot; on a fresh DB
  the documented demo accounts are auto-seeded (`docs/operations/
  DEMO-CREDENTIALS.md`). Re-seed anytime with `./docker/dev.sh seed laravel`.
* **Node** — after `up`, run `./docker/dev.sh seed node` once
  (`prisma db push` + `prisma/seed.ts`).
* **PHP** — schema is imported automatically on a fresh DB
  (`eskoofy-php-app/database/schema.sql` via MariaDB init scripts); then run
  `./docker/dev.sh seed php` once for the demo accounts.
* **WP theme** — the `setup` service installs WordPress, activates the theme
  and seeds demo pages automatically (requires `WP_ADMIN_PASSWORD`; `dev.sh`
  falls back to a dev default unless you export your own).
* **Branding website** — schema + admin/demo content are imported automatically
  on a fresh DB (`eskoofy-branding-website/database/schema.sql` via MariaDB
  init scripts). Admin login: `admin@eskoofy.com` / `admin123`.

## Port map (all five products at once)

| Port | Service |
|---|---|
| 8080 | WP theme (Apache) |
| 8090 | Laravel app (`artisan serve`) |
| 5173 | Laravel Vite dev server (HMR) |
| 3000 | Node.js Next.js dev server |
| 8051 | Raw PHP app (built-in server) |
| 8052 | Branding website (built-in server) |
| 33068 | Laravel MySQL |
| 33069 | Raw PHP MariaDB |
| 33070 | Branding website MariaDB |

The pre-existing compose files remain untouched:
`eskoofy-laravel-app/docker-compose.yml` (production-like nginx/php-fpm/
mysql/redis stack) and `eskoofy-nodejs-app/docker-compose.yml` (DB only, for
host-side `npm run dev`). This `docker/` tree is solely for containerized dev.