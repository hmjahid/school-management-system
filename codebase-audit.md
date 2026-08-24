# Codebase Audit Report — School Management System

**Date:** 2026-08-25
**Scope:** Live app only (excludes `archive/`)
**Auditors:** Three parallel senior-level reviews (Security, Architecture, Feature/Logic Consistency)

---

## Executive Summary

The codebase has **9 Critical**, **19 High**, **24 Medium**, and **15 Low** findings across security, architecture, and logic consistency. The most severe issues are:

1. **Webhook signature bypass** allowing forged refund completions
2. **Arbitrary file upload → RCE** via admission document upload
3. **Missing `config/payment.php`** causing hardcoded credentials to be used for all gateway calls
4. **Broken route-model binding / dead middleware** (`Kernel.php` is dead in Laravel 12; `student_guardian` alias unregistered)
5. **Duplicate / broken code paths** (two refund controllers, unreachable publish toggle, `str_plural()` undefined)

---

## Critical Findings (Fix Immediately)

### C1. Refund Webhook Signature Bypass
**`app/Http/Controllers/RefundController.php:255-285` + `routes/api.php:206`**

The public `POST /webhooks/{gateway}/refund` endpoint has a signature bypass for the `rocket` gateway: `getWebhookSignatureValue` reads `$payload['signature']` from the body, and `computeWebhookSignature` **returns the same value**. `hash_equals($x, $x)` is always true → any forged request with an arbitrary `signature` field passes verification and marks any pending refund `completed`.

For `nagad`/default gateways, the secret falls back to hardcoded `'test_merchant_secret'` / `'test_secret'` (published in source).

**Fix:** Compute the expected HMAC from a server-side secret + raw body. Never echo the client-supplied signature back to itself. Reject unknown gateways. Create `config/payment.php` with real secrets from env.

---

### C2. Arbitrary File Upload → Remote Code Execution
**`app/Http/Controllers/AdmissionController.php:353`**

`uploadDocument` validates `'file' => 'required|file|max:10240'` with **no `mimes` restriction**. Files are stored to the **public disk** (`storage/app/public/admissions/...`). `public/.htaccess` and `docker/nginx/nginx.conf` serve `.php` files via PHP-FPM with no block on `/storage/`.

**Impact:** Any authenticated applicant can upload a `.php`/`.phtml` payload and execute it → full RCE.

**Fix:** Add `mimes:jpg,jpeg,png,pdf,doc,docx|max:5120`. Store on the `local` (non-public) disk. Serve via a controlled download controller. Block PHP execution in `/storage` at the web-server level.

---

### C3. Missing `config/payment.php` — Hardcoded Gateway Credentials
**`app/Services/PaymentService.php:594-598,672-673` + `app/Http/Controllers/RefundController.php:280-281`**

`config/payment.php` **does not exist**. Every `config('payment.gateways.bkash.*')` / `config('payment.gateways.rocket.*')` call returns the hardcoded fallback defaults (`'bkash_user'`, `'bkash_pass'`, `'bkash_app_key'`, `'bkash_app_secret'`, `'rocket_user'`, `'rocket_pass'`, `'test_secret'`). These are committed in source → any attacker can forge valid gateway calls.

**Fix:** Create `config/payment.php` reading from env/DB. Abort if credentials are missing.

---

### C4. `str_plural()` Undefined — Every `Exam` Serialization Fatals
**`app/Models/Exam.php:271,274`**

`str_plural('hour', $hours)` / `str_plural('minute', $minutes)`. The global helper `str_plural` does **not exist** in Laravel 12 (only `Illuminate\Support\Str::plural`). Because `duration_formatted` is in `$appends` (line 95), **every `Exam` serialization triggers a fatal error**. Same bug in `app/Models/RecurringPaymentProfile.php:281`.

**Fix:** Replace with `Str::plural('hour', $hours)`.

---

### C5. `student_guardian` Middleware Alias Unregistered — Student/Guardian Routes Broken
**`routes/web.php:167` + `app/Http/Kernel.php:72` (dead)**

`routes/web.php:167` uses `->middleware('student_guardian')`, but the alias is defined **only** in `app/Http/Kernel.php:72`, which is **dead** in Laravel 12 (Laravel 11+ uses `bootstrap/app.php`, not `Kernel.php`). The alias is not registered in `bootstrap/app.php:55-62`. At runtime: `Middleware [student_guardian] does not exist`.

