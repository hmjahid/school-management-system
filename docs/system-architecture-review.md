# System Architecture Review — School Management System

**Scope:** Full-stack Laravel 12 school management system at `/home/mdjahidhasan/Documents/GitHub/school-management-system`
**Review type:** Senior-system-architect read-only review (no source changes)
**Method:** Direct inspection of `composer.json`, `routes/`, `app/`, `config/`, `database/migrations`, `resources/views`, `tests/`, and `bootstrap/app.php`. Every claim below cites a file.

---

## 1. Executive Summary

This is a **large, feature-rich, single-tenant Laravel 12 application** that serves three audiences from one codebase:

- A **public marketing + service website** (Blade views, bilingual EN/BN, result lookup, admissions, payments).
- An **admin/operations dashboard** (huge Blade-driven back office).
- A **JSON API** under `/api/v1` (Sanctum-authenticated, enveloped, used by a likely external/mobile client).

It is not a micro-service or DDD-heavy system; it follows classic **Laravel layered architecture**: thin-ish controllers → Services → Eloquent models, with a well-designed payment gateway adapter layer and a mature notification subsystem.

**Scale (measured):**

| Metric | Count | Source |
|---|---|---|
| Eloquent models | 86 | `app/Models` |
| Controllers (Web/Api/Auth/Admin) | 129 | `app/Http/Controllers` |
| Service classes | 26 | `app/Services` |
| Migrations | 170 | `database/migrations` |
| Blade views | 314 | `resources/views` |
| API Resources | 18 | `app/Http/Resources` |
| Policies | 19 | `app/Policies` |
| Test files | ~147 | `tests/Feature`, `tests/Unit` |

**Verdict:** A genuinely capable system with several **above-average architectural decisions** (gateway adapter strategy, notification channel abstraction, API envelope middleware, bilingual CMS merge logic, idempotent webhook event store). However it carries **significant, concrete risks**: inconsistent authorization across the dashboard (only `auth` on most routes), a payment webhook whose signature verification is *optional*, heavy reliance on `$request->all()`, no Form Request layer, lazy migration hygiene, and substantial repo clutter. None are fatal; most are fixable in weeks.

---

## 2. Tech Stack & Framework

| Concern | Choice | Evidence |
|---|---|---|
| Framework | Laravel `^12.0` | `composer.json:11` |
| PHP | `^8.2` | `composer.json:9` |
| Auth (API) | `laravel/sanctum` `^4.2` | `composer.json:12` |
| Roles/perms | `spatie/laravel-permission` `^6.21` | `composer.json:15` |
| Audit log | `spatie/laravel-activitylog` `^4.12` | `composer.json:14` |
| PDF | `barryvdh/laravel-dompdf` `^3.1` | `composer.json:10` |
| Frontend | Blade + **Tailwind CSS v4** (`@tailwindcss/vite`) + **Vite 7** | `package.json:10-15`, `vite.config.js` |
| Tests | **PHPUnit `^11.5.3`** (SQLite `:memory`) | `composer.json:24`, `phpunit.xml` |
| Tooling | Pint, Pail, Sail, Collision | `composer.json` dev deps |

Notable: there is **no Inertia/Vue/React SPA** in the active app — the legacy SPA lives in `archive/frontend/` (per `AGENTS.md`, do not modify). Everything is server-rendered Blade + a JSON API.

---

## 3. Architecture & Layering

### Directory structure (conventional, well-separated)
```
app/
  Http/Controllers/{Web,Api,Auth,Admin}   # 129 controllers
  Services/ (Payment/, Sms/, Push/, Notification/, Channels/)  # 26
  Models/ (Concerns/, 86 models)
  Policies/ (19), Middleware/ (12), Providers/ (6)
  Support/ (ApiResponse, SiteFrontend, CmsPageRegistry)
  Events/, Jobs/, Mail/, Notifications/, Observers/, Traits/, Contracts/
routes/ web.php · dashboard.php · api.php · payments.php · admissions.php
        · notifications.php · refunds.php · admin/notifications.php · console.php
```

