# Payment System Review — All Products + Branding Website

**Date:** 2026-09-25
**Scope:** `eskoofy-laravel-app`, `eskoofy-php-app`, `eskoofy-wp-theme`, `eskoofy-nodejs-app`, `eskoofy-branding-website`
**Type:** Read-only security & functional review (no code was modified)
**Method:** End-to-end source tracing, diff against the Laravel reference and `docs/design/PAYMENT-MODEL.md`, `docs/operations/PAYMENT-DEPLOYMENT.md`, `docs/operations/API-PAYMENTS.md`, live route/execution probes, and the existing test suites.

---

## 1. Executive summary

**Overall verdict: NOT production-ready for online money movement in any product.** The only end-to-end working payment paths today are manual/offline ones (admin-verified bank/cash). Every online gateway (bKash, Rocket, Nagad, Stripe, PayPal, Paddle) is broken at either checkout, callback verification, or webhook confirmation — and the callback/webhook surfaces that do exist contain critical, exploitable authorization and verification gaps.

| Product | Online gateways | Offline/manual | Security posture | Verdict |
|---|---|---|---|---|
| Laravel app | Broken end-to-end (BD flows 404; callback flaws) | Working | **5 criticals reproduced** — free-enrollment, invoice settlement w/o auth, underpayment settles in full, nightly billing fatals | NOT ready |
| Raw PHP app | Dead code — web flow never reaches a gateway | Partially working (column/ENUM mismatches) | **Unauthenticated state tampering + any-authenticated-user API abuse** | NOT ready |
| WP theme | Broken end-to-end (`wp_safe_redirect` blocks gateways; no webhooks) | Partially working (ledger divergence) | **Public callback can downgrade settled payments** | NOT ready |
| Node variant | Not implemented (generic CRUD fallback; 501s) | Not implemented | **Unauthenticated API + secret disclosure + IDORs** | NOT ready |
| Branding website | Broken end-to-end (verify dead-code; Stripe/PayPal/Paddle broken) | Working (manual approval) | **License model unenforceable; open license API by default** | NOT ready |

Cross-cutting root causes (present in **every** surface):

1. **No server-side amount/currency reconciliation** — client-supplied or gateway-reported amounts are trusted instead of the payable record.
2. **Fail-open or missing webhook/callback verification** — signatures either skipped (empty header accepted), not implemented, or implemented against the wrong provider scheme.
3. **Payment identity resolved by guessable references** (sequential `payments.id`, predictable `INV-000001` invoice numbers) instead of opaque, bound tokens.
4. **Inconsistent/no authorization and tenant scoping** on payment, refund, gateway, and fee-payment endpoints.
5. **No single ledger semantics** — the side-effect (marking `fee_payments` paid) is not bound to the completion path, so revenue and dues are misreported.
6. **No idempotency / transactions / row locks** on payment completion, refunds, invoice generation, or license issuance.
7. **The green test suites are misleading** — they pass while covering none of the above paths.

---

## 2. Severity definitions & confidence

- **Critical** — exploitable without credentials, or causes irreversible loss/fabrication of money, or breaks a production billing job.
- **High** — breaks an entire flow end-to-end, or lets an authenticated-but-unauthorized actor affect another tenant, or diverges from the provider contract such that real transactions fail.
- **Medium** — data-integrity, privacy, ops, or hardening defects with limited exploitability.
- **Low** — hygiene, UX, and doc-drift items.
- **Confirmed** — proven by executing the code path or by unambiguous static evidence (e.g. a column that does not exist).
- **Risk/unknown** — depends on provider behavior or runtime behavior not exercised during the review (e.g. live sandbox gateway responses). These are flagged explicitly and were **not** asserted as facts.

---

## 3. Laravel app (`eskoofy-laravel-app/`)

Reference implementation; the most complete surface (orchestration, adapters, refunds, recurring, webhook ledger) and the product the other ports must match.

### 3.1 Strengths

- `payment_webhook_events.payload_hash` unique index → race-safe webhook dedup (`createOrFirst`).
- `PaymentGateway` credentials encrypted at rest; `getConfig()` excludes secret keys.
- `Payment`/`FeePayment` audit-logged (spatie/activitylog, `logOnlyDirty`).
- Public refund webhook is fail-closed with signature check (403 before state change).
- Full suite green: **938 passed / 2431 assertions**; Pint clean (1056 files).

### 3.2 Critical (all reproduced by execution)

