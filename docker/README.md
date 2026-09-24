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

| Product | Live reload mechanism |
|---|---|
| WP theme | `eskoofy-wp-theme` bind-mounted into WordPress; PHP re-reads it per request |
| Laravel | `eskoofy-laravel-app` bind-mounted; `php artisan serve` re-reads PHP per request + **Vite dev server (:5173) for CSS/JS hot-reload**; migrations auto-run on boot and demo data is auto-seeded on a fresh DB |
| Node.js | `eskoofy-nodejs-app` bind-mounted; **Next.js dev server hot-reload** (`node_modules`/`.next` live in container volumes) |
| Raw PHP | `eskoofy-php-app` bind-mounted; PHP built-in server re-reads per request |
| Branding website | `eskoofy-branding-website` bind-mounted; PHP built-in server re-reads per request |

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