### Routing strategy
- **`routes/web.php`** — public site + session auth (`AuthSessionController`, `StudentGuardianLoginController`). `bootstrap/app.php:12`.
- **`routes/dashboard.php`** — mounted with `['web', 'throttle.dashboard']` (`bootstrap/app.php:33-34`). Contains **387 `Route::` definitions**; this is the entire back-office surface.
- **`routes/api.php`** — `api/v1` prefix, mounted with `api` + `request.id` + `force.json` (`bootstrap/app.php:17-19`). Defines public content + admin/teacher/auth routes.
- **Mounted API groups** (`bootstrap/app.php:20-38`): `payments.php`, `admissions.php`, `notifications.php`, `refunds.php` under `/api/v1`, plus `admin/notifications.php` under `/api/v1/admin` with `role:admin`.
- Public **gateway webhooks** live *outside* the `v1` prefix (`routes/api.php:194` refund webhook; `payments.php:27` payment webhook) so they are excluded from the envelope (see §6).

### Middleware
Registered in `bootstrap/app.php:44-79`:

| Alias | Implementation | Notes |
|---|---|---|
| `throttle.dashboard` | `DashboardWriteThrottle` | 120/min, **writes only** (POST/PUT/PATCH/DELETE) keyed by user+IP. Good. |
| `request.id` / `force.json` | `RequestId` / `ForceJsonResponse` | Added to `api` group. Good for traceability. |
| `cors` | `CorsMiddleware` | Applied to `api` group. |
| `role` / `permission` | spatie middlewares | Available, used **inconsistently** (see §8/§9). |
| `student_guardian` | `StudentGuardianMiddleware` | Logs out non student/guardian. Used for portal routes. |
| `StandardizeApiResponse` | appended to `api` | Envelope normalization. |
| `SecurityHeaders` | appended globally | Good. |
| `TrustProxies` | `env('TRUSTED_PROXIES', '*')` | **Wildcard by default** — flagged in §9. |

`AdminMiddleware` (`app/Http/Middleware/AdminMiddleware.php`) exists but is **never registered or used** in routes — dead code.

### Layering consistency
Layering is **consistent and sane**: Controllers depend on Services, Services on Models/Adapters. No Repository or Action layer exists (not required, but noted). The API side additionally uses API Resources (18) for output shaping, and the envelope middleware keeps controllers from hand-rolling JSON shape.

---

## 4. Domain Subsystems (observed)

| Domain | Key models / controllers / services | Notes |
|---|---|---|
| **Academic** | `Student`, `SchoolClass`, `Section`, `Batch`, `Subject`, `Exam`, `ExamResult`, `Grade`, `Routine`, `Assignment`, `Attendance`, `Course` | Exam uses `batch_id`+`academic_session_id`+`section_id`+`subject_id` (canonical migration `2025_10_06_022131`). `Exam::isFullyPublished()` guards dual publish flags (`app/Models/Exam.php:254`). |
| **Finance** | `Fee`, `FeePayment`, `Invoice`, `Payment`, `PaymentGateway`, `Refund`, `LedgerEntry`, `ChartOfAccount`, `Expense`, `ExpenseCategory`, `Budget`, `RecurringPaymentProfile` | Strongest subsystem — adapter pattern + side-effects + ledger. |
| **HR** | `Teacher`, `LeaveRequest`, `LeaveType`, `SalaryStructure`, `Payslip`, `StaffAttendance`, `User` | Teacher portal API routes (`routes/api.php:84-88`). |
| **Communications** | `Notification*`, `Message`, `Announcement`, `Notice`, `SmsCampaign*`, `ScheduledNotification`, `NotificationLog` | Multi-channel (DB/Mail/SMS/Push). |
| **CMS / Public site** | `WebsiteContent`, `WebsiteSetting`, `WebsiteMedia`, `WebsiteDocument`, `Page`/`CmsPageRegistry`, `Gallery`, `News`, `Event`, `Career`, `AboutContent` | Bilingual CMS with pruning logic. |
| **Auth & RBAC** | `User` (HasRoles/HasPermissions), `Role`, `Permission`, `RefreshToken`, `Guardian`, `Student` | spatie permissions; policies for 19 models. |
| **Admissions** | `Admission`, `AdmissionSetting`, `AdmissionTest`, `AdmissionDocument`, `JobApplication` | Public apply + admin workflow. |
| **Library / Transport / Hostel** | `Book`, `BookIssue`, `BookCategory`, `TransportRoute`, `TransportStop`, `Vehicle`, `Hostel`, `HostelRoom`, `HostelAssignment` | Secondary but present. |