| ID | Finding | Evidence |
|---|---|---|
| C1 | **Unauthenticated bKash callback settles any invoice in full.** No signature verification; adapter selects the payment with an ungrouped `orWhere` on invoice **or** attacker-supplied `paymentID`, with no amount comparison. A single POST to `POST /api/v1/payments/callback/bkash` completed a 100,000 invoice using an arbitrary `paymentID`. | `app/Http/Controllers/PaymentController.php:219-264`; `app/Services/Payment/BkashGatewayAdapter.php:96-98` |
| C2 | **Identical Nagad flaw** — same ungrouped `orWhere`, `status === 'Success'` accepted with no amount check; no merchant auth header. | `app/Services/Payment/NagadGatewayAdapter.php:91-93` |
| C3 | **Rocket self-approves any pending payment.** `verifyPayment` sets `payment_status=COMPLETED` and fabricates `paid_amount=total_amount` **without contacting Rocket** (see §Appendix for the code). Reachable via unauthenticated callback, `GET /api/v1/payments/status/{payment}`, and the web status page. | `app/Services/Payment/RocketGatewayAdapter.php:72-96` |
| C4 | **Underpayment settles the fee in full.** `initiate` accepts a client-supplied `amount` and never compares it to the fee; side effects then write `paid_amount=$feePayment->amount, balance=0`. Proven: a 5,000.00 fee settled by a **1.00** gateway payment. | `app/Http/Controllers/Web/PaymentsWebController.php:71-122`; `app/Services/Payment/PaymentSideEffects.php:31-36` |
| C5 | **Scheduled recurring billing fatals every night.** `RecurringPaymentService` calls `PaymentService::processRecurringPayment()` which **does not exist**; the `Error` escapes all `catch (\Exception)` blocks, aborts the job on the first due profile, and never rolls back the open transaction. Job is scheduled daily 01:00 (`routes/console.php:22-24`). | `app/Services/RecurringPaymentService.php:135` |

### 3.3 High

| ID | Finding | Evidence |
|---|---|---|
| H1 | `verifyPayment` never applies fee side-effects — money taken, fee ledger never updated (reproduced). | `PaymentSideEffects` vs adapter callbacks |
| H2 | Refund of a payment with null `transaction_id` → uncaught `TypeError`, HTTP 500. | `app/Services/RefundService.php:271`; `PaymentService::processRefund` |
| H3 | Refund of a payment with null `created_by` → violates `refunds.user_id NOT NULL` → 500. | `app/Services/RefundService.php:86` |
| H4 | Any authenticated user can forge a `paid` FeePayment for any student/fee (only `auth:sanctum`). | `app/Http/Controllers/Api/FeePaymentController.php:74-92,303-319` |
| H5 | **Every BD online gateway flow 404s** — seeder points callbacks at `/api/payments/bkash/callback`; the real route is `/api/v1/payments/callback/{gateway}`. | Seeder lines 31,32,35 |
| H6 | Open redirect via client-supplied `return_url`/`cancel_url` + `redirect()->away()`. | `app/Http/Requests/InitiatePaymentRequest.php:24-25`; `PaymentController.php:158-159` |
| H7 | Stripe: always `sandbox_url` regardless of `test_mode`; no webhook timestamp tolerance; `$paid = status==='succeeded'` accepts wrong objects; `complete()` has no status guard → replays resurrect refunded/cancelled payments. | `app/Services/Payment/StripeGatewayAdapter.php` |
| H8 | PayPal `verifyWebhookSignature` is a self-admitted local approximation → real webhooks fail. | `app/Services/Payment/PaypalGatewayAdapter.php:158-185` |
| H9 | Paddle: `vendor_id` missing from checkout URL; signature string built without `&` separators; reads `paddle_public_key` from DB while config defines it. | `app/Services/Payment/PaddleGatewayAdapter.php` |
| H10 | Webhook error path sets `processed_at` → gateway retries silently dropped. | `PaymentController.php:344-349` |
| H11 | No unique refund idempotency; `ProcessRefundJob` has no `$tries`/`ShouldBeUnique`; TOCTOU on refundable amount. | `app/Services/RefundService.php`; `app/Jobs/ProcessRefundJob.php` |
| H12 | Dual divergent ledgers: API `approve` vs dashboard approve disagree on balance mutation; neither is transactional. | `FeePaymentController.php:135-152`; `DashboardFeePaymentController.php:71-87` |
| H13 | Invoice-number races: no unique index, no lock, read-then-insert. | `Payment::generateInvoiceNumber:149-161`; `FeePayment::generateInvoiceNumber:114-126` |
| H14 | `PaymentProcessed` dispatched at 7 sites but `app/Listeners/` does not exist → **no payment/refund notification or receipt is ever sent**. `PaymentRefunded` is never dispatched. | grep of `app/Listeners` |
| H15 | `PaymentResource` returns full `payment_details` (incl. `bkash_token`, raw gateway responses); currency hardcoded to `BDT` because `payments.currency` does not exist. | `app/Http/Resources/PaymentResource.php:41,36`; `RefundService.php:89` |

### 3.4 Notable Medium/Low

- `PaymentPolicy::initiate` returns true for any authenticated user.
- `paymentable_type` stores purposes (`tuition`) not class names → `paymentable` permanently null.
- `PaymentGateway::is_configured` defaults **true**.
- Seeder hardcodes test secrets and `updateOrCreate` clobbers live credentials.
- Request headers incl. `Authorization` logged in callback path (`PaymentController.php:302,315-318`).
- `export` unbounded + CSV injection; `per_page` unbounded; `LIKE` on JSON column.
- Docs-vs-code drift: `API-PAYMENTS.md` omits `/api/v1`, wrong refund path, rate limits documented 100/20/10 vs actual 60; `PAYMENT-MODEL.md` FX/`GATEWAY_BDT_RATE`/subscriptions unimplemented; `.env.example` missing `PAYMENT_DEFAULT_CURRENCY`/`PAYMENT_WEBHOOK_SECRET`, duplicates `ROCKET_WEBHOOK_SECRET`.