**Fix:** Add `'student_guardian' => \App\Http\Middleware\StudentGuardianMiddleware::class` to `$middleware->alias([...])` in `bootstrap/app.php`.

---

### C6. Unauthenticated Payment Endpoints — IDOR on `status` + Public `initiate`
**`routes/payments.php:18-36` + `app/Http/Controllers/PaymentController.php:81-224`**

`initiate`, `callback`, `webhook`, `status` are declared public (no `auth:sanctum`):
- `POST /payments/initiate` lets anonymous users create `Payment` rows for arbitrary `paymentable_type`/`paymentable_id`.
- `GET /payments/status/{payment}` is **IDOR** — anyone can enumerate payments by sequential id/invoice_number and receive full `PaymentResource` (transaction_id, payment_details, gateway tokens).

**Fix:** Authenticate `initiate`. Scope `status` to the authenticated owner or a signed lookup token.

---

### C7. Broken Duplicate Refund Path — `PaymentController::refund`
**`app/Http/Controllers/PaymentController.php:589-676` + `routes/payments.php:67`**

`POST /api/v1/payments/{payment}/refund` (singular) is a **second, broken** refund endpoint alongside the canonical `POST /api/payments/{payment}/refunds` (RefundController). It is **untested** and broken on every code path:
- Never calls `PaymentService::processRefund` (stubs a random ID at line 611).
- Sets `currency => $payment->currency` (Payment has no `currency` column → null).
- Mass-assigns `refund_id`/`notes` not in `Refund::$fillable` (silently dropped).
- Sets `payment_status => 'partially_refunded'` (not a valid `Payment` status constant).
- References undefined `$id` in the catch block (line 667) → masks real errors.

**Fix:** Delete `PaymentController::refund` and `routes/payments.php:67-69`. Use `RefundController::store` as the single canonical refund endpoint.

---

### C8. Unreachable Publish-to-Public Toggle — Duplicate Route Name
**`routes/web.php:273` vs `routes/web.php:386`**

Both lines define `Route::post('/dashboard/exams/{exam}/publish')` with name `dashboard.exams.publish` but different controllers. Laravel matches the first for the URI, resolves the name to the last → `DashboardExamController::publishToggle` (the "publish to public site" toggle) is **unreachable**. The public-result-publish feature is dead.

**Fix:** Rename one route (e.g. `dashboard.exams.results.publish` for line 273) and give `publishToggle` a distinct URI like `/dashboard/exams/{exam}/visibility`.

---

### C9. `ClassModel` and `SchoolClass` Both Claim `Student.class_id` FK
**`app/Models/ClassModel.php:59-62` + `app/Models/Student.php:84-87`**

`Student::class()` → `SchoolClass` (table `school_classes`). `ClassModel::students()` → `hasMany(Student::class, 'class_id')` claiming the **same FK** but resolving to table `classes`. `Student.class_id` cannot point at two tables. The `Api\TeacherController` (line 49, 106, 119) mixes both, causing `getClassStudents` to return wrong/empty results.

**Fix:** Remove `ClassModel::students()` or repoint to a different FK column. Drive the teacher portal off `SchoolClass` consistently.

---

## High Findings

### H1. `verifyRocketPayment` Signature Mismatch → TypeError
**`app/Services/PaymentService.php:534`**

Signature is `(Payment $payment, array $verificationData)` but `verifyPayment()` dispatches via `$this->$method($payment, $gateway)` passing a `PaymentGateway`. Also `public` while siblings are `protected`. → `TypeError` when verifying Rocket payments.

**Fix:** Change to `protected function verifyRocketPayment(Payment $payment, PaymentGateway $gateway): Payment`.

### H2. Missing Models Referenced by Relations
**`app/Models/ExamResult.php:81,89,97` + `app/Models/Exam.php:172,188`**

Relations reference `Staff`, `ExamSchedule`, `ExamQuestion`, `ExamResultDetail`, `ExamRemark` — **none of these models exist**. Calling these relations fatals.

**Fix:** Create the models + migrations, or remove the relations.

### H3. `Exam::getStatistics` Filters on Non-Existent Status
**`app/Models/Exam.php:483,485`**

`$results->where('status','submitted')` — `ExamResult` has no `'submitted'` status (constants are `pending/passed/failed/absent/malpractice`). `participated` and `passed` are always `0`.