---

## 5. Database & Schema

- **170 migrations.** Many are **duplicate/legacy guarded migrations** deliberately kept (`Schema::hasTable` guard), e.g.:
  - Canonical `create_exams_table` `2025_10_06_022131` → `batch_id` + `academic_session_id` + `section_id` + `subject_id`, nullable FKs, `metadata` json, `softDeletes`.
  - Guarded duplicate `2025_10_11_000003` → would create `class_id` (FK), `start_time`/`end_time`, different enum `'upcoming','ongoing','completed','cancelled'`. **Never runs** but is a drift trap: a fresh `migrate` depends on the older file winning the sort order; editing either silently no-ops.
  - Similar pattern for `students`, `payments`, `exam_results`, `fees`.
- **"Fix/upgrade/fix-legacy" migrations in flight** signal ongoing schema churn rather than a settled schema: `2026_03_25_fix_exam_results_schema_for_portal`, `2026_03_25_upgrade_legacy_fees_table_columns`, `2026_03_25_160000_add_code_to_fees_table_if_missing`, `2025_10_13_add_class_id_to_students_table`, `2026_08_24_000017_add_refund_status_to_payments_table`. These should be consolidated into a squashed baseline before v1.
- **Soft deletes** used on `Exam`, `ExamResult`, `User`, `Fees` (`2026_03_25_131000_add_deleted_at_to_fees_table`), etc.
- **Multilingual content strategy** (two complementary mechanisms):
  1. **Language files** `lang/en/site_frontend.php` + `lang/bn/site_frontend.php`, read via `site_ui()` helper (`app/helpers.php:5`) which merges config + CMS.
  2. **Per-row bilingual columns** on `WebsiteContent`: `title`/`title_en`/`title_bn`, `content`/`content_en`/`content_bn`, `meta_description*`. The model implements careful **pruning of untranslated BN leaves so they fall back to EN / language file** (`app/Models/WebsiteContent.php:84-193`). This is a sophisticated, correct design — a strength.
- **Relationships** are well-defined as Eloquent relations (`app/Models/Exam.php:137-198`). `Student` references `class_id`, `batch_id`, `roll_number` (per `AGENTS.md`); results connect via `batch_id → exam.batch_id`.

---

## 6. API Design

**Envelope contract** (`app/Http/Middleware/StandardizeApiResponse.php` + `app/Support/ApiResponse.php`):

```json
{ "success": true|false, "message": "...", "data": ..., "meta": { "pagination": {...} }, "request_id": "..." }
```

- `StandardizeApiResponse` unwraps Laravel paginators and `{data:…}` Resource wrappers into a flat `data` + `meta.pagination` shape (`StandardizeApiResponse.php:53-90`).
- Already-enveloped payloads (boolean `success`) are normalized, not double-wrapped (`:27-35`).
- **Gateway webhook paths** (`*/webhook/*`, `*/callback/*`) are explicitly **excluded** from wrapping (`:46-51`), so third-party gateways receive raw passthrough. Correct.
- Controllers use `ApiResponse` trait `success()/created()/paginated()/error()` (`app/Support/ApiResponse.php`).
- **Versioning:** `/api/v1` prefix; `config/api.php` holds `version`, `self_register_roles`, `cors_origins`, `rate_limits` (auth=10, public=60, authenticated=120).
- **Auth:** Sanctum; `auth:sanctum` gate on protected groups. `throttle:10,1` on auth endpoints, `throttle:60,1` on careers apply, `throttle:10,1` on admissions store.