### 3.5 Test gaps (Laravel)

- **Zero coverage** for `payments/callback/*`, `return_url`/`cancel_url`, amount-vs-fee reconciliation, and `FeePaymentController`.
- `RefundConcurrencyTest` simulates concurrency with a sequential loop; its "double-processing" test never calls production code (test-local reimplementation). `lockForUpdate` is a no-op on SQLite, so row locking is untested.

---

## 4. Raw PHP app (`eskoofy-php-app/`)

### 4.1 Strengths

- Clean `GatewayInterface` + factory, symmetric with Laravel's adapter split.
- `payment_webhook_events` idempotency ledger in API and web handlers; `hash_equals` signature checks; correct Stripe timestamp parsing in the adapter.
- CSRF on web state-changing routes; dashboard surface centrally role-gated (`config/access.php`).
- Prepared statements throughout; `refundableAmount` dedupe + duplicate-refund guard exist.
- Full suite green: **316 tests / 818 assertions**, incl. per-gateway adapter unit tests.

### 4.2 Critical

| ID | Finding | Evidence |
|---|---|---|
| C1 | **Unauthenticated payment-state tampering (fail-open).** `/api/webhooks/{gateway}/refund` has no middleware and `refundWebhook` does **no signature check and no gateway-existence check** — marks arbitrary `payments.id`/`refunds.id` completed/refunded. `callback()` has no signature check at all. `webhook()` skips verification when the signature header is empty (`if ($signature !== '' && …)`). The Laravel reference is fail-closed by contrast. | `routes/api.php:133,196-198`; `app/Controllers/Api/PaymentController.php:461-526,528-633,639-666` |
| C2 | **Payment/refund/gateway/fee-payment API open to any authenticated user** (students/parents): `updateStatus` sets any payment `completed`, `recordOffline` creates completed payments, `index`/`export` dump all payments, refund `store/process` triggers gateway refunds on any payment, `PaymentGatewayController` edits live config, `FeePaymentController::store` creates `fee_payments` **immediately `status='paid'`** for any student, zeroing balance. | `routes/api.php:200-227`; `app/Controllers/Api/PaymentController.php:210-459`; `Api/RefundController.php:97-216`; `Api/FeePaymentController.php:101-126` |

### 4.3 High

| ID | Finding | Evidence |
|---|---|---|
| H1 | **Web online payment flow can never reach a gateway** — web controller compares `type === 'online'` but the DB ENUM is `bank/mobile_financial_service/online_payment/other`. Branch is dead code; users land on a status page that never verifies. | `app/Controllers/PaymentController.php:119,254`; `database/schema.sql:776` |
| H2 | **Webhook/callback payment-ID resolution broken for every gateway** — reads `payment_id`/`invoice_number` keys that gateways never send (Stripe sends `metadata.invoice_id='INV-00000042'`, PayPal `custom_id`, Paddle `passthrough`), then `(int)`-casts → `0`. Legit webhooks are acked but no payment is updated. | `app/Controllers/Api/PaymentController.php:489,585` |
| H3 | No fee side-effect on completion (missing `PaymentSideEffects` parity) — completed payments leave the student's fee balance outstanding. | vs `PaymentSideEffects.php:15-43` |
| H4 | **DB-stored gateway credentials ignored at runtime** — `GatewayFactory` builds adapters from `.env` config instead of the `payment_gateways` row; gateway can appear "configured" yet fail at the token call. | `app/Gateways/GatewayFactory.php:83-89` |
| H5 | Gateway payloads incomplete vs reference: bKash/Rocket `create` omit `callbackURL`; token grant omits merchant auth headers; `return_url`/`cancel_url` never passed to adapters (PayPal/Paddle order creation fails). | `app/Gateways/BKashGateway.php:43-48`; `RocketGateway.php:40-45`; `Api/PaymentController.php:175-182` |
| H6 | **Schema mismatch breaks dashboard recording** — dashboard reads/writes `amount_paid`; schema defines `paid_amount`. Every dashboard fee-payment insert/update SQL-errors. | `app/Controllers/Dashboard/FeePaymentController.php:85,100,108,113,132`; `database/schema.sql:695` |
| H7 | Public admission `submit-payment` is spoofable — anonymous endpoint flips `payment_status` to `submitted` with a fake `transaction_id` for any admission id. | `routes/web.php:791`; `app/Controllers/SiteController.php:541-575` |
| H8 | Refund state machine defects: writes `status='processed'` but ENUM is `pending/processing/completed/failed/cancelled`; any completed refund marks the **whole** payment `refunded` (even partials); read-check-write race → double refund; no `supportsRefunds()` gate. | `Dashboard/RefundController.php:94`; `Api/RefundController.php:205-211`; `schema.sql:880` |

