# Feature & Engineering Improvements

Senior software engineer review of the SchoolEase codebase
(129 controllers · 86 models · 170 migrations · 314 views · 152 test files · 513 routes).

**Author context:** This is an improvement proposal, not a bug list. It assumes the existing architecture
(Laravel 12, spatie permissions, sanctum API, dompdf, Tailwind v4) stays. Every item is grounded in
verified findings from the codebase (file references included). Items are prioritized by business impact
vs. effort, and marked `[quick-win]`, `[strategic]`, or `[engineering-health]`.

---

## 1. Executive summary

The product is unusually complete for its stage: admissions, students, exams/results, fees/payments,
payroll, HR, library, transport, hostel, CMS-driven public website, bilingual content, and a full
role/permission model are all present with working UI and a 152-file test suite. The single biggest
theme of this review is that **several headline features are "wired but not real"**:

- **SMS & push notifications are log-only** — no provider is actually reachable, and the presenters
  are force-bound in code so `config/sms.php` is ignored.
- **Refund + gateway execution is a stub** — money never moves.
- **4 API sub-resources under `/api/v1/students/*` return hard-coded empty arrays.**
- **Career applications are never persisted.**
- **A few admin notifications (admission → admin) are TODO/unwired.**

Fixing these "half-features" is higher value than adding new modules. After that, the roadmap below
lays out the highest-leverage new capabilities (SMS/push provider enablement, 2FA, Idempotency,
multi-School SaaS, notifications center admin UI, invoice UI, mobile SDK surface, analytics).

---

## 2. Critical: finish the "half-wired" features

### 2.1 Enable real SMS delivery `[quick-win]` [P0]
**Evidence:** `AppServiceProvider::register()` (app/Providers/AppServiceProvider.php:31-32) hard-binds
`SmsService::class → LogSmsService::class` and `PushNotificationService::class → LogPushNotificationService::class`.
The provider classes `SmsServiceProvider`, `PushNotificationServiceProvider`, `NotificationServiceProvider`
exist but are **not registered** (`bootstrap/providers.php`), and `twilio/sdk` + Firebase SDKs are
**not in composer.json** — so `TwilioSmsService` would fatal if ever unbound.

**Recommendation:**
1. Remove the force-binds in `AppServiceProvider`; bind through `config('sms.default')` / a `SmsManager`
   so the configured driver (`log` in dev, `twilio` in prod) decides.