**Concern:** API authorization is coarse — `routes/api.php` contains **zero `can:` policy checks**; protection is via `role:admin` middleware for admin groups (`routes/api.php:125,132`). Only `routes/payments.php` uses `can:` (7 occurrences). Role-based rather than permission/owner-based at the API edge invites privilege creep.

---

## 7. Strengths

1. **Payment gateway adapter strategy** — `GatewayAdapterInterface` + `GatewayAdapterFactory` + `Bkash/Nagad/Rocket` adapters + `PaymentSideEffects` trait (`app/Services/Payment/`). Clean, testable, isolated per-gateway logic. Backed by strong unit tests (`tests/Unit/Services/BkashGatewayAdapterTest.php`, etc.).
2. **Idempotent webhook event store** — `PaymentWebhookEvent` dedupes on `payload_hash` (`PaymentController.php:298-314`); `RefundController` has idempotency key (`RefundController.php:228`). Good for at-least-once gateway delivery.
3. **Notification channel abstraction** — `DatabaseChannel`/`MailChannel`/`SmsChannel` behind `NotificationDeliveryService`, plus `ScheduledNotificationService`, `Sms` (`BaseSmsService`/`Twilio`/`Log`) and `Push` (`FirebasePush`/`Log`) services, wired through Service Providers. Decoupled and swappable.
4. **Bilingual CMS merge logic** — `WebsiteContent` pruning/fallback (`WebsiteContent.php:84-276`) is unusually careful and correct for partial translations.
5. **API envelope middleware** — centralizes contract; keeps controllers clean; correctly excludes webhooks.
6. **Audit logging & activity** — spatie activitylog on `Exam`, `ExamResult`, and `User` (`LogOptions`), plus `ActivityController`/`DashboardActivityController`.
7. **Rate limiting layered** — `throttle.dashboard` (writes only, 120/min) + per-route API throttles + login throttle (5/min) in `AuthSessionController.php:32`.
8. **Testing maturity for services/models** — ~147 test files; dedicated unit tests for every payment gateway, SMS, push, ledger, refund concurrency, and many models.

---

## 8. Weaknesses & Risks