### 4.4 Notable Medium/Low

- Signature header/key mismatch vs documented `X-Webhook-Signature`/`X-Bkash-Signature` and `PAYMENT_WEBHOOK_SECRET`.
- No amount/currency cross-check at completion (M2); no transactions/locking on initiate/status transitions (M3); open-redirect surface via client `return_url` (M4).
- Documented rate limits not implemented on callbacks/webhooks/initiate.
- IDOR on `show/status/index/export`; `getPaymentMethods` returns values inconsistent with model constants.
- `RefundController::store` hardcodes `currency='BDT'` (violates config-only BD/INT rule).
- Dashboard payment views wired to wrong payload keys (empty cells); public pay page never renders the pay form/history.
- `RecurringPaymentService` mints duplicate pending payments without executing any gateway charge.
- Full request headers incl. auth-bearing values stored in `payment_webhook_events` (privacy).
- ENUM gaps (`paddle`/`offline` missing from `payments.payment_method`); JSON PUT reads `$_POST`.

---

## 5. WordPress theme (`eskoofy-wp-theme/`)

### 5.1 Strengths

- Prepared statements everywhere; nonces + `check_admin_referer` on forms; secrets encrypted at rest (AES-256-GCM); server-side verification pattern (bKash `execute`, Stripe PaymentIntent GET, PayPal capture); consistent escaping + `wp_safe_redirect`; capability-gated dashboard; `.htaccess` blocks `inc/`.

### 5.2 Critical

| ID | Finding | Evidence |
|---|---|---|
| C1 | **Public callback downgrades settled payments.** `GET esk/v1/payments/callback/{gateway}` has `permission_callback => '__return_true'`; looks up the payment **only by guessable `invoice_number`**; on any non-positive verification it **unconditionally sets `payment_status='failed'`** — including for payments already `completed`. Any anonymous visitor can flip settled records and corrupt finance reports. | `inc/rest-api.php:245-253,259-325` (esp. 270, 282, 319) |

### 5.3 High

| ID | Finding | Evidence |
|---|---|---|
| H1 | **No online gateway works end-to-end**: `wp_safe_redirect($init['redirect_url'])` with external gateway URLs (bKash, Rocket, Nagad, PayPal, Paddle) and no `allowed_redirect_hosts` filter anywhere → payer is bounced to the site home page, `pending` records never settle. | `inc/shortcodes.php:400-403`; `inc/payment-gateways.php:187,293,380,614,697` |
| H2 | Callback returns JSON (a `redirect` key), never redirects the browser; no JS consumes it → the payer sees raw JSON and the `?payment=success|failed` notice never appears. | `inc/rest-api.php:312-324` |
| H3 | **No webhook endpoints at all** — no `POST /payments/webhook/{gateway}`, no refund webhook; all `verify_webhook()` implementations are dead code. INT auto-renew (Stripe/PayPal/Paddle) and webhook-driven refunds are unimplemented. | grep: no other `register_rest_route`; `payment-gateways.php:236,329,413,504,652,719` |
| H4 | **No gateway config surface and no seed data** — `esk_payment_gateways` is never populated, the "Payment Gateways screen" referenced by settings does not exist; gateway feature is unavailable out of the box. | `inc/database.php:389-426`; `views/admin/settings.php:224` |
| H5 | **Amount/currency not verified at settlement; payer controls amount.** Form accepts any `$_POST['amount'] > 0`; on completion `esk_fee_payments.paid_amount = $payment->total_amount` (payer-supplied) with `balance=0`, no gateway-amount check, no currency check. | `inc/shortcodes.php:326,341-343`; `inc/rest-api.php:306-307` |
| H6 | **Refunds are a paper ledger** — no gateway refund call, no reduction of `paid_amount`/`due_amount`/fee balance; revenue dashboards stay overstated. Server validation is only `$amount > 0`; refund form offered on pending/failed rows too. | `views/admin/refunds.php:16,32-42,87`; `views/admin/fee-payments.php:102-108` |
| H7 | **Adapter provider-fidelity defects**: PayPal `verify_payment` captures `…/orders/{order_id}/capture` using the **invoice**, not the PayPal order id → always fails; Stripe creates a PaymentIntent never collected (redirect_url is the local callback); Rocket base URLs are placeholder strings containing spaces (`'https://sandbox Rocket api'`); Paddle `verify_webhook` computes HMAC but Paddle signs with RSA (classic) or key-pair/`Paddle-Signature` (2.0). | `inc/payment-gateways.php:258,260,464-467,634,743` |

### 5.4 Notable Medium/Low

- Test-mode toggle is dead config (option vs row split-brain).
- No transaction/locking around two-row settlement; invoice generation read-then-insert with silently-ignored insert failures → orphaned rows.
- Two divergent completion paths (callback vs admin "Mark Paid") — revenue reports sum `esk_fee_payments` while the Payments page reads `esk_payments`.
- No audit trail; no rate limit on public callback; bKash `execute` on every callback hit (status oscillation).
- Secret-decrypt failure silently returns `''`; `wp_salt` rotation breaks stored secrets (no key versioning).
- No `currency` column on `esk_payments`/`esk_fee_payments` → INT (USD) impossible.
- **No automated tests exist for the theme at all**; `phpcs` on the 5 payment files shows 226 style errors (non-gating, none security).

