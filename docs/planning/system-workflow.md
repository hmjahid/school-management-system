# School Management System — Full Workflow

**Document version:** 2026-08-25
**Scope:** End-to-end business and technical workflows for the live Laravel 12 application (excludes `archive/`).

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [User Roles & Authentication](#2-user-roles--authentication)
3. [Module Map](#3-module-map)
4. [Admission Workflow](#4-admission-workflow-applicant-journey)
5. [Exam & Result Workflow](#5-exam--result-workflow)
6. [Fee & Payment Workflow](#6-fee--payment-workflow)
7. [Payment Gateway Lifecycle](#7-payment-gateway-lifecycle)
8. [Refund Workflow](#8-refund-workflow)
9. [Attendance Workflow](#9-attendance-workflow)
10. [Finance & Ledger Workflow](#10-finance--ledger-workflow)
11. [Library Workflow](#11-library-workflow)
12. [Notification Workflow](#12-notification-workflow)
13. [Public Site vs Dashboard](#13-public-site-vs-dashboard)
14. [Background Jobs & Commands](#14-background-jobs--commands)
15. [Integrations](#15-integrations)
16. [Data Relationship Map](#16-data-relationship-map)

---

## 1. Architecture Overview

**Stack:** Laravel 12 · PHP 8.2+ · Blade + Tailwind v4 + Vite · SQLite (dev) / MySQL 8 (prod) · Redis (queues)

**Key packages:** `spatie/laravel-permission` (roles/permissions), `spatie/laravel-activitylog` (audit), `barryvdh/laravel-dompdf` (PDFs), `laravel/sanctum` (API tokens).

**Route mounting** (`bootstrap/app.php:18-37`):

| File | Prefix | Middleware group | Purpose |
|---|---|---|---|
| `routes/web.php` | `/` | `web` (session + CSRF) | Blade pages (site + dashboard) |
| `routes/api.php` | `/api/v1` | `api` (throttle, JSON) | REST JSON API |
| `routes/payments.php` | `/api/v1/payments` | `api` + `request.id` + `force.json` | Payment controller endpoints |
| `routes/admissions.php` | `/api/v1/admissions` | `api` + `request.id` + `force.json` | Admission API |
| `routes/notifications.php` | `/api/v1/notifications` | `api` + `request.id` + `force.json` | Notification API |
| `routes/admin/notifications.php` | `/api/v1/admin/notifications` | `api` + `auth:sanctum` + `role:admin` | Admin notification templates |

**i18n:** English + Bengali via `lang/{en,bn}/site_frontend.php`, accessed with `site_ui('nav.xxx')` helper (`app/helpers.php:5-12`) which deep-merges lang defaults with CMS overrides (`app/Support/SiteFrontend.php:43-62`).

---

## 2. User Roles & Authentication

### 2.1 Roles

Defined in `database/seeders/RolePermissionSeeder.php:20-28`. Admin inherits ALL permissions (`:251`).

| Role | Key permissions | Auth scope |
|---|---|---|
| `admin` | Everything | Full dashboard + API |
| `teacher` | View users/students, manage attendance & marks, view/print results, publish notices | Dashboard (teacher-scoped) + API (`/api/v1/teacher/*`) |
| `student` | View attendance, view/print own results | Student portal (`/student/dashboard`) |
| `parent` | Same as student (views linked children) | Guardian portal (`/guardian/dashboard`) |
| `accountant` | Fee categories/types, collect fees, expenses, approve payments | Dashboard (finance) |
| `librarian` | Manage/issue books, collect dues, library reports | Dashboard (library) |
| `user` | No permissions | Generic |

Seeded admin: `admin@school.com` / `password` (`database/seeders/AdminUserSeeder.php`).

### 2.2 Two Authentication Systems

**(A) Web session auth** (Blade dashboard/portals) — guard `web`:

```
Admin/Staff Login
  GET  /login            → AuthSessionController@create
  POST /login            → AuthSessionController@store  (throttle 5/60s by IP+email)
                           redirects to /dashboard

Student/Guardian Login
  GET  /student/login    → StudentGuardianLoginController@showLoginForm
  POST /student/login    → StudentGuardianLoginController@login
                           Auth::attempt → checks hasRole('student'/'parent')
                           if wrong role → logout + redirect
                           redirects to /student/dashboard or /guardian/dashboard

Logout
  POST /logout           → AuthSessionController@destroy
```

**Files:** `app/Http/Controllers/Web/AuthSessionController.php`, `app/Http/Controllers/Auth/StudentGuardianLoginController.php`.

**(B) Sanctum API token auth** (JSON API) — guard `sanctum`:

```
POST /api/v1/auth/login          → Auth\AuthController@login
                                   issues access token (60 min) + refresh token (30 days)
POST /api/v1/auth/register       → Auth\AuthController@register
                                   self-registration restricted to student/parent roles only
                                   (config('api.self_register_roles'), RegisterRequest.php:12-18)
POST /api/v1/auth/refresh-token  → rotates tokens; invalid refresh → revokes ALL tokens
POST /api/v1/auth/logout         → revokes all tokens
```

**File:** `app/Http/Controllers/Auth/AuthController.php`. Token pair logic in `app/Models/User.php:162-195`.

### 2.3 Middleware Aliases

| Alias | Class | Purpose |
|---|---|---|
| `auth` | `Authenticate` | Redirect guests to login |
| `role` | Spatie `RoleMiddleware` | Check role |
| `permission` | Spatie `PermissionMiddleware` | Check permission |
| `admin` | `App\Http\Middleware\Admin` | JSON 401/403 for admin-only API |
| `student_guardian` | `App\Http\Middleware\StudentGuardianMiddleware` | Requires student/parent role (logout + redirect if not) |

---

## 3. Module Map

| Module | Models | Web Controller(s) | API Controller(s) | Key Operations |
|---|---|---|---|---|
| **Students** | `Student` | `DashboardStudentController` | — | CRUD, promote, view results |
| **Guardians** | `Guardian` | `DashboardGuardianController` | — | CRUD, link to students (pivot) |
| **Teachers** | `Teacher` | `DashboardTeacherController` | `Api\TeacherController` | CRUD, class/section/subject assignment |
| **Classes** | `SchoolClass`, `Section`, `Batch`, `AcademicSession`, `Subject` | `DashboardSchoolClassController` | `SchoolClassController` (flat) | CRUD sections/subjects/batches |
| **Attendance** | `Attendance` | `DashboardAttendanceController`, `DashboardStaffAttendanceController` | `AttendanceController` | Daily/bulk/subject-wise, staff attendance |
| **Exams** | `Exam`, `ExamResult` | `DashboardExamController`, `DashboardExamResultController` | — | Create, marks entry, publish, results |
| **Fees** | `Fee`, `FeePayment` | `DashboardFeeController`, `DashboardFeePaymentController` | `Api\FeeController`, `Api\FeePaymentController` | Fee categories, collection, approve/cancel |
| **Payments** | `Payment`, `PaymentGateway` | `PaymentsWebController` | `PaymentController` | Initiate, callback, webhook, status, refund |
| **Admissions** | `Admission`, `AdmissionDocument`, `AdmissionSetting` | `AdmissionWebController`, `DashboardAdmissionController` | `AdmissionController` | Apply, payment, review, enroll |
| **Finance** | `Expense`, `ExpenseCategory`, `Budget`, `ChartOfAccount`, `LedgerEntry` | `DashboardExpenseController`, `DashboardLedgerController`, `DashboardBankReconciliationController` | — | Expenses, budgets, ledger, reconciliation |
| **Library** | `Book`, `BookCategory`, `BookIssue` | `DashboardBookIssueController` | — | Issue, return, fines, reports |
| **Hostel** | `Hostel`, `HostelRoom`, `HostelAssignment` | `DashboardHostelController` | — | Rooms, assignments |
| **Transport** | `Vehicle`, `TransportRoute`, `TransportAssignment` | `DashboardTransportController` | — | Vehicles, routes, assignments |
| **CMS** | `Notice`, `News`, `Event`, `Gallery`, `WebsiteContent`, `WebsiteSetting` | `CmsWebController`, `DashboardNewsController`, etc. | `Api\CmsController`, `Api\NewsController`, etc. | Pages, media, menus, settings |
| **Certificates** | `Certificate`, `AdmitCard`, `StudentIdCard` | `DashboardCertificateController`, etc. | — | Generate + print PDFs |
| **Notifications** | `Notification`, `NotificationLog`, `NotificationTemplate` | `DashboardNotificationController` | `NotificationController` | Database, mail, SMS, push |
| **Reports** | — | `DashboardReportController`, `DashboardController` | `Api\AnalyticsController` | Stats, export, audit log |
| **Settings** | `WebsiteSetting` | `DashboardSettingController` | `Admin\WebsiteSettingController` | School profile, theme, gateways, SMS |

---

## 4. Admission Workflow (Applicant Journey)

```
┌─────────────────────────────────────────────────────────────────────┐
│                         ADMISSION FLOW                              │
└─────────────────────────────────────────────────────────────────────┘

  [Applicant] ──GET /admissions/apply──► AdmissionWebController@apply
                                          │
                                          ├─ Check AdmissionSetting.is_open
                                          ├─ If closed → show closedView
                                          └─ Load academic sessions + batches

           ──POST /admissions/apply──► AdmissionWebController@applyStore
                                        │ (throttle 12/min)
                                        └─► AdmissionSubmitter::submitPublicApplication
                                              │
                                              ├─ Validate student/parent info + file uploads
                                              ├─ DB transaction:
                                              │   ├─ Create Admission (status=submitted)
                                              │   ├─ Generate application_number (APP{YYYY}{00001})
                                              │   ├─ Store documents → public disk
                                              │   └─ Create AdmissionDocument records
                                              ├─ Stamp admission_fee from AdmissionSetting
                                              └─ Email: AdmissionSubmittedNotification

  [Applicant] ──POST /admissions/{id}/submit-payment──► submitTransaction
                                                          │
                                                          ├─ Set payment_status=submitted, paid_at
                                                          └─ Email: AdmissionTransactionSubmittedNotification

  [Admin]    ──POST /dashboard/admissions/{id}/verify-payment──► verifyPayment
                                                                    │
                                                                    ├─ Set payment_status=verified
                                                                    └─ Email: AdmissionPaymentVerifiedNotification

  [Admin]    ──POST /dashboard/admissions/{id}/tests──► scheduleTest
                                                         │
                                                         ├─ Create AdmissionTest
                                                         └─ Email: AdmissionTestScheduledNotification

  [Admin]    ──POST /dashboard/admissions/{id}/status──► updateStatus
                                                            │
                                                            ├─ approve()  → status=approved
                                                            ├─ reject()   → status=rejected + reason
                                                            └─ Email: AdmissionStatusChangedNotification

  [Admin]    ──POST /api/v1/admissions/{id}/enroll──► AdmissionController@enroll
                                                       │
                                                       └─► Admission::enroll($studentData)
                                                            │
                                                            ├─ Only if approved & not enrolled
                                                            ├─ Create Student record (copy applicant data)
                                                            ├─ Generate roll_number
                                                            ├─ Create User account (random password, assign 'student' role)
                                                            └─ Set admission status=enrolled

  [Applicant] ──GET /admissions/{id}/receipt──► printable receipt
  [Applicant] ──GET /admissions/{id}/approval-letter──► (only if verified + approved)
```

**Key files:** `app/Http/Controllers/Web/AdmissionWebController.php`, `app/Services/AdmissionSubmitter.php`, `app/Models/Admission.php` (`enroll()` at `:365-431`).

---

## 5. Exam & Result Workflow

### 5.1 Exam Creation

```
[Admin/Teacher] ──GET /dashboard/exams/create──► DashboardExamController@create
                                                    │
                                                    └─ Load sessions, batches, sections, subjects,
                                                       teachers, exam types, statuses, grading types

                ──POST /dashboard/exams──► DashboardExamController@store
                                              │
                                              └─► ExamController@store (JSON 201) → redirect to list
```

**Exam schema** (`2025_10_06_022131_create_exams_table.php`):
- `batch_id` + `academic_session_id` + `section_id` + `subject_id`
- `total_marks`, `passing_marks` (live on `Exam`, NOT on `ExamResult`)
- **No `class_id` / `year` columns** (per AGENTS.md convention)

### 5.2 Marks Entry

```
[Admin/Teacher] ──GET /dashboard/exams/{exam}/results──► DashboardExamResultController@index
                                                              │
                                                              ├─ Load students matching exam.batch_id + section_id
                                                              ├─ Load existing results keyed by student_id
                                                              └─ Load Exam::getStatistics()

                ──POST /dashboard/exams/{exam}/results──► store
                                                          │
                                                          ├─ Validate marks array (each ≤ exam.total_marks)
                                                          ├─ For each student:
                                                          │   ├─ Compute grade via Exam::calculateGrade()
                                                          │   ├─ Compute status: passed/failed vs passing_marks
                                                          │   └─ ExamResult::updateOrCreate(
                                                          │        [exam_id, student_id],
                                                          │        [obtained_marks, grade, grade_point, remarks, status]
                                                          │     )
```

**Column naming:** `exam_results.obtained_marks` (NOT `marks_obtained`). `total_marks` lives on `Exam`.

### 5.3 Publishing — TWO Independent Flags

```
Flag 1: ExamResult.is_published (per-result, gates dashboard view)
  [Admin] POST /dashboard/exams/{exam}/results/publish   → bulk-set is_published=true
  [Admin] POST /dashboard/exams/{exam}/results/unpublish → bulk-set is_published=false

Flag 2: Exam.is_published_to_public (per-exam, gates PUBLIC site)
  [Admin] POST /dashboard/exams/{exam}/publish            → publishToggle
                                                          → flips is_published_to_public

⚠️ Both flags must be true for public lookup to return results.
```

### 5.4 Public Result Lookup

```
[Public] ──GET /results──► SiteResultController@lookup
                             │
                             ├─ Input: class_id, academic_session_id, roll
                             ├─ Find Student by class_id + (roll_no OR roll_number)
                             ├─ Exams where:
                             │   ├─ is_published_to_public = true
                             │   ├─ academic_session_id = {input}
                             │ └─ batch_id = student.batch_id
                             ├─ ExamResults where is_published = true
                             └─ Return view: site.results

[Public] ──GET /results/download──► SiteResultController@download
                                      │
                                      └─ Pdf::loadHTML() → download PDF

[API]  ──GET /api/v1/academics/results/lookup──► Api\ResultController@lookup
                                                   │
                                                   └─ Same logic, returns JSON grouped by exam

[API]  ──GET /api/v1/academics/results/filters──► returns classes + sessions
```

**Key data flow:** Student → `batch_id` → `Exam.batch_id` → `ExamResult`. The join key between students and exams is `batch_id`, not `class_id`.

---

## 6. Fee & Payment Workflow

### 6.1 Fee Creation

```
[Admin/Accountant] ──/dashboard/fees──► DashboardFeeController
                                          │
                                          ├─ Create Fee (name, code, amount, fee_type,
                                          │   frequency, class_id/section_id/student_id,
                                          │   fine_amount, discount_amount)
                                          └─ Fee types: tuition, admission, exam, transport, library
```

### 6.2 Fee Payment Lifecycle

```
┌──────────────────────────────────────────────────────────────────────┐
│                    FEE PAYMENT LIFECYCLE                              │
└──────────────────────────────────────────────────────────────────────┘

  [Student/Parent] ──POST /payments/initiate──► PaymentsWebController@initiate
                                                   │ (auth required)
                                                   │
                                                   ┌─ DB Transaction ─────────────────────┐
                                                   │ ├─ Create FeePayment (status=pending, │
                                                   │ │   paid_amount=0, balance=amount)    │
                                                   │ ├─ Create Payment (paymentable=tuition│
                                                   │ │   status=pending, metadata.fee_payment_id)
                                                   │ └─ Call PaymentService::initializePayment│
                                                   └──────────────────────────────────────┘
                                                   │
                                                   ├─ bKash → redirect to bkashURL
                                                   ├─ Nagad → redirect to callBackUrl
                                                   └─ Rocket → redirect to success_url

  [Gateway] ──POST /api/v1/payments/callback/{gateway}──► PaymentController@callback
                                                             │
                                                             └─► PaymentService::processCallback
                                                                   │
                                                                   ├─ verifyBkashPayment (checkout/execute)
                                                                   ├─ verifyNagadPayment (verify/payment)
                                                                   │
                                                                   ├─ On SUCCESS:
                                                                   │   ├─ Payment → status=completed, paid_amount=total
                                                                   │   ├─ applyPaymentSideEffects():
                                                                   │   │   ├─ Find FeePayment via metadata.fee_payment_id
                                                                   │   │   └─ FeePayment → status=paid, balance=0
                                                                   │   └─ Fire PaymentProcessed event
                                                                   │
                                                                   └─ On FAILURE:
                                                                       └─ Payment → status=failed

  [Gateway] ──POST /api/v1/payments/webhook/{gateway}──► PaymentController@webhook
                                                            │
                                                            ├─ Optional HMAC verification
                                                            ├─ De-duplicate via PaymentWebhookEvent
                                                            └─ Re-run processCallback

  [Anyone] ──GET /api/v1/payments/status/{payment}──► PaymentController@status
                                                         │
                                                         └─ If not completed → PaymentService::verifyPayment
                                                            (queries gateway payment/status)
```

### 6.3 Manual/Offline Path

```
[Admin] ──POST /api/v1/payments/record-offline──► PaymentController::recordOfflinePayment
                                                    │
                                                    └─ Create Payment (status=completed directly)
                                                       + fire PaymentProcessed event

[Admin] ──POST /dashboard/fee-payments/{id}/approve──► DashboardFeePaymentController::approve
                                                         │
                                                         └─ FeePayment → status=paid, approved_by

[Admin] ──POST /dashboard/fee-payments/{id}/cancel──► DashboardFeePaymentController::cancel
                                                        │
                                                        └─ FeePayment → status=cancelled
```

### 6.4 Due Tracking & Reminders

```
DashboardSmsController::dueFeeRecipients
  │
  ├─ Group FeePayments where balance > 0 AND status NOT IN (paid, cancelled, refunded)
  ├─ Sum per-student balance
  └─ Queue SendBulkSmsJob with due-reminder template
```

---

## 7. Payment Gateway Lifecycle

### 7.1 Config Sources (Dual)

| Source | Used by | Notes |
|---|---|---|
| **DB: `PaymentGateway` model** | Init, callback, verify | `getApiConfig()` returns `test_mode, api_key/secret/username/password, callback_url, webhook_url, currency`. Credentials cast `encrypted`. |
| **`config('payment.gateways.*')`** | Refund methods + webhook signature | ⚠️ `config/payment.php` does NOT exist — calls fall back to hardcoded defaults. |

### 7.2 Full Lifecycle (bKash example)

```
1. INITIATE
   PaymentController::initiate
     │
     ├─ Validate (gateway, amount, currency, paymentable_type, paymentable_id)
     ├─ Load PaymentGateway by code → check is_active, is_configured, limits
     ├─ Compute fee → total_amount = amount + fee
     ├─ Create Payment (status=pending, invoice_number=INV{Ymd}{seq})
     └─ PaymentService::initializeBkashPayment
          │
          ├─ POST {sandbox_url}/checkout/token/grant  (username/password → id_token)
          ├─ POST {sandbox_url}/checkout/create        (Authorization: id_token, paymentID)
          ├─ Store bkash_payment_id + bkash_token in payment_details
          └─ Return { redirect_url: bkashURL }

2. USER REDIRECT → bKash checkout page → user pays

3. CALLBACK (browser redirect)
   PaymentController::callback
     └─ PaymentService::processBkashCallback
          │
          ├─ POST {sandbox_url}/checkout/execute (Authorization: bkash_token, paymentID)
          ├─ If transactionStatus == 'Completed':
          │   ├─ Payment → status=completed, paid_amount=total, due_amount=0
          │   ├─ Store transaction_id (trxID)
          │   └─ applyPaymentSideEffects → FeePayment → status=paid
          └─ Else: Payment → status=failed

4. WEBHOOK (server-to-server, idempotent)
   PaymentController::webhook
     ├─ Optional HMAC-SHA256 verification (webhook_secret from gateway.extra_attributes)
     ├─ De-duplicate via PaymentWebhookEvent (payload_hash)
     └─ Re-run processCallback

5. STATUS CHECK (polling)
   PaymentController::status
     └─ If not completed → PaymentService::verifyBkashPayment
          ├─ POST {sandbox_url}/checkout/payment/status
          ├─ Update payment_status if changed
          └─ Fire PaymentProcessed event on transition to completed
```

### 7.3 Supported Gateways

| Gateway | Init | Callback | Verify | Refund |
|---|---|---|---|---|
| bKash | ✅ `sandbox.bkash.com/checkout/` | ✅ `checkout/execute` | ✅ `checkout/payment/status` | ✅ `checkout.sandbox.bka.sh/tokenized/checkout/payment/refund` |
| Nagad | ✅ `sandbox.mynagad.com` | ✅ `verify/payment` | ✅ | ✅ `api.mynagad.com/api/dfs/refund/initialize` |
| Rocket | ✅ | ✅ | ⚠️ Signature mismatch (see audit C1) | ✅ `api.razo.com.bd` |
| `test_gateway` | — | — | — | ✅ (stub, for tests) |

---

## 8. Refund Workflow

### 8.1 Canonical Path (RefundController + RefundService)

```
[Admin] ──POST /api/payments/{payment}/refunds──► RefundController::store
              (can:refund, auth:sanctum)            │
                                                   ├─ Validate (amount, reason)
                                                   ├─ Duplicate-amount guard (abort 422)
                                                   └─ RefundService::initiateRefund
                                                        │
                                                        ├─ Validate amount > 0
                                                        ├─ isRefundable(payment)?
                                                        │   ├─ status == completed
                                                        │   ├─ supportsRefunds(method)
                                                        │   └─ refunded < amount
                                                        ├─ getRefundableAmount(payment)
                                                        ├─ Create Refund (status=pending)
                                                        │   user_id = payment.created_by
                                                        ├─ processGatewayRefund
                                                        │   └─ PaymentService::processRefund(gatewayCode, txnId, amount, reason)
                                                        │      ├─ refundBkash    → token/grant + payment/refund
                                                        │      ├─ refundNagad    → dfs/refund/initialize
                                                        │      ├─ refundRocket   → token + refund
                                                        │      └─ test_gateway   → stub success
                                                        │
                                                        ├─ On SUCCESS:
                                                        │   ├─ Refund → status=completed, transaction_id
                                                        │   ├─ updatePaymentRefundStatus(payment)
                                                        │   │   └─ Payment.refund_status = fully_refunded / partially_refunded
                                                        │   └─ Return { success: true, refund: $refund }
                                                        │
                                                        └─ On FAILURE:
                                                            ├─ Refund → status=failed (PERSISTED for audit)
                                                            └─ Return { success: false, refund: $refund }
```

### 8.2 Refund Webhook (Gateway → Server)

```
[Gateway] ──POST /api/webhooks/{gateway}/refund──► RefundController::webhook
                                                     │ (public, no auth)
                                                     ├─ verifyWebhookSignature
                                                     │   ├─ bKash/Nagad: HMAC-SHA256 with config secret
                                                     │   └─ Rocket: ⚠️ reads signature from body (bypass — see audit C1)
                                                     ├─ Find Refund by transaction_id
                                                     └─ Refund → status=completed
```

### 8.3 Process / Cancel

```
[Admin] POST /api/refunds/{refund}/process   → RefundController::process  (sleep(2), stubs completion)
[Admin] POST /api/refunds/{refund}/cancel     → RefundController::cancel
[Admin] GET  /api/refunds                     → index (list with filters)
[Admin] GET  /api/refunds/statistics          → statistics (counts by status)
[Anyone] GET /api/refunds/{refund}           → show (admin OR owner)
```

---

## 9. Attendance Workflow

```
[Admin/Teacher] ──GET /dashboard/attendance/create──► DashboardAttendanceController@create
                                                         │
                                                         ├─ Load students by class/section/batch
                                                         └─ Load existing attendance for date

                ──POST /dashboard/attendance/bulk──► store
                                                     │
                                                     ├─ For each student:
                                                     │   ├─ Create/Update Attendance record
                                                     │   │   (status: present/absent/late/half_day/holiday/on_leave)
                                                     │   └─ type: daily / subject_wise / special_event
                                                     └─ If absent → dispatch SendAbsenceSmsJob

[Staff] ──/dashboard/staff-attendance──► DashboardStaffAttendanceController
                                         ├─ index  (daily form)
                                         ├─ store  (mark staff attendance)
                                         └─ report (monthly summary)
```

**Statuses:** `present, absent, late, half_day, holiday, on_leave`
**Types:** `daily, subject_wise, special_event`

---

## 10. Finance & Ledger Workflow

### 10.1 Expenses

```
[Admin/Accountant] (permission:manage_expenses)
  /dashboard/expenses           → CRUD + export
  /dashboard/expense-categories → CRUD
  /dashboard/budgets            → CRUD
  /dashboard/bank-reconciliation → index + reconcile
```

### 10.2 Ledger

```
DashboardLedgerController
  ├─ /ledger           → journal entries
  ├─ /ledger/cashbook  → cash book report
  ├─ /ledger/bankbook  → bank book report
  └─ Reports:
      ├─ income-statement
      ├─ balance-sheet
      └─ cash-flow
```

**Service:** `app/Services/LedgerService.php`. Bank reconciliation compares book balance (sum debit-credit on bank accounts) vs statement balance.

### 10.3 Payroll

```
/dashboard/payroll
  ├─ salary-structures → create salary structure per teacher/staff
  ├─ generate-payslips → generate monthly payslips
  └─ mark-paid → mark payslip as paid
```

### 10.4 Recurring Payments

```
artisan payments:process-recurring {--retry-failed} {--max-retries=3} {--force} {--test}
  └─ RecurringPaymentService::processDuePayments
     ├─ Find due RecurringPaymentProfile records
     └─ Create Payment + charge via gateway
```

---

## 11. Library Workflow

```
[Librarian]
  /dashboard/books               → CRUD books
  /dashboard/book-categories     → CRUD categories
  /dashboard/book-issues
    ├─ create → Issue book (decrement book.available_quantity)
    ├─ return → Calculate late fee (BookIssue::calculateLateFee)
    │           Increment book.available_quantity
    ├─ fine   → Collect fine (mark fine_paid)
    └─ lost   → Mark as lost
  /dashboard/library-reports
    ├─ issued   → currently issued
    ├─ overdue  → past due date
    └─ history  → all transactions
```

**Statuses:** `issued, returned, lost, damaged`

---

## 12. Notification Workflow

### 12.1 Channels

| Channel | Service | Config | Default |
|---|---|---|---|
| `database` | Laravel Notification | — | ✅ on |
| `mail` | Laravel Mail | `config/mail.php` | ✅ on |
| `sms` | `SmsService` contract → `LogSmsService` / `TwilioSmsService` | `config/sms.php` | ❌ off (log driver) |
| `push` | `PushNotificationService` contract → `FirebasePushService` / `LogPushService` | `config/fcm.php` | ✅ on |

### 12.2 Delivery Flow

```
NotificationDispatch
  │
  ├─ NotificationDeliveryService (singleton)
  │   ├─ Resolve per-user NotificationPreference
  │   │   (email on, SMS off, push on, DnD 22:00-07:00 weekends)
  │   ├─ Dispatch via SmsService (queue optional)
  │   └─ Dispatch via PushNotificationService (FCM)
  │
  ├─ ScheduledNotification
  │   └─ artisan notifications:process-scheduled {--limit=10}
  │      └─ ScheduledNotificationService::processDueNotifications
  │
  └─ Bulk SMS
      └─ DashboardSmsController::send → SendBulkSmsJob
```

### 12.3 Notification Templates

```
[Admin] /api/v1/admin/notifications/templates → CRUD templates
         ├─ Types + variables
         └─ Preview
```

---

## 13. Public Site vs Dashboard

### 13.1 Public (No Auth)

```
/                  → Homepage (CMS-driven, bilingual)
/results           → Public result lookup (class + roll + session)
/admissions/apply  → Online admission application
/admissions/status → Track application status
/news, /news/{slug}
/notices
/gallery
/events
/committee
/about, /academics, /faculty, /transport
/contact
/terms, /privacy
/routine           → Public class timetable
/search            → Site search
/payments         → Payment info page (initiate/receipt require auth)
/portal            → Login portal landing
/locale/{locale}   → Switch EN/BN
```

### 13.2 Authenticated Dashboard

```
/dashboard                    → Role-gated stats (admin/teacher/accountant/staff/librarian)
/profile                      → Edit own profile
/notifications                → In-app notifications
/messages                     → Direct messages
/search                       → Dashboard search

Student/Guardian Portal (student_guardian middleware):
  /student/dashboard          → Own attendance, results, fees, invoices
  /guardian/dashboard         → Linked children's data

Admin (role:admin):
  /dashboard/settings/*       → School profile, theme, gateways, SMS, CMS
  /dashboard/cms/*            → Pages, media, menus, header, footer
  /dashboard/news/*           → News CRUD
  /dashboard/gallery/*        → Gallery CRUD
  /dashboard/users/*          → User management
  /dashboard/roles/*          → Role management
  /dashboard/students/*      → Student CRUD + promote
  /dashboard/teachers/*       → Teacher CRUD
  /dashboard/exams/*          → Exam + result management
  /dashboard/fees/*           → Fee management
  /dashboard/fee-payments/*   → Approve/cancel payments
  /dashboard/expenses/*       → Finance (permission-gated)
  /dashboard/ledger/*         → Ledger reports
  /dashboard/library/*        → Library
  /dashboard/hostels/*        → Hostel
  /dashboard/transport/*      → Transport
  /dashboard/certificates/*   → Certificate + admit card + ID card
  /dashboard/sms/*            → SMS campaigns
  /dashboard/backup/*         → Backup/restore
  /dashboard/activity         → Audit log
```

---

## 14. Background Jobs & Commands

### 14.1 Artisan Commands

| Command | Purpose | Schedule |
|---|---|---|
| `backup:run {--path=}` | Zip `storage/app/public` + DB into `storage/app/backups` | Daily at 02:00 (`routes/console.php:12`) |
| `backup:restore` | Restore from backup file | Manual |
| `payments:process-recurring {--retry-failed} {--max-retries=3} {--force} {--test}` | Process due recurring payments | Manual (cron) |
| `notifications:process-scheduled {--limit=10} {--force}` | Send due scheduled notifications | Manual (refuses outside prod without `--force`) |

### 14.2 Queue Jobs

| Job | Trigger | Purpose |
|---|---|---|
| `SendBulkSmsJob` | `DashboardSmsController::send` / `dueReminder` | Send SMS campaign to all recipients |
| `SendAbsenceSmsJob` | Attendance marking | Send absence SMS to parent |
| `SendNotificationJob` | Notification dispatch | Generic notification delivery |

**Queue config:** `config/queue.php`. SMS queueing optional via `config/sms.php:86-111`.

---

## 15. Integrations

| Integration | Config | Service | Notes |
|---|---|---|---|
| **bKash** | `PaymentGateway` DB model (encrypted) | `PaymentService::initializeBkashPayment` | Sandbox: `sandbox.bkash.com/checkout/` |
| **Nagad** | `PaymentGateway` DB model | `PaymentService::initializeNagadPayment` | Sandbox: `sandbox.mynagad.com` |
| **Rocket** | `PaymentGateway` DB model | `PaymentService::initializeRocketPayment` | Sandbox: `api.razo.com.bd` |
| **Twilio SMS** | `config/sms.php` + env | `TwilioSmsService` | Optional; default = `log` driver |
| **FCM Push** | `config/fcm.php` + env | `FirebasePushService` | Service account from `FIREBASE_CREDENTIALS` |
| **PDF (dompdf)** | — | `Pdf::loadHTML()` | Marksheets, certificates, result PDFs, ID cards |
| **Audit log** | `spatie/laravel-activitylog` | `LogsActivity` trait | `DashboardActivityController` viewer |

---

## 16. Data Relationship Map

### 16.1 Student Linkage

```
Student
  ├─ user_id        → User (auth account)
  ├─ class_id       → SchoolClass (table: school_classes)  [NOT NULL]
  ├─ section_id     → Section
  ├─ batch_id       → Batch
  ├─ guardian_id    → Guardian (denormalized primary)
  └─ admission_id   → Admission (if enrolled via admission)

Guardian ←→ Student  via  guardian_student pivot
                    (relationship, is_primary)
```

### 16.2 Exam Result Linkage

```
Exam
  ├─ batch_id             → Batch       ─┐
  ├─ academic_session_id  → AcademicSession │  ← join keys
  ├─ section_id           → Section     │     between students
  ├─ subject_id          → Subject     │     and exams
  ├─ total_marks         (on Exam)
  └─ passing_marks       (on Exam)

ExamResult
  ├─ exam_id     → Exam
  ├─ student_id  → Student
  ├─ obtained_marks  (NOT marks_obtained)
  ├─ grade, grade_point, remarks
  ├─ status     (pending/passed/failed/absent/malpractice)
  ├─ is_published (per-result flag)
  ├─ submitted_by/at, reviewed_by/at, published_by/at
  └─ NO total_marks (lives on Exam)
```

**Public lookup join:** Student → `batch_id` → `Exam.batch_id` + `Exam.academic_session_id` + `Exam.is_published_to_public` → `ExamResult.is_published`.

### 16.3 Fee Payment Linkage

```
FeePayment
  ├─ student_id → Student
  ├─ fee_id     → Fee
  ├─ amount, paid_amount, balance
  ├─ status     (pending/paid/partial/cancelled/refunded)
  └─ payment_method (cash/bank_transfer/check/online_payment/mobile_banking/other)

Payment (polymorphic)
  ├─ paymentable_type = 'tuition'
  ├─ paymentable_id   = FeePayment.id
  ├─ payment_status   (pending/processing/completed/failed/refunded/cancelled/expired)
  ├─ refund_status    (not_refunded/partially_refunded/fully_refunded)
  ├─ metadata.fee_payment_id  → links back to FeePayment
  └─ refunds() → Refund

Online path:  Payment completed → applyPaymentSideEffects → FeePayment.status = paid
Manual path: DashboardFeePaymentController::approve → FeePayment.status = paid
```

### 16.4 Teacher Linkage

```
Teacher
  ├─ classes   → pivot class_teacher (is_class_teacher, academic_session_id)
  ├─ sections  → pivot section_teacher
  ├─ subjects  → pivot class_subject_teacher
  ├─ attendances, leaves, salaryPayments, payslips
  └─ activeStructure (current class/section/subject assignment)
```

---

## Appendix: Key Conventions (from AGENTS.md)

| Convention | Rule |
|---|---|
| **Exam schema** | `exams` uses `batch_id` + `academic_session_id` + `section_id`. No `class_id` or `year`. |
| **Exam results** | `exam_results.obtained_marks` (not `marks_obtained`). `total_marks` lives on `Exam`, not `ExamResult`. |
| **Student lookup** | `Student.class_id` → `school_classes` (NOT `classes`/`ClassModel`). Connected to exams via `batch_id`. |
| **Public result lookup** | `GET /results` → `SiteResultController@lookup`. API: `GET /api/v1/academics/results/lookup`. |
| **Migration gotchas** | Duplicate `create_exams_table` / `create_exam_results_table` migrations are intentional (guarded with `Schema::hasTable`). Do not delete — `migrate:fresh` would break. |
| **Site UI text** | All navigation/site text via `site_ui('nav.xxx')` reading from `lang/{locale}/site_frontend.php` merged with CMS overrides. |