**Fix:** Use `ExamResult::STATUS_PASSED` / `STATUS_FAILED`.

### H4. `SchoolClass::exams()` Queries Non-Existent Column
**`app/Models/SchoolClass.php:103-106`**

`return $this->hasMany(Exam::class, 'class_id')` — `exams` table has no `class_id` (uses `batch_id`+`section_id`). Calling `$schoolClass->exams` → SQL error.

**Fix:** Remove or rewrite via `batch_id`/`section_id`.

### H5. `DashboardExamResultController` References Non-Existent `exam->class_id`
**`app/Http/Controllers/Web/DashboardExamResultController.php:35-37`**

Dead branch: `$exam->class_id` is always null (exams table has no `class_id`).

### H6. Hardcoded Sandbox/Typo URLs in Refund Methods
**`app/Services/PaymentService.php:591,639,669`**

- bKash refund: `$base = 'https://checkout.sandbox.bka.sh'` (sandbox only, no live path).
- Nagad refund: `'https://api.mynagad.com/api'` (live only, hardcoded).
- Rocket refund: `'https://api.razo.com.bd/api/v1'` (likely typo "razo" for "rocket").

Init flow correctly uses `$gateway->sandbox_url`/`live_url`; refund flow contradicts it.

**Fix:** Drive all base URLs from `PaymentGateway::getApiConfig()`.

### H7. `PaymentGateway` Model Has No `$hidden` — Credential Leak
**`app/Models/PaymentGateway.php:48-78`**

`$fillable` includes `api_key`, `api_secret`, `api_username`, `api_password`. The `encrypted` cast **decrypts on read/serialization**, so `toArray()`/JSON exposes plaintext. `PaymentGatewayController::index` returns these via `ApiResponse::paginated()`.

**Fix:** Add `protected $hidden = ['api_key','api_secret','api_username','api_password']`.

### H8. `WebsiteSetting` Has No `$hidden` — Credential Leak to All Views
**`app/Models/WebsiteSetting.php:34-104` + `app/Providers/AppServiceProvider.php:59-73`**

`$fillable` includes bKash/Twilio credentials. No `$hidden`. `Admin/WebsiteSettingController::index/update` returns the full model, and `View::composer('*', ...)` shares it with **every Blade view**.

**Fix:** Add `$hidden` for all credential columns. Share a whitelisted DTO, not the raw model.

### H9. `app/Http/Kernel.php` Is Dead in Laravel 12
**`app/Http/Kernel.php` (entire file)**

Laravel 11+ configures middleware via `bootstrap/app.php` and does **not load `Kernel.php`**. Its aliases (`admin`, `student_guardian`, custom `auth`/`signed`/…) are not applied. All custom middleware classes referenced only there are effectively unregistered.

**Fix:** Delete `Kernel.php`. Re-register genuinely-needed aliases in `bootstrap/app.php`.

### H10. Duplicate Controllers (Same Class Name, Different Namespaces)
- `TeacherController` (flat) vs `Api\TeacherController`
- `NotificationController` (flat) vs `Web\NotificationController`
- `NewsController` (flat) vs `Api\NewsController`
- `AuthController` (flat) vs `Auth\AuthController`

**Fix:** Delete the unused flat duplicates after confirming no dynamic resolution.

### H11. Duplicate Route Name `admissions.status`
**`routes/admissions.php:25` vs `routes/web.php:123`**

Last-registered name wins → silent shadowing.

### H12. Refund Routes Outside `v1` Prefix
**`routes/api.php:195-206`**

Refund routes (`/api/refunds`, `/api/payments/{payment}/refunds`, `/api/webhooks/{gateway}/refund`) are outside the `v1` group. Payment routes are at `/api/v1/payments/...`. Same domain, two versioning policies.

### H13. CORS Misconfiguration
**`config/cors.php:19,24`**

`allowed_origins => ['*']` with `supports_credentials => true` is **invalid per the CORS spec**. Also effectively dead because `HandleCors` is only wired in the dead `Kernel.php`.

### H14. `trustProxies('*')` — IP Spoofing
**`bootstrap/app.php:53`**

Trusts all proxies → `X-Forwarded-For` spoofing defeats IP-based rate limiting and pollutes audit logs.