---

## 6. Node variant (`eskoofy-nodejs-app/`)

### 6.1 Strengths

- Structural parity verified: `npm run route:parity` 585/585; `npm test`, `typecheck`, `lint` pass.

### 6.2 Critical

| ID | Finding | Evidence |
|---|---|---|
| C1 | **Unauthenticated generic API over every table.** `/api/v1/[...path]` performs generic REST with **no `currentUser()` and no permission check**; `middleware.ts` protects only `/dashboard` and `/account`. `GET /api/v1/payments`, `/api/v1/refunds`, `/api/v1/payment-gateways` and model writes are open. | `app/api/v1/[...path]/route.ts:16-66`; `middleware.ts:15-35`; `lib/route-registry.ts:20-24` |
| C2 | **Public secret disclosure.** Gateway keys/secrets (Prisma `payment_gateways`) and bKash/app/mail secrets (`website_settings`) returned raw by generic handlers without redaction. Laravel returns allowlisted public fields by contrast. | `prisma/schema.prisma:1282-1319,2138-2215`; `lib/db-query.ts`; `lib/api-response.ts` |
| C3 | **Public fee-receipt IDOR.** `app/(site)/payments/receipts/[id]/page.tsx` loads any sequential `fee_payments` row + related student/fee data without auth or ownership (Laravel requires admin/accountant/staff or linked student). | `app/(site)/payments/receipts/[id]/page.tsx:20-30` |

### 6.3 High

| ID | Finding | Evidence |
|---|---|---|
| H4 | **Public payment-status IDOR** — any payment by sequential id (amount, gateway, invoice, trx) visible. | `app/(site)/payments/status/[id]/page.tsx:18-27` |
| H5 | **Dashboard server-action authorization bypass** — `saveResource()`/`deleteResource()` trust submitted `__table` and never call `can()`; read page is gated but the server actions are not. `markNotificationRead()` updates arbitrary notification IDs. | `app/(dashboard)/dashboard/actions.ts:27-55`; `screen-actions.ts:25-30` |
| H6 | **Payment endpoints collide with generic REST** — `/payments/initiate`, `/record-offline`, `/callback/{gateway}`, `/webhook/{gateway}`, `/refunds/{id}/process` fall through to generic create/read instead of their intended actions. No real initiate, adapter, callback, webhook, refund worker, reconciliation, or side-effect implementation exists (documented in `docs/NOT-IMPLEMENTED.md:70-76`). | `lib/resources.ts:121-141`; `routes.generated.ts:142-152` |

### 6.4 Notable Medium/Low

- Hard-coded dev JWT secret fallback when `AUTH_SECRET` is absent.
- `findUnique` without soft-delete predicate; generic writes return raw DB errors; no payment-specific validation.
- Generated route metadata documents auth/policy middleware but `matchRoute()` never enforces it.
- Public admission-status lookup uses predictable application ids (shared with Laravel).

---

## 7. Branding website (`eskoofy-branding-website/`)

### 7.1 Strengths

- SQL-injection safe (PDO prepared statements, `EMULATE_PREPARES=false`); global CSRF with `hash_equals`; good session hygiene; admin/customer scoping baseline; **server-side prices** (amounts derived from DB plan rows, never client input); manual gateway flow is coherent end-to-end; correct Stripe webhook HMAC; strong license key generation (`random_int`); security headers middleware; 123 tests pass.

### 7.2 Critical

| ID | Finding | Evidence |
|---|---|---|
| C1 | **Online payment completion is dead code — no online sale can complete automatically.** Every fresh payment is `pending`, and the controller treats any pending payment as "manual → wait for admin", so `verify()` is **never reached**; grep confirms no other caller. Customers who pay at any online gateway return to a perpetual "pending" page; no license/subscription/receipt is issued. | `app/Controllers/Site/PaymentStatusController.php:37-48` |
| C2 | **Stripe never collects a card.** A PaymentIntent is created but `redirect_url` is the internal status page; the `client_secret`/`publishable_key` are returned but consumed nowhere (no Stripe Elements/Checkout/SDK). The webhook listens for `checkout.session.completed`/`invoice.paid` events this integration can never produce. | `app/Gateways/StripeGateway.php:47-72`; `WebhookController.php:121` |
| C3 | **PayPal webhooks are always 403** — no `verifyWebhook` implementation exists, so the controller rejects every event. Auto-renew (claimed in copy) is impossible. | `app/Gateways/PaypalGateway.php`; `WebhookController.php:38-49` |
| C4 | **Paddle webhook verification fundamentally broken** — builds a PEM from `base64_encode($vendor_auth_code)` (the vendor auth code is not a public key); every legit classic-Paddle webhook fails `openssl_verify`; the 2.0 `Paddle-Signature` HMAC scheme is not implemented. | `app/Gateways/PaddleGateway.php:84-107` |

