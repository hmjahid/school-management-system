# Archive

Snapshots of the **pre–plain-Blade** codebase (API + React SPA + repo docs) for reference. Active UI development is **Laravel Blade** under `backend/`; the repo root `frontend/` folder is only a pointer README (the React app lives here in `archive/frontend/`).

## Layout

| Path | Description |
|------|-------------|
| **`backend/`** | Copy of `backend/` — Laravel app (PHP, routes, migrations, `resources/views`, tests). |
| **`frontend/`** | Copy of `frontend/` — Vite + React SPA. |
| **`project-root/`** | Copy of repository root files only: `docs/`, `docker/`, `docker-compose.yml`, `*.md`, scripts, etc. (no `backend/`, `frontend/`, `archive/`, `.git`). |

## Excluded from `archive/backend/`

- `vendor/`, `node_modules/`
- `.env`, `.env.backup`, `.env.production` (use `.env.example`; run `composer install`)
- `public/build`, `public/hot`, `public/storage`
- `storage/*.key`, `storage/pail`, `storage/logs/*.log`
- `storage/framework/cache/data/*`, `storage/framework/sessions/*`, `storage/framework/views/*`
- `bootstrap/cache/*.php` (compiled config)
- `.phpunit.cache`

## Excluded from `archive/frontend/`

- `node_modules/`, `coverage/`, `dist/`, `build/`, `.vite/`
- `.env`, `.env.*` (see `archive/frontend/.gitignore`)

## Run archived copies locally

**Laravel**

```bash
cd archive/backend
cp .env.example .env   # then configure
composer install
php artisan key:generate
# migrate / seed as needed
php artisan serve
```

**React SPA**

```bash
cd archive/frontend
npm install
npm run dev
```

## Plain Blade (current direction)

New server-rendered UI lives in the **root** `backend/` tree: `resources/views/`, `routes/web.php`, and Laravel Vite (`vite.config.js`).
