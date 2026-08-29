# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Also see `AGENTS.md` — it overlaps with this file and carries extra conventions (site UI text, nav wiring, exam/result schema gotchas). Keep both in sync when conventions change.

## Commands

| command | what |
|---|---|
| `composer dev` | runs `php artisan serve` (:8000) + `queue:listen` + `pail` (log tail) + `npm run dev` concurrently |
| `composer test` | `php artisan config:clear && php artisan test` — **always clear config first**, a cached config breaks the test env |
| `php artisan test --testsuite=Unit` / `--testsuite=Feature` | one suite |
| `php artisan test --filter=ApiResponseEnvelopeTest` | single test class |
| `php artisan test --filter=test_webhook_payloads_are_not_rewrapped` | single test method |
| `php artisan migrate:fresh --seed` | full reset (SQLite dev DB + full demo dataset) |
| `./vendor/bin/pint` / `./vendor/bin/pint --test` | code style fix / check |
| `npm run build` | Vite production build |
| `php artisan backup:database` | on-demand DB backup (`--keep=N` retention) |
| `php artisan queue:monitor-failed` | report recent failed queue jobs |

Tests run on SQLite `:memory:` (`phpunit.xml`) — no DB server needed. Dev also defaults to SQLite. `docker-compose.yml` (nginx :8080 + php-fpm + MySQL :33061 + Redis) is only for a production-like stack.

## Architecture

Laravel 12 / PHP 8.2, **server-rendered Blade** (`resources/views/`) + Tailwind 4 via Vite. `archive/` holds a legacy React SPA and old backend — read-only, never modify. Root-level `models/`, `routes/aboutContent.js`, `routes/admin/` (JS) are legacy leftovers from that SPA, not part of the Laravel app.

### Routing (all wired in `bootstrap/app.php`)

Route files are **not** all auto-loaded — `bootstrap/app.php`'s `then:` closure mounts most of them with explicit middleware. When adding a route file, register it there.

| file | mount | notes |
|---|---|---|
| `routes/web.php` | `web` | public marketing site + auth + payment/portal pages |
| `routes/dashboard.php` | `web` + `throttle.dashboard` | ~52KB, **all** admin/dashboard routes live here |
| `routes/api.php` | `api` | base `/api/*` |
| `payments.php`, `admissions.php`, `notifications.php`, `refunds.php` | `api` + `request.id` + `force.json`, prefix `api/v1` | JSON API groups |
| `routes/admin/notifications.php` | same + `auth:sanctum` + `role:admin`, prefix `api/v1/admin` | |

### API envelope

`App\Http\Middleware\StandardizeApiResponse` (appended to the `api` group) normalizes every `api/*` JSON response to `{success, message, data[, meta]}`. Pagination hoists to `meta.pagination`; `{data: …}` resource wrappers are unwrapped. **Gateway webhook/callback paths (`*/webhook/*`, `*/callback/*`) are never rewrapped.** Controllers should use the `App\Support\ApiResponse` trait (`success()`/`created()`/`paginated()`/`error()`); payloads already carrying a boolean `success` key are normalized but not double-wrapped. Exceptions render through `App\Exceptions\ApiExceptionRenderer` (registered globally in `bootstrap/app.php`).

### Controllers