### 7.3 High

| ID | Finding | Evidence |
|---|---|---|
| H1 | **Webhook auto-renew has no idempotency and stacks periods** — `createSubscription` extends `expires_at` from current expiry; the test suite explicitly asserts two calls stack **+2 years**; no dedup by event/invoice/transaction id; handler never compares amount/currency with the payment row; first-purchase events are no-ops. | `WebhookController.php:95-116`; `LicenseManager.php:496-544`; `LicenseManagerTest.php:250-268` |
| H2 | `LicenseManager::renew()` **grants extension regardless of payment outcome** — gateway result ignored, license extended and subscription inserted unconditionally. | `LicenseManager.php:438-458` |
| H3 | **License `validate` ignores subscription status** — never checks `subscriptions.status` (past_due/cancelled/expired must reject per `PAYMENT-MODEL.md:69-70`); returns `ok` even when this domain is not activated. Subscription model is effectively unenforceable. | `LicenseManager.php:360-397` |
| H4 | **License API open by default and throttle bypassable** — `LICENSE_PRODUCT_SECRET` optional/unset by default; throttle counter stored **in the session**, so rotating cookies resets the limit. | `LicenseApiController.php:33-44`; `ThrottleMiddleware.php:31-52` |
| H5 | **No transactions/locking → duplicate license issuance** — `if ($paid && empty($payment['license_id'])) { issue(); }` with no lock; two concurrent hits both insert a license. | `PaymentStatusController.php:52-69`; `Admin PaymentController.php:115-126` |
| H6 | **Rocket/Nagad callbacks trust client-supplied status** — `trx_status`/`status` query params mark a payment paid with no provider verification. Latent forgery that becomes live the moment C1 is fixed naively. | `app/Gateways/RocketGateway.php:90-96`; `NagadGateway.php:91-98` |
| H7 | **Gateway secrets stored in plaintext and echoed back to the admin UI** — live credentials at rest in settings rows, rendered into the form; a DB dump/backup exposes all of them. | `app/Controllers/Admin/GatewayController.php:58-74`; `views/admin/gateways.php:32` |

### 7.4 Notable Medium/Low

- Paddle checkout price is a client-tamperable URL param with no reconciliation.
- Duplicate checkout (no idempotency token on POST).
- Open redirect via scheme-relative `//evil.com` in login redirect; `Controller::back()` uses raw `HTTP_REFERER`.
- CORS `Access-Control-Allow-Origin: *` with `Allow-Credentials: true` (invalid combo).
- SMTP TLS verification disabled (`verify_peer=false`).
- No index/unique on `payments.reference`.
- **Refunds/cancellations promised in copy but absent** — no gateway refund call, no cancellation flow; admin can only flip a `refunded` string.
- Activation-cap race + NULL-machine dedup gap (MySQL `NULL` in unique key).
- No email verification; `country` is unvalidated free text and drives currency/gateway routing (weak trust anchor).
- Currency display bug (`views/account/payments.php:22` hardcodes `$` for BDT rows).
- Docs drift: `PAYMENT-DEPLOYMENT.md`/`API-PAYMENTS.md`/`PRODUCTION-CHECKLIST.md` describe Laravel-app artifacts (artisan, queue worker, refund API) that don't exist here.
- State-changing capture/execute on GET navigation; failed webhook signatures not logged; `payments.raw` may store gateway PII; naive `.env` parser; dev `.env` with `APP_DEBUG=true` and inconsistent Paddle vars.

---

## 8. Cross-product parity & consistency assessment

The root `AGENTS.md` mandates feature parity across all four products plus sales copy. The payment system does **not** meet that bar:

1. **Laravel is the reference but is itself non-conformant** with its own docs (rate limits, webhook secrets, `/api/v1` paths, `PAYMENT-MODEL.md` FX/subscriptions unimplemented), so every port inherits the drift.
2. **Each port diverged independently** in ways that would fail any parity gate:
   - Payment identity: `paymentID` vs `invoice_number` vs `metadata.invoice_id` vs `custom_id` vs `passthrough` — each port solves this differently and most solve it wrong.
   - Ledger semantics: API-approve vs dashboard-approve vs callback vs admin "Mark Paid" each mutate balances differently.
   - Config: DB-row credentials vs `.env` (raw PHP ignores the DB row entirely); options vs row split-brain (WP); Prisma vs table (Node).
   - Currency: `payments.currency` absent in Laravel, raw PHP, WP, Node schemas; only the branding site stores currency (per-payment, on the plan), yet even it hardcodes `$` in the account UI.
3. **The BD/INT claim is false where it matters most** — INT (Stripe/PayPal/Paddle) flows are broken in every product, and BD flows are broken in every product except Laravel's partially (and Laravel's has the C1/C3 exploits).
4. **`route:parity` (Node) proves structural parity only** — it passes while the underlying business logic is missing entirely.

---

## 9. Suggestions — implementing a correct payment system across all products

