# Feature-Improvements Implementation Plan

Tracks implementation of `docs/FEATURE-IMPROVEMENTS.md` (senior-engineer review).
Each item carries status + verification. Scope follows the review's own priority order.

## Item key
- **[done]** = implemented + test/verification passing
- **[partial]** = implemented with known gaps
- **[deferred]** = intentionally not implemented in this pass (recorded with rationale)
- **[n/a]** = decided not applicable / refused

**Verification baseline (2026-09-08):** `composer test` → **892 passed, 1 risky, 0 failed**.
The single risky test (`Tests\Unit\Services\SmsChannelTest::it_sends_through_the_sms_service_and_logs`)
performs no assertions; it is pre-existing and unrelated to this pass.

---

## Phase 1 — Finish the "half-wired" features (Section 2, P0/P1)

| # | Item | Status | Notes / verification |
|---|------|--------|----------------------|
| 2.1 | Driver-based SMS/push configuration | **[done]** | Already in code: vendors registered in `bootstrap/providers.php` (Notification/Push/SMS); `config/sms.php` + `config/fcm.php` read `SMS_DRIVER`/`FCM_DRIVER` (default `log`); `AppServiceProvider` no longer force-binds. Verified `NotificationProviderSelectionTest` (log drivers resolve in tests). |
| 2.1a | `DeviceToken` model + `User::deviceTokens()` relation | **[done]** | `app/Models/DeviceToken.php` + migration `2026_09_07_000001_create_device_tokens_table`; `User::deviceTokens()` at `app/Models/User.php:153`; consumed by `NotificationDeliveryService::sendPushNotification` (`app/Services/NotificationDeliveryService.php:179`). |
| 2.1b | Guard missing SDKs (twilio/firebase) so misconfig gives clear error | **[done]** | `class_exists` guards in `SmsServiceProvider.php:53` (Twilio) and `PushNotificationServiceProvider.php:57` (Firebase). |
| 2.2 | Real refund execution (bKash end-to-end) or explicit offline-only | **[done]** | `RefundService` + gateway adapters (bKash HTTP, Rocket/Nagad offline); idempotency key on `initiateRefund`. Covered by `RefundServiceTest` (incl. `it_is_idempotent_for_the_same_idempotency_key`, `it_processes_a_pending_refund_through_the_gateway`, `it_marks_a_pending_refund_failed_when_gateway_rejects`). |
| 2.3 | `/api/v1/students/{id}` stubs → real data | **[done]** | `StudentController` implements `attendance`/`results`/`fees`/`edit` scoped via `StudentPolicy::viewAttendance` etc. Covered by `StudentApiTest` (admin attendance, own results only, own fees, edit payload). Fixed `AttendanceResource` eager `route()` calls to guard missing routes. |
| 2.4 | Persist career applications + admin workflow UI | **[done]** | `CareerController::apply` persists `JobApplication` (status `pending`, 201); `DashboardCareerController` index/show/updateStatus/destroy; views `dashboard/careers/{applications,show}.blade.php`; routes `dashboard.careers.*`. Covered by `CareerApplicationTest` (3 tests). Initial status label is `pending` (plan's "New→Under review…" workflow mapped to `pending/reviewed/shortlisted/rejected/hired`). |
| 2.5 | Admission → admin notification | **[done]** | New `AdmissionSubmittedToAdminNotification`; sent to `admin`/`manage_admissions` roles (via `whereHas('roles')`, robust to unseeded roles) on both API submit (`AdmissionController`) and public web apply (`AdmissionWebController`); applicant gets `AdmissionSubmittedNotification`. Covered by `AdmissionSubmissionNotificationTest` (both channels asserted). |
| 2.6 | Placeholder scan: NotificationService + `scheduled_at` | **[done]** | `sendSmsNotification()` fully implemented via `SmsService` (not a placeholder). `scheduled_at` honored at both dispatch sites via `SendBulkSmsJob::delay()` (`DashboardSmsController`). `ScheduledNotificationService` refactored onto `NotificationDeliveryService::send(User, type, data, channels)` with `resolveRecipients()`; `ScheduledNotificationServiceTest` updated & passing (10 tests). Fixed missing `FeePayment` import in `DashboardSmsController`. |

## Phase 2 — Tests (Section 5.1, P1)

| # | Item | Status | Verification |
|---|------|--------|--------------|
| 5.1a | Feature tests for the 4 student endpoints | **[done]** | `StudentApiTest` (5 tests): attendance list, published-results-only, unauthorized cross-student results, own fees + summary, admin edit payload — all pass. |
| 5.1b | Refund contract test (gateway call + idempotency) | **[done]** | `RefundServiceTest`: adapter invocation, offline gateway, idempotent retry with same idempotency key, pending→processed/failed transitions — all pass. |
| 5.1c | Provider selection test (log in tests, twilio when configured) | **[done]** | New `NotificationProviderSelectionTest` (4 tests): `log` default for SMS + push under test; `SmsService` resolves to `LogSmsService`; singleton container; synthetic send success. |
| 5.1d | Dashboard index smoke test (200) | **[done]** | New `DashboardIndexSmokeTest` (1 test, 25 assertions): 24 dashboard index routes render 200 for an `admin` user. |

## Phase 3 — Scheduling & ops (Section 5.3 + 3.5)

| # | Item | Status | Verification |
|---|------|--------|--------------|
| 5.3 | Schedule `payments:process-recurring` + `notifications:process-scheduled` | **[done]** | `routes/console.php`: `payments:process-recurring` daily 01:00 and `notifications:process-scheduled --force` every 5 min, both `withoutOverlapping()`. |

## Phase 4 — Doc debt (Section 6)

| # | Item | Status | Verification |
|---|------|--------|--------------|
| 6.1 | Update/archive `docs/plan-build-incomplete-features.md` | **[done]** | Header marked ARCHIVED/SUPERSEDED; stale "out of scope" list annotated with implementation pointers. |
| 6.2 | Sync `docs/IMPLEMENTATION-PLAN.md` deferred list with priorities | **[done]** | Deferred list updated: H6 (ClassModel consolidation) retained as data-migration ticket; H7/H8/M4 noted partial (refund execution landed, admin UIs still feature work); H3/H4 + L1/L2 tracked separately. |

## Deferred (recorded, not attempted)

- **3.1** Form Request pass — large, separate ticket (31 `$request->all()` sites tracked).
- **3.2** Domain services for big controllers — architectural refactor, separate ticket.
- **3.3** Multi-school SaaS — review §8: do not rush; tenancy stays `enabled=false`.
- **3.4** Token abilities + TOTP 2FA — security hardening, separate ticket (SEC-001/002).
- **3.6** Observability (per-route latency p95, slow query log) — partial: `LogVisitor` exists; metrics dashboard separate.
- **F1–F14** Roadmap features — each a separate ticket; only items that fall out of the above phases may be picked up opportunistically.

---
*Last updated 2026-09-08. All Phase 1–4 items complete; full suite green (892 passed, 1 pre-existing risky).*