1. **Inconsistent dashboard authorization (HIGH).** `routes/dashboard.php` top group uses only `auth` (`routes/dashboard.php:71`). `role:admin`/`permission:*` appear on only ~6 of 387 routes (`:213,223,334,344,349,368`). The admin dashboard is **not gated by role globally**, so any authenticated non-student user (teacher, accountant) can reach most back-office endpoints. Student/guardian routes *are* fenced by `student_guardian`, but the admin surface is not. *Fix: apply a global `role:admin` (or permission) middleware to the dashboard mount, with exceptions for teacher/portal areas.*
2. **Payment webhook signature verification is optional (HIGH).** `PaymentController::webhook` only verifies HMAC when `extra_attributes['webhook_secret']` is set; default is `null` (`PaymentController.php:289-295`). `callback()` has **no verification at all** (`:231`). A caller who can reach the endpoint can forge payment completion. *Fix: require signature/verification by default; fail closed.*
3. **Mass-assignment smell (MEDIUM).** 32 controllers pass `$request->all()` into flows (`grep` count). No `Model::create($request->all())` was found (good — `$fillable` is defined, e.g. `Exam.php:66`, `ExamResult.php:32`), but `$request->all()` is still threaded into service calls and creates brittle, over-broad input. *Fix: use Form Requests / explicit `validated()`.*
4. **No Form Request layer (MEDIUM).** Only `app/Http/Requests/ApiFormRequest.php` exists; validation is inline `$request->validate()` everywhere. Reduces reusability and testability of rules.
5. **N+1 / in-memory aggregation (MEDIUM).** `Exam::getStatistics()` loads **all** results into memory then iterates (`app/Models/Exam.php:464-522`). Dashboard student closures run multiple per-relation `count()` queries inline (`routes/dashboard.php:97-104`). Works at current scale but will not scale gracefully.
6. **Authorization at API edge is role-only (MEDIUM).** As noted (§6), `routes/api.php` has zero `can:` checks; owner/permission scoping is absent, relying on `role:admin`.
7. **Migration drift & churn (MEDIUM).** Duplicate guarded migrations + many `fix_/upgrade_/add_…_if_missing` migrations (§5). High risk during restores/CI and confusing for new engineers.
8. **Dead code (LOW).** `AdminMiddleware` unused; `Event` dispatching commented out in `BkashGatewayAdapter.php:137`; `models/AboutContent.js` stray file at repo root.
9. **Repo hygiene / clutter (LOW-MEDIUM).** Root contains many scratch/non-source artifacts: `self-notes.md`, `codebase-audit.md`, `improvement-suggestion-*.md`, `features-impl-prompt-*.md`, `unified-implementation-plan-*.md`, `test_admin.php`, `test-dashboard.sh`, and an `unnecessary-files/` folder (`demo-credentials.md`, `cookies.txt`, `architecture-plan.md`, …). These should be removed or moved out of the shipped tree.
10. **Hardcoded/duplicated logic (LOW).** Default grading scale hardcoded in `Exam::getDefaultGradingScale()` (`Exam.php:405`); refund status mapping duplicated across adapters. Bengali fallback string lists hardcoded in `WebsiteContent.php:185`.

---

## 9. Security Assessment

| Area | Status | Evidence |
|---|---|---|
| **Authentication** | Good | Session login rate-limited (5/min) `AuthSessionController.php:32`; Sanctum for API; separate student/guardian login. |
| **Authorization (RBAC)** | **Weak/inconsistent** | Dashboard mostly `auth`-only; policies exist (19) but rarely invoked at API edge; `role:admin` coarse. §8.1/§8.6. |
| **Webhook integrity** | **Weak** | Payment webhook verification optional, callback none. §8.2. `RefundController` does verify. |
| **Mass assignment** | Acceptable | `$fillable` defined; no raw `create($request->all())`. Smell remains. |
| **Input validation** | Adequate but ad-hoc | Inline `validate()`; no centralized Form Requests. |
| **CSRF** | Good | Web routes inherit Laravel `VerifyCsrfToken` (default). |
| **Rate limiting** | Good | Dashboard write throttle + API throttles + login throttle. |
| **Secrets/config** | Good | `config/payment.php` env-driven; `.env.example` placeholders; offline fallback in config. |
| **Trusted proxies** | **Risk** | `TrustProxies` at `'*'` (`bootstrap/app.php:75`) — accepts all proxies; with `SecurityHeaders`+HTTPS this can spoof `X-Forwarded-*`/`clientIp`. Set to real LB CIDRs in prod (TODO already present). |
| **Error leakage** | Minor | Webhook catch returns raw `$e->getMessage()` to caller (`PaymentController.php:273`); should log-only. |
| **Exception rendering** | Good | Central `ApiExceptionRenderer` for all exceptions (`bootstrap/app.php:81-84`). |

---

## 10. Maintainability & Developer Experience