`App\Http\Controllers\Web\` (85 files) holds both public-site *and* dashboard controllers — the `Dashboard*` prefix distinguishes them, not the namespace. `Api\` (23) is JSON-only. `Admin\` and the 18 root-namespace controllers are older/mixed; prefer `Web\` or `Api\` for new work.

### Service layer

- **Payments**: `PaymentService` resolves a `PaymentGateway` row (runtime creds live in the `payment_gateways` table), short-circuits offline gateways using `config/payment.php` bank details, then delegates to `Payment\GatewayAdapterFactory::make($code)` → `BkashGatewayAdapter` | `NagadGatewayAdapter` | `RocketGatewayAdapter`, all implementing `GatewayAdapterInterface` (`initialize`/`processCallback`/`verifyPayment`/`refund`). New gateway = new adapter + factory `match` arm + `PaymentGatewaySeeder` row. Shared post-success work lives in the `PaymentSideEffects` trait. `RefundService` + `ProcessRefundJob` handle refunds (see `tests/Unit/Services/RefundConcurrencyTest.php` for the locking contract).
- **Accounting**: `LedgerService::postEntry()/postJournal()` writes double-entry `ledger_entries` against `ChartOfAccount`. `FinanceObserver` auto-posts on `FeePayment::created` and `Expense::created` — wired manually in `AppServiceProvider::boot()` (behind `Schema::hasTable` guards), not via `Model::observe`. A `Relation::morphMap` maps `fee_payment`/`expense`.
- **Notifications — two parallel stacks, don't conflate them**: `NotificationService` is a fluent builder (`->type()->to()->via()->send()`) used by the `Notifiable` trait; `NotificationDeliveryService` is a preference-aware dispatcher (per-user `NotificationPreference`, `NotificationTemplate` rendering, `NotificationLog`). Transport contracts `App\Contracts\SmsService` / `PushNotificationService` are bound to the `Log*` no-op implementations in `AppServiceProvider::register()` — swap those bindings for `Sms\TwilioSmsService` / `Push\FirebasePushService` in real deployments. `SmsServiceProvider`, `PushNotificationServiceProvider`, `NotificationServiceProvider`, `BroadcastServiceProvider` exist but are **not** in `bootstrap/providers.php` (only `AppServiceProvider` and `ActivityLogServiceProvider` are registered).

### View-layer globals

`AppServiceProvider::boot()` registers a `View::composer('*')` injecting `$siteSettings` (cached `WebsiteSetting`) and `$siteUi` (`SiteFrontend::merged()`). A second composer on the dashboard sidebar/topbar injects `$sidebarPendingCounts`, `$dashboardFavorites`, `$dashboardHelpSection`. All of it is `Schema::hasTable`-guarded and try/catch-wrapped so views still render pre-migration — preserve that when editing.

Site copy: `SiteFrontend::merged()` = `lang/{en,bn}/site_frontend.php` overlaid with CMS overrides from the DB, read via the `site_ui('nav.xxx')` helper (`app/helpers.php`, autoloaded through composer `files`). Editable CMS pages are declared in `App\Support\CmsPageRegistry`.

### Auth & authorization

Session auth for web, Sanctum for API. `spatie/laravel-permission` provides roles `admin, teacher, student, parent, accountant, librarian, user` (seeded by `RolePermissionSeeder`; a stale `RolesAndPermissionsSeeder` also exists but isn't called). 20 policies in `app/Policies/`. Middleware aliases: `role`, `permission`, `role_or_permission`, `student_guardian`, `throttle.dashboard` (120/min on dashboard writes), `request.id`, `force.json`.

`spatie/laravel-activitylog` audit trail via the `App\Traits\LogsModelActivity` trait (`logFillable` + `logOnlyDirty`).

`App\Models\Concerns\TenantScoped` is a **dormant** multi-tenant foundation — a no-op unless `config('tenancy.enabled')`. Only `Budget` and `ExpenseCategory` use it today.

## Gotchas

- **170 migrations, several intentionally duplicated** (two `create_exams_table`, two `create_exam_results_table`) and guarded with `Schema::hasTable(...)`. Do not delete them as duplicates — a fresh `migrate` breaks. The canonical `exams` schema comes from the *earliest* file: `batch_id` + `academic_session_id` + `section_id`, **no** `class_id`/`year`. The later guarded duplicate that adds `class_id` never runs.
- `exam_results` uses `obtained_marks` (not `marks_obtained`); `total_marks` lives on `Exam`, not `ExamResult`.
- Exam publishing needs **both** `is_published` (column) and `STATUS_PUBLISHED` (workflow status) — use `$exam->isFullyPublished()`.
- Students link to results via `student.batch_id` → `exam.batch_id`, not `class_id`.
- Adding a nav item means editing `lang/en/site_frontend.php`, `lang/bn/site_frontend.php`, **and** `resources/views/partials/site/nav.blade.php`.
- Real payment secrets go in `.env` / the `payment_gateways` table, never `config/payment.php`.
- Only 10 model factories exist (`database/factories/`) — most feature tests build fixtures inline.

## Ops

Scheduled in `routes/console.php`: `backup:database` daily 02:00, `queue:monitor-failed` every 5 min (logs / `LOG_SLACK_WEBHOOK_URL`). Docs: `docs/PRODUCTION-CHECKLIST.md`, `docs/RUNBOOKS.md`, `docs/BACKUP-RESTORE.md`, `docs/API-PAYMENTS.md`, `docs/ADMISSIONS.md`, `docs/PAYMENT-DEPLOYMENT.md`.