### H15. 8 Pairs of Duplicate `create_*_table` Migrations
Only `exams`/`exam_results` are documented in AGENTS.md. Also: `students`, `teachers`, `payments`, `notifications`, `guardians`, `fees`. All guarded with `Schema::hasTable` so later ones no-op, but they're dead code that confuses readers.

### H16. Inconsistent API Response Envelope
Controllers using `PaymentResource`/`AdmissionResource` emit `{data: {...}}`. Controllers using `ApiResponse` trait emit `{success, message, data}`. Two different shapes depending on endpoint.

**Fix:** Standardize on one. Either `JsonResource::withoutWrapping()` + `ApiResponse`, or always Resources.

### H17. Duplicate `PaymentServiceTest` Files
**`tests/Feature/PaymentServiceTest.php`** (namespace `Tests\Feature`) **and** `tests/Feature/Payment/PaymentServiceTest.php` (namespace `Tests\Feature\Payment`) — same class name, overlapping coverage. PHPUnit runs both.

### H18. Event CRUD Authorization Gaps (API + Web)
**`routes/api.php:126-130` + `routes/web.php:282-288`**

`store`/`update`/`destroy` under `auth:santum`/`auth` only; no role check, no ownership check → IDOR (any user edits/deletes any event).

### H19. `AdminUserSeeder` Default Credentials
**`database/seeders/AdminUserSeeder.php:24,63-64`**

Seeds `admin@school.com` / `password`. If `migrate:fresh --seed` runs in production → publicly-known weak admin login.

---

## Medium Findings

| # | Location | Issue |
|---|---|---|
| M1 | `app/Http/Controllers/Api/CmsController.php:175` | `$settings->fill($request->all())` — mass assignment without validation |
| M2 | `app/Http/Controllers/Api/SearchController.php:57-60` | Returns student/teacher emails to any authenticated user (PII enumeration) |
| M3 | `routes/web.php:364-382` vs `597-615` | Transport routes registered twice (auth-only + role:admin) — brittle |
| M4 | `app/Http/Controllers/RefundController.php:153` | `sleep(2)` in HTTP request — production-unsafe; should be a queued job |
| M5 | `app/Http/Controllers/RefundController.php:207` | Webhook has no idempotency (replays can flip status repeatedly) — unlike `PaymentController::webhook` |
| M6 | `app/Services/PaymentService.php:43-47` | Offline payment returns hardcoded `'1234567890'` account/routing — placeholder data shipped to users |
| M7 | `app/Services/PaymentService.php` (703 lines) | God class — mixing orchestration with per-gateway HTTP. Split into adapters |
| M8 | `app/Models/Exam.php:89-96` + `252-255` | `is_published` column/accessor name collision — accessor overrides column on serialization |
| M9 | `app/Models/ExamResult.php:209-232` | `publish()`/`unpublish()` set columns not in `$fillable` or schema (`unpublish_remarks`, etc.) |
| M10 | `app/Models/User.php:43-53` | `$fillable` includes `role_id`/`password` — privilege-escalation risk if any controller does `User::create($request->all())` |
| M11 | `config/session.php:149,177` | `SESSION_SECURE_COOKIE` defaults `false`; session cookie sent over HTTP |
| M12 | `config/session.php:51` | `SESSION_ENCRYPT=false` — session data unencrypted |
| M13 | `app/Exceptions/ApiExceptionRenderer.php:69-71` | Returns `$e->getMessage()` when `APP_DEBUG=true` — debug-gated leak |
| M14 | `app/Http/Controllers/PaymentController.php:274,356,673` | Returns `$e->getMessage()` **unconditionally** — leaks internals to public callers |
| M15 | `app/Http/Controllers/Auth/AuthController.php:62,108,139` | Catch blocks return `$e->getMessage()` when `APP_DEBUG=true` |
| M16 | `app/Http/Middleware/CorsMiddleware.php:15` | Falls back to echoing first configured origin for disallowed requests (should send nothing) |
| M17 | `resources/views/site/news-show.blade.php:94-100` | `{!! nl2br($rest) !!}` on `strip_tags()` output — not XSS-safe |
| M18 | `routes/notifications.php:46-49` | `GET /notifications/stream` returns hardcoded stub — fake endpoint in production |
| M19 | `database/migrations/2026_08_24_000001_encrypt_sensitive_settings.php:55` | Uses `str_starts_with($value, 'eyJ')` heuristic to detect already-encrypted — false positives |
| M20 | `database/migrations/2026_03_25_135000_fix_exam_results_schema_for_portal.php:22` | `->after('gpa')` references non-existent column; `default(true)` contradicts canonical `default(false)` |
| M21 | `database/migrations/2025_10_11_000000_create_exam_results_table.php:67` | MySQL-only `FULLTEXT` — would break SQLite if guard removed |
| M22 | `app/Models/Exam.php` — `is_published` vs `is_published_to_public` | Two independent publish flags with divergent semantics — confusing |
| M23 | `database/migrations/2026_07_18_000013_add_publish_flag_to_exams.php` | `is_published_to_public` not in `Exam::$fillable` or `$casts` |
| M24 | `app/Providers/AppServiceProvider.php:48-57` | `Schema::hasTable(...)` called in `boot()` on every request — slow; observer registration inconsistent |