- **Conventions:** Clear Web vs Api controller split, Service layer, API Resources, Policies. `AGENTS.md` and `CLAUDE.md` document routing, envelope, and migration gotchas — strong onboarding docs.
- **Docs:** `docs/` holds operational runbooks (`PRODUCTION-CHECKLIST.md`, `RUNBOOKS.md`, `BACKUP-RESTORE.md`, `PERFORMANCE-BASELINE.md`, etc.) — good ops maturity.
- **Testing:** PHPUnit on SQLite `:memory`; good service/model coverage, **but coverage is uneven** — many dashboard Web controllers and several domains (library, transport, hostel, committee) appear untested. No HTTP-level feature tests for the bulk of `routes/dashboard.php`.
- **DX friction:** 170 migrations (many legacy), root-level scratch files, and the `archive/` legacy SPA add cognitive load. Pint is configured for style enforcement (`composer test`, `./vendor/bin/pint`).
- **Onboarding:** New engineers must understand the "guarded duplicate migration" convention or risk breaking `migrate` — a sharp edge best documented in `AGENTS.md` (it is, but non-obvious).

---

## 11. Recommendations / Roadmap

### Short-term (1–3 weeks, low risk, high impact)
1. **Gate the dashboard by role.** Add `role:admin` (or a permission middleware) to the `routes/dashboard.php` mount, carving out teacher/portal prefixes. *(§8.1, §9)*
2. **Make payment webhook verification fail-closed.** Require HMAC signature on `/api/v1/payments/webhook` and `/callback`; reject when secret missing. *(§8.2, §9)*
3. **Restrict `TRUSTED_PROXIES`** to real LB/CDN CIDRs in production env. *(§9)*
4. **Stop returning raw exception messages** from webhook handlers. *(§9)*

### Medium-term (1–2 months)
5. **Introduce Form Requests** for all write endpoints; replace `$request->all()` with `validated()`. Enables reuse + testability. *(§8.3, §8.4)*
6. **Use policies at the API edge** (`can:` middleware) for owner/permission scoping instead of blanket `role:admin`. *(§6, §8.6)*
7. **Replace in-memory aggregations** (`Exam::getStatistics`, dashboard counts) with query-builder aggregates / eager loading to remove N+1 and memory risk. *(§8.5)*
8. **Remove dead code** (`AdminMiddleware`, commented `event(...)`, `models/AboutContent.js`) and **clear root clutter** (`unnecessary-files/`, scratch `.md`, `test_admin.php`). *(§8.8, §8.9)*

### Long-term (quarter+)
9. **Consolidate migrations** into a squashed baseline (or mark legacy ones clearly) to end schema churn and CI fragility. *(§5, §8.7)*
10. **Add HTTP feature tests** covering the highest-risk dashboard controllers and finance/HR workflows; raise coverage on untested domains (library/transport/hostel). *(§10)*
11. **Extract shared constants** (grading scale, refund status maps, BN fallback lists) into config or dedicated classes to kill duplication. *(§8.10)*
12. **Consider an Action/Command layer** for complex write operations to further thin controllers as the codebase grows beyond 129 controllers.

---

### Appendix — Key files cited
- `composer.json`, `package.json`, `vite.config.js`, `phpunit.xml`
- `bootstrap/app.php` (routing/middleware registration)
- `routes/{web,dashboard,api,payments,admissions,notifications,refunds}.php`, `routes/admin/notifications.php`
- `app/Http/Middleware/{StandardizeApiResponse,DashboardWriteThrottle,StudentGuardianMiddleware,AdminMiddleware,RequestId,ForceJsonResponse,CorsMiddleware,SecurityHeaders}.php`
- `app/Support/ApiResponse.php`, `app/helpers.php`
- `app/Http/Controllers/{Controller,PaymentController,RefundController,Web/AuthSessionController,Web/DashboardController}.php`
- `app/Services/Payment/*`, `app/Services/{NotificationService,NotificationDeliveryService}.php`, `app/Services/Sms/*`, `app/Services/Push/*`
- `app/Models/{Exam,ExamResult,WebsiteContent,User,Payment,PaymentGateway,Refund,Student}.php`
- `config/{payment,api}.php`
- `database/migrations/2025_10_06_022131_create_exams_table.php`, `2025_10_11_000003_create_exams_table.php`
- `tests/Unit/Services/*`, `tests/Feature/*`
