# AGENTS.md — School Management System

## Project structure

- **Root** = the whole app (Laravel 12). Standard Laravel layout — `app/`, `config/`, `routes/`, `resources/`, `public/`, etc.
- **`archive/`** = old copies (former backend + frontend). Do not modify.
- **`frontend/`** = README only. The old React SPA is in `archive/frontend/` — do not touch.

## UI

- **Laravel Blade** (`resources/views/`) is the frontend. Tailwind CSS 4 + Vite.
- CSS is Tailwind v4 utility classes only (`@tailwindcss/vite` plugin).
- Multi-language: English (`lang/en/site_frontend.php`) + Bengali (`lang/bn/site_frontend.php`). Navigation labels use `site_ui('nav.xxx')` helper which reads from the lang file merged with CMS overrides.
- The `routes/web.php` has all routes — site pages, auth, dashboard.
- The `routes/api.php` has REST JSON endpoints (mostly public + admin CRUD).

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

## Key conventions

- **Controllers** in `App\Http\Controllers\Web\` for Blade pages, `App\Http\Controllers\Api\` for JSON endpoints.
- **Site UI text** goes in `lang/{locale}/site_frontend.php` under the proper key, then referenced with `site_ui('nav.xxx')` or `site_ui('home.xxx')` etc.
- **Navigation items** must be added in both `lang/en/site_frontend.php` (nav section) and `lang/bn/site_frontend.php`, then wired in `resources/views/partials/site/nav.blade.php` inside the appropriate `$dropdownGroup()` call.
- **Exam/result schema**: `exams` table uses `batch_id` + `academic_session_id` + `section_id`. No `class_id` or `year` columns. `exam_results` uses `obtained_marks` (not `marks_obtained`) and `total_marks` lives on the `Exam` model, not `ExamResult`.
- **Student lookup**: Students have `class_id` (FK to `school_classes`), `batch_id` (FK to `batches`), and `roll_no`/`roll_number`. Exam results are connected via student's `batch_id` → `exam.batch_id`.
- **Public result lookup**: `GET /results` → `SiteResultController@lookup` → `site.results.blade.php`. API: `GET /api/v1/academics/results/lookup` → `ResultController@lookup`.

## Testing

- PHPUnit with SQLite in-memory (`phpunit.xml`). No external DB needed.
- `composer test` runs all suites.
- Tests live in `tests/Feature/` and `tests/Unit/`.