2. Register the provider + add `twilio/sdk`, `kreait/firebase` (+ `laravel-notification-channels/fcm` if used).
3. Provide a `DeviceToken` model + `User::deviceTokens()` relation (currently `NotificationDeliveryService`
   calls `$user->deviceTokens()` which doesn't exist — the push path is latent-broken).
4. Keep everything behind env-driven config so tests remain log-based.

### 2.2 Real refund execution — or explicitly offline-only `[strategic]` [P0]
**Evidence:** `ProcessRefundJob` contains the comment *"In a real application this would call the payment
gateway"*; the transaction id is `'R-'.uniqid`. `RocketGatewayAdapter` (app/Services/Payment/RocketGatewayAdapter.php:66)
throws *"Callback processing not implemented"* and so does `PaymentService` (line 56).

**Recommendation:** Implement at minimum the **bKash refund** path end-to-end (official API) since bKash
adapter + tests already exist; make Rocket/Nagad refunds either implemented or **explicitly UI-disabled**
with a clear "manual/offline refund" workflow. Never let the system show `Refunded` status when money
didn't move. Add an idempotency key on `initiateRefund`.

### 2.3 Implement the stubbed `/api/v1/students/*` endpoints `[quick-win]` [P1]
**Evidence:** `routes/students.php` mounts 4 endpoints; all return hard-coded empties:
`students/{id}/attendance` → `['data' => []]`, `/results` → `['data' => []]`, `/fees` → `['data' => []]`,
`/edit` → `['data' => null]`.

**Recommendation:** Wire to `Attendance`/`ExamResult`/`FeePayment` scoped by the authenticated student,
or **delete the stubs** — serving fake empty JSON from a production API is worse than 404.

### 2.4 Persist career applications `[quick-win]` [P1]
**Evidence:** `Api/CareerController@apply` validates + stores the resume file, then comments
*"In a real app, you would save this to the database."* There is no admin list UI either
(`JobApplication` model + migration may exist).

**Recommendation:** Persist to `job_applications`, add a dashboard admin index/status workflow
(New → Under review → Shortlisted → Rejected), and fire an application-received notification.

### 2.5 Wire the "admission → notify admin" TODO `[quick-win]` [P1]
**Evidence:** `AdmissionController.php:174` has `// TODO: Send notification to admin`.

**Recommendation:** Dispatch `AdmissionSubmittedNotification`/`AdmissionTransactionSubmittedNotification`
to role-holders (admins/`manage_admissions`) on public application submit, using the existing
`NotificationDeliveryService` pipeline.

### 2.6 Scanner: grep for remaining placeholders/stubs
**Evidence:** `NotificationService` line 489 is literally *"This is a placeholder implementation"*;
`DashboardSmsController:136` ignores `scheduled_at` ("ignore scheduled_at for v1 simplicity").

**Recommendation:** Resolve the NotificationService placeholder (serve the real `NotificationDeliveryService`
pipeline) and implement `scheduled_at` in SMS campaigns (queue a `SendBulkSmsJob` with a delay =
`scheduled_at - now`). Both are small.

---

## 3. Engineering health & architecture

### 3.1 Form Request + parameter-object cleanup `[engineering-health]` [P1]
**Evidence:** Docs note ~31 `$request->all()` call sites and `H3/H4` deferred in
`docs/IMPLEMENTATION-PLAN.md`.

**Recommendation:** Introduce Form Requests for the largest controllers (admissions, fees, payroll,
bulk import/export, settings). Use `validated()` everywhere; add per-field rules with
`Rule::unique()->ignore()`. This also removes scattered inline validation (e.g.
`AuthSessionController::store` validates inline).

### 3.2 Domain services for the big controllers `[engineering-health]` [P2]
`DashboardExamResultController` (317 lines), `DashboardPayrollController` (236), `DashboardSmsController`
(347) mix querying + business rules + rendering. Extract service/repository layers (e.g.
`ExamResultService::saveMarksheet()`, `PayrollService::runMonthly()`, `SmsCampaignService`) so policy and
tests can target logic without HTTP.

### 3.3 Real multi-school readiness (single-tenant at rest) `[strategic]` [P2]
**Evidence:** `app/Models/Concerns/TenantScoped.php` + `config/tenancy.php` `enabled=false` — the trait is a
no-op; ~all tables are global.

**Recommendation (only if SaaS is the roadmap):**
1. Flip tenancy to a real `tenant_id` discriminator with a global scope + `TenantMiddleware` (set current
   tenant from session/token claim).
2. Backfill `tenant_id` for existing data; add composite unique indexes.
3. Keep `School Setting` per tenant; isolate backups, media (filesystem folders), and CMS content.
If SaaS is NOT the roadmap, delete/neutralize the trait to avoid implying a capability that doesn't exist.

### 3.4 API hardening (deferred L1/L2) `[engineering-health]` [P2]
**Evidence:** Sanctum tokens aren't scoped by role; no web 2FA; `RefreshToken` model stores sha256 tokens.
**Recommendation:**
1. Token **abilities** per role (`students:read`, `payments:manage`, …) enforced by middleware on api routes.
2. **TOTP 2FA** for admin web login (`app/Providers` guard hook + a `TwoFactorAuthentication` model/table +
   recovery codes). Gate on admin role; enforce in production.
3. Enforce `SESSION_DRIVER=database/redis`, `httponly` + `samesite=lax`, and `SESSION_SECURE_COOKIE` per env
   (already documented in `docs/PRODUCTION-CHECKLIST.md`).

### 3.5 Data integrity & audit `[engineering-health]` [P1]
- Add FK-constraint-level replicas of the observable-side effects. `AttendanceObserver`/`FinanceObserver`
  maintain `student.attendance_percentage`/ledger from model events — add **fallback recompute scheduled job**
  so drift self-heals (`payroll:reconcile`, `finance:reconcile-ledger`) instead of only observer-triggered writes.
- Add a money/units numeric review: ledger entries, fees and payments currently mix float/int — move to
  integer minor units or DECIMAL(12,2) with explicit `round()` on write.

### 3.6 Observability `[engineering-health]` [P2]
`standardizeApiResponse` normalization + request IDs are great. Add:
- Structured context to `LogVisitor` (device, referer, duration).
- Per-route latency metric (middleware; push to log/Slack) with p95 alerting, reusing `MonitorFailedJobs`
  pattern.
- Slow-query log for sqlite/mysql (`DB::listen` in `AppServiceProvider` when `APP_DEBUG`).

---

## 4. High-value NEW features (roadmap)

Ordered by (business impact / effort):

| # | Feature | Why | Notes |
|---|---------|-----|-------|
| F1 | **Online fee payment "pay now"** on student/guardian portal (bKash/Nagad/Rocket) | Largest revenue-flow automation the system is clearly heading toward | Reuse `PaymentService` + gateway adapters; add per-invoice "Pay" button + webhook→ledger mapping; needs 2.1/2.2 solid first |
| F2 | **2FA + admin security** (see 3.4-2) | Highest trust item for school data | Ship with recovery codes + email fallback |
| F3 | **Notification center admin UI** (templates, preferences, delivery log) | `notifications.php` API + admin routes exist; **UI is missing** — the admin product story for notifications is incomplete | `NotificationTemplateController`, `NotificationPreferenceController`, `SmsCampaignController` need dashboard views |
| F4 | **Invoice model UI** (invoice/credit note per fee-payment) | `Invoice` model exists with **zero UI** | Add create/list/print + link to fee payments + ledger |
| F5 | **Admission test + seat allocation** | `AdmissionTest` model exists; streamlines enrollment season | Admission test scheduling → mark entry → rank → auto-enroll (!) |
| F6 | **Bulk communications → campaigns** (email+SMS) queue with delivery receipts | Reuse `SendBulkSmsJob`; add mail counterpart + per-recipient status tracking | Includes honoring `scheduled_at` (2.6) |
| F7 | **Analytics: real charts + exports** | Dashboard charts are CSS bars; add a lightweight chart render (SVG, no heavy dep) + CSV/PDF export of analytics | `AnalyticsController` already computes aggregates |
| F8 | **Student/teacher mobile/tablet surfaces** | Big moat vs spreadsheet-driven schools | PWA already installed; extend with role-scoped API + offline-first attendance recording screen (job deduped) |
| F9 | **Exams: marks automatic grade/compute** | Marks entry exists; add GPA/grade computation + publishing auto-confirmation to portal | Reuse `ExamResult` + `Grade` semantics |
| F10 | **Payroll: bank file export + tax/NSSF rows** | Local payroll can export bank transfer file + net-pay SMS/PDF | `Payslip` has structure; add batch CSV exporter |
| F11 | **Library: auto fine accrual + due-date notifications** | Daily command recomputing fines + notification on due    | `BookIssue` + `ProcessRecurringPayments`-style scheduled job |
| F12 | **Transport: route assignment + live notice per route** | Extend `TransportRoute`/assignment with per-route SMS/notice to guardians | Cheap, high parent-appreciation |
| F13 | **Reports: PDF report packs (term report by class)** | `DashboardReportController` exports CSV only; teachers want printable term packs | Reuse dompdf + `ReportBuilder` |
| F14 | **Import/export: template download + validation report** | `DashboardBulkController` has dry-run; add downloadable templates + error sheet | Very cheap, high daily-use value |

---

## 5. Quality, tests, and non-functional

### 5.1 Test coverage targets `[engineering-health]` [P1]
152 files (36 Feature / 114 Unit) is strong. Add:
- **Feature tests for the 4 stub endpoints** (2.3) — lock them to real behavior.
- **Refund contract test** on `RefundService::initiateRefund` asserting gateway call + idempotency.
- **Provider selection test** (2.1) asserting `log` driver used in tests, `twilio` selected when configured.
- **Broken-markup guardrails:** a smoke test that hits every dashboard index route (200) catches dead views
  (like the `reports/index` nesting bug).

### 5.2 Performance baseline `[engineering-health]` [P1]
`docs/PERFORMANCE-BASELINE.md` exists. Reinforce:
- N+1 audit on list views (students index, fees, exam results) with `with()` + `withCount()`.
- `$students` capped at 500 in marks entry — consider chunked/lazy or virtualized paging.
- Cache the home page (`Cache::remember` per locale) since it is CMS-driven + DB-heavy; invalidate on
  WebsiteContent save. Keep `app.debug=false` production check for asset pipeline already addressed.

### 5.3 Dependency & concurrency
- `composer audit` in CI; pin PHP 8.3 (Local uses 8.3.27).
- Add queue worker docs + `failOnWait` monitoring; `ProcessRecurringPayments` should be transactional per
  student (catch + continue, not fail-all).

---

## 6. Doc debt

- **`docs/plan-build-incomplete-features.md` is stale** — it lists library/transport/hostel/SMS/timetable
  as out-of-scope, yet all are implemented. Update or archive it.
- Wire the "legacy" `ClassModel`/`Grade` consolidation into a real ticket (AGENTS.md already calls it out) and
  schedule the data migration; do not leave it as an indefinite debt note.
- Keep `docs/IMPLEMENTATION-PLAN.md` deferred list synced with this document's priorities.

---

## 7. Suggested 90-day roadmap (summarized)

**Weeks 1–4 (stability + finish half-features):**
SMS provider linearization (2.1), student API stubs (2.3), career persistence (2.4),
admission→admin notify (2.5), refund bKash or explicit offline-only (2.2), NotificationService cleanup (2.6).
→ Re-runs the full 876+ test suite; release.

**Weeks 5–8 (product depth):**
Student portal "pay now" (F1), notification-center admin UI (F3), invoice UI (F4), SMS scheduled_at (2.6),
exam grade computation (F9), library fine accrual (F11).

**Weeks 9–12 (engineering hardening):**
2FA + token scoping (3.4), Form Request pass (3.1), ledger/money numerics, observability (3.6),
performance + caching (5.2), and the test additions (5.1).

---

## 8. What NOT to do (yet)

- Do not rush a **full multi-tenant SaaS migration** while incidents/outages on core flows remain — the trait
  is a good placeholder, but the (SQL) migration, partitioning, and per-tenant backup isolation are large.
- Do not build a **separate React/Vue admin** — the Blade + component system (x-admin-data-table,
  x-empty-state, confirm-modal) is coherent and fast to extend; splitting UI stacks is a heavy cost for little gain.
- Do not add a **charts/BI heavyweight dependency** — CSS bars + a small SVG helper satisfy current needs;
  pick one target (e.g. admin revenue dashboard) and validate with users first.