---

## Low Findings

| # | Location | Issue |
|---|---|---|
| L1 | `app/Models/User.php:128-135` | `hasPermissionTo` swallows `PermissionDoesNotExist` — safe, but masks config errors |
| L2 | `app/Models/User.php:253-260` | `defaultProfilePhotoUrl()` depends on external `ui-avatars.com` — privacy/availability |
| L3 | `app/Policies/PaymentPolicy.php:174` | `processRefund(User $user)` — unused dead policy method |
| L4 | `app/Http/Controllers/ExamController.php:679-682` | Hardcodes academic sessions `2023-2024`/`2024-2025` instead of querying `AcademicSession` |
| L5 | `resources/views/partials/site/nav.blade.php:257,299,306` | Hardcoded English strings bypass `site_ui()` CMS-override mechanism |
| L6 | `tests/Feature/ExampleTest.php` + `tests/Unit/ExampleTest.php` | Leftover Laravel stubs — dead |
| L7 | `database/migrations/2025_10_11_135800_create_notifications_table.php.bak` | `.bak` file in migrations dir |
| L8 | `app/Http/Middleware/Admin.php` + `AdminMiddleware.php` | Two near-identical admin middleware classes, both only in dead Kernel |
| L9 | `config/sanctum.php:101` | Empty `token_prefix` — GitHub secret-scanning disabled |
| L10 | `app/Http/Controllers/Auth/AuthController.php:85,163` | Tokens always issued with `abilities = ['*']` — no scoping |
| L11 | `app/Http/Resources/PaymentResource.php:46-48` | Exposes `createdBy.email`/`updatedBy.email` when relations loaded |
| L12 | `.env:4` | `APP_DEBUG=true` in working tree — ensure prod sets `false` |
| L13 | `test_admin.php` (repo root) | CLI impersonation script in repo root — hygiene risk |
| L14 | 10 test files | Deprecated `@test` doc-comment annotations — migrate to `#[Test]` attribute |
| L15 | `tests/Unit/Services/RefundServiceTest.php` + `RefundConcurrencyTest.php` | In `Unit` suite but use `RefreshDatabase` — should be in `Feature/` |

---

## Architecture Recommendations

1. **Delete `Kernel.php`** and migrate all needed aliases to `bootstrap/app.php`.
2. **Create `config/payment.php`** reading gateway credentials from env; stop using hardcoded defaults.
3. **Consolidate refund logic** — delete `PaymentController::refund`, keep `RefundController::store` as canonical.
4. **Split `PaymentService`** into orchestration + per-gateway adapter classes (`BkashGateway`, `NagadGateway`, `RocketGateway`).
5. **Standardize response envelope** — pick one (`ApiResponse` trait or Resources, not both).
6. **Move refund routes inside `v1` prefix** — uniform API versioning.
7. **Split `routes/web.php`** (696 lines) — extract dashboard routes into `routes/dashboard.php`.
8. **Delete duplicate controllers** (flat `TeacherController`, `NewsController`, `AuthController`, `NotificationController`).
9. **Delete duplicate `PaymentServiceTest`** — keep the richer `Payment/PaymentServiceTest.php`.
10. **Create missing models** (`Staff`, `ExamSchedule`, etc.) or remove the relations.
11. **Fix `str_plural()` → `Str::plural()`** in `Exam` and `RecurringPaymentProfile`.
12. **Add `$hidden` to `PaymentGateway` and `WebsiteSetting`** for credential columns.
13. **Block PHP execution in `/storage`** at the web-server level (nginx/apache).
14. **Make webhook signature verification mandatory** (not optional `if ($secret)`).