The following is the recommended target architecture and sequence. Apply it to the **Laravel reference first**, then mirror to raw-PHP/WP/Node via `build/propagate/propagate-feature.sh` (php view parity = byte-identical Blade; theme parity = mirrored `views/admin/*` + `inc/`; Node parity = `lib/nav.ts` + Prisma + same API envelope).

### 9.1 Target contract (shared across all five surfaces)

Define one canonical payment contract and reuse it everywhere:

1. **State machine (single, strict):** `pending → processing → completed | failed | cancelled | expired`, `completed → refunded | partially_refunded`. Enforce **forward-only** transitions with a guarded `UPDATE … WHERE status = <expected>` (row lock). Never allow `completed → failed`.
2. **Amount is never client-authored.** Derive `chargeable_amount = min(gateway_verified_amount, outstanding_balance)` server-side at initiation; at settlement require `|gateway_amount − chargeable_amount| ≤ 0.01` **and** matching currency before marking complete.
3. **Payment identity is an opaque token.** Generate a cryptographically random `payment_reference` (≥ 128-bit) at initiation; gateways/callbacks/webhooks address the payment **only** by that token or by the signed provider transaction id — never by sequential `id` or `INV-00000N`.
4. **Webhooks are fail-closed and idempotent.** Reject missing/empty/invalid signatures (constant-time compare), verify timestamp tolerance, dedup by provider `event_id`/`invoice_id` against a unique column or a `payment_webhook_events`-style table, and reconcile amount/currency/invoice before any state change. Store **redacted** payloads.
5. **One settlement routine.** A single `markPaymentCompleted($payment, $verifiedAmount, $currency, $providerRef, $actor)` used by callback, webhook, admin approve, and manual entry. It must, in one DB transaction, (a) update the payment, (b) apply the fee/invoice side-effect (mark `fee_payments` paid, adjust `balance`), (c) record an audit row, and (d) dispatch notifications. There must be exactly one such function per product.
6. **Authorization matrix.** Payment initiation/status for the payer; view/approve/refund/gateway-config for admin/accountant/staff; tenant scoping on every query (student/school/customer). Mirror Laravel's `PaymentPolicy` abilities and `role:admin` gates.
7. **Money columns everywhere:** add `currency`, `gateway_fee`, `verified_amount`, `provider_reference`, `idempotency_key` to `payments`/`refunds`/`fee_payments` in Laravel, raw-PHP schema, WP `esk_*`, Prisma, and the branding `payments`/`subscriptions` tables. Add a **unique index** on `idempotency_key` and on generated invoice numbers.
8. **Refunds:** cap at `min(paid − already_refunded, paid)`; gate on `completed` and `supportsRefunds()`; call the provider refund API with the verified transaction id; track `refunded_amount` on the payment; handle partial vs full; run the refund worker as a unique, retried queue job.
9. **Recurring/subscription:** only advance billing after a **confirmed** provider charge (token-based), with per-cycle idempotency; do not extend `expires_at` by stacking on unconfirmed events; license `validate` must reject past_due/cancelled/expired subscriptions.
10. **BD/INT stays config/data** (`config/payment.php`, `build/profiles/*`, `config/eskoolfy.ts`): no hardcoded `BDT`, no per-product `if (variant==='bd')` in feature code, no hardcoded secrets in seeders.

### 9.2 Provider-conformance checklist (per gateway, per product)

- **bKash:** token grant (merchant auth headers) → `create` with `callbackURL` → server-side `execute`/`queryPayment`; verify `paymentID`, amount, merchant invoice; signature via webhook secret; test-mode `mode` value consistent.
- **Nagad:** `time`/`expires`/`sensitiveData` fields, numeric `currencyCode`, server-side status call; never trust redirect params.
- **Rocket:** real base URLs (no placeholder strings), token acquisition, server-side `queryTransaction`.
- **Stripe:** hosted Checkout Session (`mode=payment`, success/cancel URLs) or PaymentElement using `client_secret`; webhook on `payment_intent.succeeded` with timestamp tolerance; `live_url`/`sandbox_url` driven by `test_mode`.
- **PayPal:** create order → capture using the returned **order id** (not invoice); implement real webhook signature verification (transmission headers + cert chain, pinned).
- **Paddle:** correct signature scheme — classic RSA with Paddle's public key **or** Billing `Paddle-Signature` HMAC (`paddle-{timestamp};h1=…`) over `timestamp:body` with the webhook secret; include `vendor_id` in checkout.
- **Offline/manual:** identical ledger semantics to online settlement; admin approval runs the same `markPaymentCompleted` routine.

### 9.3 Remediation roadmap

**Phase 0 — Stop the bleeding (1–2 days; all products)**
- Laravel: disable/authenticate the public bKash/Nagad callbacks; make Rocket `verifyPayment` call the provider; derive fee amount server-side (C1–C4).
- Laravel: fix seeder callback URLs and disable the recurring schedule until `processRecurringPayment` is implemented (C5, H5).
- Raw PHP: make webhook/callback/refund-webhook fail-closed and require gateway existence (C1).
- WP: make the callback authenticated/forward-only, POST-only, rate-limited, idempotent (C1).
- Node: block unauthenticated access to `/api/v1` payment/refund/gateway/settings resources and add credential redaction (C1, C2, C3).
- Branding: make `PaymentStatusController` actually verify (C1) and stop echoing secrets (H7).

