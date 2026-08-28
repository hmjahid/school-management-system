# AGENTS.md — School Management System

## Project structure

- **Root** = the whole app (Laravel 12, PHP 8.2+). Standard Laravel layout — `app/`, `config/`, `routes/`, `resources/`, `public/`, etc.
- **`archive/`** = old backend + frontend copies, including the legacy React SPA in `archive/frontend/`. Do not modify.

## Key packages

- `spatie/laravel-permission` — roles & permissions
- `spatie/laravel-activitylog` — audit logging
- `barryvdh/laravel-dompdf` — PDF generation (certificates, ID cards, admit cards)
- `laravel/sanctum` — API auth tokens

## UI

- **Laravel Blade** (`resources/views/`) is the frontend. Tailwind CSS 4 + Vite.
- CSS is Tailwind v4 utility classes only (`@tailwindcss/vite` plugin).
- Multi-language: English (`lang/en/site_frontend.php`) + Bengali (`lang/bn/site_frontend.php`). Navigation labels use `site_ui('nav.xxx')` helper (`app/helpers.php`) which reads from the lang file merged with CMS overrides.
- The `routes/web.php` has the public site + auth routes; `routes/dashboard.php` (mounted with `web` + `throttle.dashboard`) has all admin/dashboard routes; `routes/api.php` + mounted groups under `/api/v1` (`payments.php`, `refunds.php`, `admissions.php`, `notifications.php`) for the JSON API.

## API envelope (IMPORTANT)

- Every `api/*` JSON response is normalized by `App\Http\Middleware\StandardizeApiResponse` (registered in the `api` middleware group) to `{success, message, data[, meta]}`.
- Pagination hoists under `meta.pagination` (`current_page`, `per_page`, `total`, `last_page`); resource `{data: …}` wrappers are unwrapped so `data` is a single payload.
- Gateway webhook paths (`*/webhook/*`, `*/callback/*`) are **never** rewrapped.
- Controllers should use the `ApiResponse` trait helpers (`success()/created()/paginated()/error()`); already-enveloped payloads with a boolean `success` key are normalized (missing `message` added) but not double-wrapped.

## Middleware

| alias | where | purpose |
|---|---|---|
| `throttle.dashboard` | `routes/dashboard.php` mount | rate-limits dashboard **write** methods (POST/PUT/PATCH/DELETE), 120/min/user/IP |
| `StandardizeApiResponse` | api group | enforces the JSON envelope above |
| `request.id` / `force.json` / `cors` | api group | request-ID header, JSON-only responses, CORS |

## Scheduled jobs & ops commands

| command | schedule | purpose |
|---|---|---|
| `backup:database` | daily 02:00 | self-contained DB backup (sqlite/mysql/pgsql) to `storage/app/backups`, prunes to 7 by default |
| `queue:monitor-failed` | every 5 min | reports failed queue jobs to the default log channel / Slack (`LOG_SLACK_WEBHOOK_URL`) |

Ops docs: `docs/PRODUCTION-CHECKLIST.md`, `docs/RUNBOOKS.md`, `docs/BACKUP-RESTORE.md`.

## Running the app

```bash
cp .env.example .env
php artisan key:generate
# Edit .env — default DB is sqlite (no MySQL needed for dev)
php artisan migrate:fresh --seed
# Dev servers (all at once):
composer dev
# Or individually:
php artisan serve        # app on :8000
npm run dev              # Vite HMR
```

## Commands

| command | what |
|---|---|
| `composer dev` | concurrently runs `php artisan serve`, queue:listen, pail (logs), and `npm run dev` |
| `composer test` | `php artisan config:clear && php artisan test` (PHPUnit with sqlite :memory:) |
| `php artisan test --testsuite=Feature --filter=SpecificTest` | single test |
| `npm run build` | Vite production build |
| `php artisan migrate:fresh --seed` | full reset |
| `php artisan backup:database` | on-demand DB backup (retention `--keep`) |
| `php artisan queue:monitor-failed` | list/report recent failed queue jobs |
| `./vendor/bin/pint` | auto-fix code style (Laravel Pint) |
| `./vendor/bin/pint --test` | check code style without writing |

## Key conventions

- **Controllers** in `App\Http\Controllers\Web\` for Blade pages, `App\Http\Controllers\Api\` for JSON endpoints.
- **Site UI text** goes in `lang/{locale}/site_frontend.php` under the proper key, then referenced with `site_ui('nav.xxx')` or `site_ui('home.xxx')` etc.
- **Navigation items** must be added in both `lang/en/site_frontend.php` (nav section) and `lang/bn/site_frontend.php`, then wired in `resources/views/partials/site/nav.blade.php` inside the appropriate `$dropdownGroup()` call.
- **Exam/result schema**: `exams` table uses `batch_id` + `academic_session_id` + `section_id`. No `class_id` or `year` columns. `exam_results` uses `obtained_marks` (not `marks_obtained`) and `total_marks` lives on the `Exam` model, not `ExamResult`.
- **Student lookup**: Students have `class_id` (FK to `school_classes`), `batch_id` (FK to `batches`), and `roll_number`. Exam results are connected via student's `batch_id` → `exam.batch_id`.
- **Public result lookup**: `GET /results` → `SiteResultController@lookup` → `site.results.blade.php`. API: `GET /api/v1/academics/results/lookup` → `ResultController@lookup`.

## Payment configuration

- Runtime gateway credentials live in the `payment_gateways` table (`App\Models\PaymentGateway`).
- Fallback defaults and offline/bank-transfer account details live in `config/payment.php`, driven by environment variables (see `.env.example`).
- Do not put real secrets in `config/payment.php`; set them in `.env`.

## Exam publish semantics

- `Exam::$is_published` is the boolean column.
- `Exam::STATUS_PUBLISHED` is the workflow status value.
- An exam is considered "fully published" only when **both** are true. Use `$exam->isFullyPublished()` instead of relying on the column alone.

## Migration gotchas

- Several migrations are duplicated/legacy (e.g. two `create_exams_table`, two `create_exam_results_table`) and guarded with `Schema::hasTable(...)`. They are intentional — do not delete them as "duplicates," or a fresh `migrate` will break.
- The canonical `exams` schema (from the earliest `create_exams_table`) has `batch_id` + `academic_session_id` + `section_id` and no `class_id`/`year`. The later guarded duplicate that adds `class_id` never runs.

## Testing

- PHPUnit with SQLite in-memory (`phpunit.xml`). No external DB needed.
- `composer test` runs all suites.
- Tests live in `tests/Feature/` and `tests/Unit/`.

## Docker

- `docker-compose.yml` provides nginx + php-fpm + MySQL 8 + Redis stack.
- Production-like setup: MySQL on port 33061, nginx on 8080.
- Dev uses SQLite (no Docker needed).