**Phase 1 — Correct completion pipeline (1–2 weeks)**
- Implement the shared settlement contract (§9.1) in Laravel; mirror to raw-PHP, WP, and Node.
- Bind `PaymentSideEffects` to a single completion path; add amount/currency reconciliation everywhere.
- Implement server-side payment resolution by opaque reference + provider transaction id.

**Phase 2 — Provider conformance + webhooks (2–4 weeks)**
- Per-gateway conformance pass (§9.2) in Laravel; then port.
- Add missing webhook endpoints in WP and Node; implement real PayPal/Paddle/Stripe verification in branding and Laravel.

**Phase 3 — Refunds, recurring, subscriptions, authorization (2–4 weeks)**
- Refund correctness per §9.1.8 in all products.
- Recurring billing: implement the missing `processRecurringPayment`, per-cycle idempotency, confirmed-charge-only advancement.
- Branding: enforce subscription status in `validate`, idempotent renewals, transactions + row locks on license issuance, require `LICENSE_PRODUCT_SECRET` in production, server-side throttle.

**Phase 4 — Data integrity, privacy, ops (ongoing)**
- `currency`/`idempotency_key` columns + unique indexes across all schemas; fix ENUM/column mismatches (`amount_paid`, `processed`, `completed_at`, `paddle`/`offline`).
- Stop logging request headers; redact `PaymentResource`; encrypt branding gateway secrets at rest; key-version secret encryption in WP.
- Add per-endpoint rate limits; fix open redirects; fix CORS; re-enable SMTP TLS verification.
- Reconcile all three payment docs and the site copy with reality, or fix the code.

**Phase 5 — Tests that would actually catch these (parallel to Phases 1–4)**
- For each product: (a) callback/webhook with **absent + wrong + replay** signatures → must reject and not mutate; (b) amount-underpay/overpay/currency-mismatch → must reject; (c) student/parent token on payment/refund/gateway/fee endpoints → 403; (d) concurrent double-settlement and double-refund on a **real** transactional DB (MySQL/Postgres, not SQLite); (e) invoice-number uniqueness under concurrency; (f) refund cap and partial-vs-full semantics; (g) webhook idempotency (duplicate delivery applies once); (h) recurring billing only on confirmed charges; (i) branding license `validate` rejecting past_due/cancelled subscriptions; (j) no secret in any API response.
- Add a parity gate beyond structure: `route:parity` must be extended to assert middleware/auth metadata and (where ported) business behavior.

---

## 10. Readiness verdicts by product

| Product | Verdict | Confidence |
|---|---|---|
| Laravel app | NOT production-ready; 5 criticals reproduced by execution | **High** on C1–C4 and H1–H3 (executed); moderate on static H7–H15 |
| Raw PHP app | NOT production-ready; end-to-end online flows dead + fail-open webhooks | **High** for code-confirmed defects; provider-API items flagged as risks |
| WP theme | NOT production-ready for any online flow | **High** for code-level findings; H1/H2 rest on WP core semantics (verify in docker harness); provider-fidelity items flagged as risks |
| Node variant | NOT production-ready; payment business logic absent + critical API exposure | **High** (static + parity gates); behavior not exercised against a live DB |
| Branding website | NOT production-ready for online payments; manual/bank is the only working path | **High** for code defects (verified by source + tests); **Medium** for live provider behavior |

Nothing in this review depends on unverified third-party claims; where provider behavior could not be confirmed without sandbox credentials it is explicitly marked as a risk/unknown and excluded from the confirmed findings.

---

## Appendix — Key reproduced evidence

- **Rocket self-approval** (`eskoofy-laravel-app/app/Services/Payment/RocketGatewayAdapter.php:72-96`): comment reads *"In a real implementation, you would verify the payment with Rocket's API — For this example, we'll assume the verification was successful"*; it then writes `payment_status=COMPLETED`, `paid_amount=total_amount`, `due_amount=0`, fabricates a `TXN{time}{random}` reference, and fires `PaymentProcessed`. Zero HTTP calls.
- **Missing recurring method** (`eskoofy-laravel-app/app/Services/RecurringPaymentService.php:135`): calls `$this->paymentService->processRecurringPayment(...)`; no such method exists on `PaymentService` → nightly `Error` → job aborts, transaction never rolled back.
- **Node generic API** (`eskoofy-nodejs-app/app/api/v1/[...path]/route.ts:16-66`): no authentication or permission check before generic `createRow`/`updateRow`/`deleteRow`/`listRows`.
- **WP public callback** (`eskoofy-wp-theme/inc/rest-api.php:245-253,319`): `permission_callback => '__return_true'`; unverified callbacks force `payment_status='failed'` on existing rows.
- **Branding dead verification** (`eskoofy-branding-website/app/Controllers/Site/PaymentStatusController.php:37-48`): `$pending` is always true for fresh payments (`status='pending'`), so `verify()` is never reached and no online payment can complete.