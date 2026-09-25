# Eskoofy Monorepo — Payment System Implementation Plan

Derived from `docs/reviews/PAYMENT-SYSTEM-REVIEW.md` (2026-09-25). **Nothing in this
document has been implemented yet** — this is the roadmap. Every task maps to a review
finding ID (`C#`, `H#`, `M#`) per product, and to the shared contract sections (§9) of the review.

> **Status legend** (same as `workplan-implementation-plan.md`):
> - ✅ **done** — implemented + verified
> - 🟡 **partial** — implemented with known gaps (listed)
> - ⛔ **blocked/gated** — waiting on a decision or external input (reason given)
> - ⬜ **not started** — out of scope / future phase
> - ⬜ **deferred** — permanently or indefinitely postponed (reason given)

Rules that apply to every change:
- **Feature consistency (root AGENTS.md):** default scope = ALL products. Implement in
  `eskoofy-laravel-app` first (reference), then mirror to `eskoofy-php-app` (byte-identical
  Blade views), `eskoofy-wp-theme` (views/admin/* + inc/), `eskoofy-nodejs-app`
  (`lib/nav.ts` + Prisma + API envelope), and the sales copy in `eskoofy-branding-website`.
  Use `build/propagate/propagate-feature.sh <name> --plan` to present the per-product file
  plan and confirm before editing.
- **Golden rule:** BD/INT differences are config/data only (`config/payment.php`,
  `build/profiles/*`, `config/eskoolfy.ts`). Never hardcode `BDT`, `if (variant === "bd")`,
  or test secrets in seeders.
- **Laravel is the reference.** Ports must not invent divergent behaviors; when a port
  cannot reach parity in a phase, it must return an explicit `501` (Node) or fail closed
  (raw PHP / theme) rather than silently accept money.
- **Verify per product** with the commands in §Final acceptance after each phase.
- **Do not break the byte-identical php view tree**: view changes must be mirrored
  app → php and re-`diff` (per `docs/design/FEATURE-PROPAGATION.md`).

---

## Phase 0 — Stop the bleeding (same-day, all products)

Highest-ROI, security-first. No architecture work — just close the exploitable and
broken paths. Do **not** ship any of these on their own; Phase 1 restores function.

| # | Task | Findings | Product(s) | Acceptance |
|---|---|---|---|---|
| 0.1 | **Lock public bKash/Nagad callbacks.** Add provider signature verification + server-side payment/status verification + amount match before any state change; fail closed on missing/empty signature. Until implemented, return 403/501 instead of completing. | Laravel C1, C2; PHP C1; WP C1 | Laravel, php, theme, node | Unauthenticated callback with arbitrary `paymentID` → rejected 403; amount mismatch → rejected; no `payment_status` mutation on failure |
| 0.2 | **Make Rocket verify for real.** `verifyPayment` must call the Rocket provider API (token → queryTransaction); never self-complete. | Laravel C3 | Laravel | A pending payment only completes when the provider confirms; `GET /api/v1/payments/status/{id}` cannot fabricate completion |
| 0.3 | **Derive chargeable amount server-side.** Ignore client-supplied `amount`; compute `min(fee.amount − paid, fee.amount)`; store it; never let a 1.00 payment settle a 5000.00 fee. | Laravel C4; WP H5; PHP M2 | Laravel, php, theme, node | Underpay/overpay/currency-mismatch rejected at initiation and at settlement |
| 0.4 | **Fix seeder callback URLs** so BD online flows reach the real `/api/v1/payments/callback/{gateway}` routes. | Laravel H5 | Laravel, php | Seed produces a working `POST /payments/initiate` → callback round-trip |
| 0.5 | **Stop the nightly recurring fatals.** Implement `PaymentService::processRecurringPayment()` (or remove the call) and catch `\Throwable` at the job boundary; add `--test` that truly skips processing. | Laravel C5, M(recurring) | Laravel, php | `schedule:run` no longer exits 255; due profiles processed or explicitly skipped |
| 0.6 | **Make webhook/callback/refund-webhook fail closed in raw PHP.** Reject empty signature headers; require the gateway row to exist; never trust client-supplied `payment_id`; resolve by server-side verification. | PHP C1 | php | Empty/missing signature → 403 before any mutation; unknown gateway → 404 |
| 0.7 | **Harden the WP public callback.** POST-only, per-IP rate limit, forward-only transitions (pending/processing → completed/failed, never completed → failed), opaque per-payment auth token instead of guessable `invoice_number`. | WP C1, M5, M6 | theme | Anonymous GET cannot downgrade a `completed` payment; replay idempotent |
| 0.8 | **Block unauthenticated Node API + redact secrets.** Require a session/user on `/api/v1` payment/refund/gateway/settings resources; add field allow-lists and credential redaction to responses; protect fee-receipt and payment-status pages with session + ownership checks. | Node C1, C2, C3, H4 | node | Unauthenticated `GET /api/v1/payments|refunds|payment-gateways|website-settings` → 401; no secret key appears in any response; `/payments/receipts/123` and `/payments/status/123` require session + ownership |
| 0.9 | **Branding: actually verify online payments.** Make `PaymentStatusController` run `verify()` for any non-paid/non-cancelled payment (drop the always-true `$pending` OR-term); gate the issue branch on a confirmed provider result; never trust `?status=`/`?trx_status=` query params. | Branding C1, H6 | website | A completed bKash/Stripe/PayPal/Paddle return verifies server-side, marks paid, issues license; forged `?status=success` rejected |
| 0.10 | **Branding: stop echoing gateway secrets.** Mask secrets in the admin form; encrypt at rest (env-derived key) or move to `.env`; don't render live values. | Branding H7 | website | Admin gateways page shows masked values; DB dump contains no plaintext secrets |

---

## Phase 1 — Shared payment contract (reference-first)

Define one canonical contract in `eskoofy-laravel-app`, then mirror it. This is the
foundation every later phase builds on. Review §9.1 is the spec.

| # | Task | Findings | Product(s) | Acceptance |
|---|---|---|---|---|
| 1.1 | **Single strict state machine.** `pending → processing → completed | failed | cancelled | expired`; `completed → refunded | partially_refunded`. Enforce forward-only transitions with guarded `UPDATE … WHERE status = <expected>` + row lock. | All C/H | Laravel, php, theme, node | Test: `completed → failed` and `completed → completed` rejected at the DB layer |
| 1.2 | **Opaque payment reference.** Generate a ≥128-bit random `payment_reference` at initiation; callbacks/webhooks/status lookups address payments only by it or by the signed provider transaction id — never sequential `id`/`INV-00000N`. | Laravel H13; PHP H2; WP C1; Node H6 | Laravel, php, theme, node | No payment lookup path accepts a guessable invoice number alone; migration seeds references for existing rows |
| 1.3 | **Idempotency keys.** Add `idempotency_key` with a unique index on `payments` and `refunds`; honour it on initiate, callback, webhook, and refund worker. | Laravel H11; WP M2; Branding H1 | Laravel, php, theme, node, website | Duplicate delivery of the same key/event applies once; concurrent identical requests yield one row |
| 1.4 | **Single completion routine.** One `markPaymentCompleted($payment, $verifiedAmount, $currency, $providerRef, $actor)` per product, used by callback, webhook, admin approve, and manual entry. In one transaction: update payment → apply fee side-effect → audit row → dispatch notifications. | Laravel H1, H12; PHP H3; WP M3 | Laravel, php, theme, node | All completion paths run through the same function; fee ledger and payment agree post-transaction |
| 1.5 | **Amount/currency reconciliation helper.** Shared predicate: `|gateway_amount − chargeable_amount| ≤ 0.01` AND currency match required before completion. Wire it into every completion path. | Laravel C1–C4; PHP M2; WP H5; Branding M1 | Laravel, php, theme, node, website | No completion path can run without a passing reconciliation check |
| 1.6 | **Money columns everywhere.** Add `currency`, `gateway_fee`, `verified_amount`, `provider_reference`, `idempotency_key` to `payments`/`refunds`/`fee_payments` in Laravel migrations, php `database/schema.sql`, WP `esk_*` tables, Node Prisma, and branding `payments`/`subscriptions`. Unique index on generated invoice numbers. | Laravel H15; WP M8; Node C2; php L1 | Laravel, php, theme, node | `schema.sql`/Prisma/migrations in sync; `currency` flows from payment to refund to fee ledger |
| 1.7 | **Authorization matrix parity.** Mirror Laravel `PaymentPolicy` abilities + `role:admin|accountant` gates in php (`config/access.php` + API middleware), theme (caps), Node (server-action + API enforcement), branding (role + tenant scope). | PHP C2, M6; Node H5; Laravel H4 | Laravel, php, theme, node, website | Student/parent token on payment/refund/gateway/fee endpoints → 403; admin passes |
| 1.8 | **Payment identity resolution.** Central service mapping provider keys → payment: bKash `paymentID`/`merchantInvoiceNumber`, Stripe `metadata.invoice_id`, PayPal `custom_id`, Paddle `passthrough` → the opaque reference. | PHP H2; WP H7 | Laravel, php, theme | Legit webhook for each gateway resolves and updates the correct payment (tested) |
| 1.9 | **Node: decide payment implementation vs explicit 501.** Either implement the real adapters/workers, or make every payment endpoint return `501` and never fall through to generic CRUD. Route collisions (initiate/record-offline/callback/webhook/refund process) must be removed from generic matching. | Node H6 | node | No payment endpoint silently performs a generic create/update; unimplemented actions return explicit 501 |

---

## Phase 2 — Completion pipeline & ledger

Make "money taken" always equal "ledger updated" and "notifications sent".

| # | Task | Findings | Product(s) | Acceptance |
|---|---|---|---|---|
| 2.1 | **Bind side effects to the single completion path.** `PaymentSideEffects` runs only from `markPaymentCompleted`; `verifyPayment` and callbacks converge on it. | Laravel H1; PHP H3; WP M3 | Laravel, php, theme | `Payment=completed` ⇒ `FeePayment.status=paid`, `paid_amount`, `balance` consistent |
| 2.2 | **Unify approve/ledger semantics.** API approve, dashboard approve, web approve share one transactional service; remove the two divergent balance mutations. | Laravel H12; WP M3 | Laravel, php, theme | Both UIs produce identical `fee_payments` rows for the same input |
| 2.3 | **Wire real event listeners/notifications.** Create `app/Listeners/` handlers for `PaymentProcessed`/`PaymentRefunded`; dispatch `PaymentRefunded`; send receipts/notifications on completion and refund. | Laravel H14 | Laravel, php, theme | Completing a payment produces a notification/receipt record; refund produces one too |
| 2.4 | **Fix invoice-number generation.** Add unique index + generation under lock or atomic sequence; detect and retry collisions. | Laravel H13; WP M2 | Laravel, php, theme | Concurrent initiations never mint duplicate invoice numbers |
| 2.5 | **Transaction + locking on settlement.** Wrap two-row updates (payment + fee) in a transaction; guard with `lockForUpdate`/conditional update. | WP M2; PHP M3; Branding H5 | Laravel, php, theme, node, website | Interrupted settlement rolls back atomically; concurrent double-issue impossible |

---

## Phase 3 — Provider conformance & webhooks

Per-gateway correctness against the provider's actual contract. Review §9.2 is the checklist.

| # | Task | Findings | Product(s) | Acceptance |
|---|---|---|---|---|
| 3.1 | **bKash conformance.** Token grant (merchant auth headers) → `create` with `callbackURL` → server-side `execute`/`queryPayment`; verify `paymentID`, amount, merchant invoice; webhook signature; consistent test-mode `mode` value. | PHP H5; WP M6; Laravel C1 | Laravel, php, theme, node | Sandbox round-trip: initiate → pay → callback completes exactly one payment |
| 3.2 | **Nagad conformance.** `time`/`expires`/`sensitiveData` fields, numeric `currencyCode`, server-side status; never trust redirect params; merchant auth header. | Laravel C2; WP (Nagad) | Laravel, php, theme | Sandbox round-trip passes; tampered redirect params rejected |
| 3.3 | **Rocket conformance.** Real base URLs (no placeholder strings), token acquisition, server-side `queryTransaction`. | Laravel C3; WP H7 | Laravel, php, theme | `verifyPayment` only completes on confirmed provider status |
| 3.4 | **Stripe conformance.** Hosted Checkout Session (`mode=payment`, success/cancel URLs) or PaymentElement with `client_secret`; webhook `payment_intent.succeeded` with timestamp tolerance; `live_url`/`sandbox_url` driven by `test_mode`; status guard in `complete()` so replays can't resurrect refunded/cancelled. | Laravel H7; Branding C2 | Laravel, php, theme, node, website | Card is actually collected; webhook completes payment; replay of old event on refunded payment rejected |
| 3.5 | **PayPal conformance.** Create order → capture using the returned **order id** (not invoice); real webhook signature verification (transmission headers + pinned cert chain). | Laravel H8; WP H7; Branding C3 | Laravel, php, theme, node, website | Capture succeeds; real webhook validates (not the local approximation); 403 → valid |
| 3.6 | **Paddle conformance.** Correct signature scheme — classic RSA with Paddle public key **or** Billing `Paddle-Signature` HMAC (`paddle-{timestamp};h1=…`) over `timestamp:body`; include `vendor_id`; signature string built with `&` separators. | Laravel H9; Branding C4; WP H7 | Laravel, php, theme, node, website | Legit webhook validates; checkout URL contains `vendor_id` |
| 3.7 | **Webhook endpoints everywhere.** Add missing `POST /payments/webhook/{gateway}` (theme, node) and refund webhook; call `verify_webhook`; fail closed; dedupe by payload hash; store redacted payloads. | WP H3; PHP M1 | Laravel, php, theme, node | Each gateway's webhook delivers once and mutates exactly the right payment |
| 3.8 | **Webhook retry semantics.** On invalid signature/verification, respond with a non-2xx that lets providers retry; never set `processed_at`/complete on error; log redacted + alert on repeated failures. | Laravel H10; Branding L3 | Laravel, php, theme, node, website | Gateway retry after a transient failure succeeds on the second delivery |
| 3.9 | **Return-URL safety.** Allow-list `return_url`/`cancel_url` to same-origin routes (or validated gateway URLs); remove `redirect()->away()` on arbitrary client input; fix protocol-relative open redirects in branding login/back. | Laravel H6; PHP M4; Branding M3, L2 | Laravel, php, theme, node, website | Off-host/protocol-relative redirect URLs rejected; `/`-only accepted |

---

## Phase 4 — Refunds, recurring, subscriptions, authorization

| # | Task | Findings | Product(s) | Acceptance |
|---|---|---|---|---|
| 4.1 | **Refund correctness.** Cap at `min(paid − already_refunded, paid)`; gate on `completed` + `supportsRefunds()`; guard `transaction_id`/`created_by` nulls; track partial vs fully refunded; call the provider refund API with the verified transaction id; conditional locking around refundable-amount reads. | Laravel H2, H3, H11; PHP H8; WP H6 | Laravel, php, theme, node | Partial refund leaves payment `partially_refunded` with `refunded_amount`; double-refund rejected; null trx/created_by → clean 422 not 500 |
| 4.2 | **Refund worker hardening.** `ShouldBeUnique` + unique idempotency key + `$tries` on `ProcessRefundJob`; dashboard refund form honors `refund_amount`. | Laravel H11; PHP M9 | Laravel, php, theme | Duplicate job submissions process once; dashboard partial refund respects the entered amount |
| 4.3 | **Refund webhook allow-list + amount match.** Include Stripe/PayPal; resolve by transaction id, not `transaction_id IS NULL`; verify amount. | Laravel M(refund webhook) | Laravel, php, theme | Refund webhooks for all supported gateways work and are amount-checked |
| 4.4 | **Recurring billing implementation.** Implement confirmed-charge-only recurring: token → provider charge → advance `next_billing_date`; per-cycle idempotency; no duplicate pending rows; payment failure recorded and retried, never silently skipped. | Laravel C5; PHP M12 | Laravel, php | A due profile is charged once per cycle; failures retried without minting duplicates; `--test` runs nothing |
| 4.5 | **Branding subscription enforcement.** License `validate` rejects `past_due`/`cancelled`/`expired` subscriptions and enforces activation on this domain; `renew()` branches on confirmed provider success; webhook auto-renew is idempotent (dedup by event id) + amount/currency reconciled + never stacks on unconfirmed events. | Branding H1, H2, H3 | website | Cancelled subscription → validation fails; duplicate webhook delivery extends once; failed renewal does not extend |
| 4.6 | **Branding license issuance atomicity.** Transaction + `SELECT … FOR UPDATE` (or `INSERT … ON DUPLICATE KEY`) so concurrent hits/admin approval never issue two licenses; unique `(payment_id)` license link. | Branding H5, M8 | website | Two concurrent completions of the same payment yield exactly one license |
| 4.7 | **Branding license API auth + throttle.** Require `LICENSE_PRODUCT_SECRET` in production; server-side (DB-backed) throttle keyed by IP + hashed key, not session. | Branding H4 | website | Rotating cookies no longer resets the limit; unauthenticated activation fails in production |
| 4.8 | **Close API authorization gaps.** `FeePaymentController::store` admin-gated + policy-protected; `updateStatus`/`recordOffline` role-gated; gateway config CRUD admin-only + tenant-scoped; dashboard server actions (Node) re-run permission checks. | Laravel H4; PHP C2; Node H5 | Laravel, php, theme, node | Any non-admin token is denied across the whole payment/refund/gateway/fee surface |
| 4.9 | **Admission payment binding.** Bind public admission `submit-payment` to the applicant (email/phone/reference token) so third parties can't flip payment status. | PHP H7 | php, theme | Unknown admission ids without the applicant token → 404; status flip requires the bound token |

---

## Phase 5 — Data integrity, privacy, ops hardening

| # | Task | Findings | Product(s) | Acceptance |
|---|---|---|---|---|
| 5.1 | **Fix column/ENUM mismatches.** php: `amount_paid` → `paid_amount`; `status='processed'` → valid ENUM; `completed_at` → `processed_at`; add `paddle`/`offline` to `payment_method` ENUM; sync `getPaymentMethods` values with model constants. | PHP H6, H8, L1, L3, M7 | php | Dashboard recording and refund processing work against the real schema |
| 5.2 | **Secret hygiene.** Encrypt branding gateway secrets at rest; WP key-versioned encryption (surface decrypt failures loudly, re-encrypt on save); stop logging request headers (incl. `Authorization`); redact `PaymentResource`; php: stop storing full raw headers. | Laravel H15/M; Branding H7; WP M7; PHP L4 | Laravel, php, theme, website | No secret or auth header in logs/API responses/DB dumps |
| 5.3 | **Currency correctness.** Remove hardcoded `BDT` (Laravel `PaymentResource`, `RefundService`; php refund controller); derive currency from payment/gateway rows; fix branding account payments `$` display for BDT. | Laravel H15; PHP M8; Branding M10 | Laravel, php, theme, website | BDT payments display and refund in BDT; USD in USD; no hardcoded currency in feature code |
| 5.4 | **Rate limits + pagination + export.** Add the documented rate limits (callbacks/webhooks/initiate); bound `per_page`/`export`; fix `LIKE` on JSON column; sanitize CSV export (formula-injection). | Laravel M; PHP M5; WP M5 | Laravel, php, theme | Limits enforced at documented values; oversized params rejected; CSV opens safely in Excel |
| 5.5 | **Gateway config correctness.** php: adapters constructed from DB-row-over-config merged config; Laravel: seeder doesn't clobber live creds, uses correct keys, no hardcoded test secrets; `PaymentGatewayController` can set `api_username`/`api_password`/`sandbox_url`/`live_url`; WP: real Payment Gateways admin CRUD + seed + single source of truth for `test_mode`. | PHP H4; Laravel M; WP H4, M1 | Laravel, php, theme | Gateways run on the credentials entered in the UI; test-mode toggle actually switches sandbox/live |
| 5.6 | **WP invoice/two-table integrity.** Check `$wpdb->insert` results; roll back on failure; single completion routine; remove inline-JS `confirm`; scope Stripe/`redirect_url` to hosted checkout. | WP M2, M3, L4, L5, H7 | theme | No orphaned `fee_payments`/`payments` rows; insert failures surfaced, not silent |
| 5.7 | **Branding ops hardening.** Fix CORS `*` + credentials; re-enable SMTP TLS verification; index/unique on `payments.reference`; robust `.env` parser; validate `country` (allow-list from geo/provider); fix admin-created customer password flow. | Branding M4, M5, M6, M9, L5, L6 | website | Spec-compliant CORS; SMTP verifies peer; lookup by reference uses an index; country values constrained |
| 5.8 | **Audit trail.** Record actor + redacted payload on every payment/refund/gateway state change across products (php activity log, WP `esk_activity`, Laravel activitylog already present, Node audit rows). | PHP L5; WP M4 | Laravel, php, theme, node | Every status change is attributable to an actor with a redacted payload |

---

## Phase 6 — Tests & parity gates

Tests that would have caught the review findings. Add them **alongside** Phases 0–5, not after.

| # | Task | Findings | Product(s) | Acceptance |
|---|---|---|---|---|
| 6.1 | **Signature fail-closed tests.** Callback/webhook with absent, wrong, replay signatures → rejected, no state mutation, for every gateway. | Laravel C1/C2, PHP C1, WP C1, Branding C3/C4 | Laravel, php, theme, node, website | Test asserts 403 + unchanged row |
| 6.2 | **Amount/currency reconciliation tests.** Underpay, overpay, currency-mismatch on every completion path → rejected. | Laravel C4, WP H5, Branding M1 | Laravel, php, theme, node, website | Test asserts no state change + error response |
| 6.3 | **Authorization denial tests.** Student/parent token on payment/refund/gateway/fee endpoints → 403; Node: unauthenticated + cross-tenant receipt/status → 401/403. | Laravel H4, PHP C2, Node C3/H4/H5 | Laravel, php, theme, node | Denials asserted per endpoint |
| 6.4 | **Concurrency tests on a real transactional DB** (MySQL/Postgres, not SQLite): double-settlement, double-refund, duplicate invoice numbers, concurrent license issuance. Replace the sequential `RefundConcurrencyTest` simulation. | Laravel H11/H13, WP M2, Branding H5 | Laravel, php, theme, node, website | One of N concurrent operations succeeds; rest rejected |
| 6.5 | **Webhook idempotency + retry tests.** Duplicate delivery applies once; retry after transient failure succeeds. | Laravel H10/H11, PHP C1, Branding H1 | Laravel, php, theme, node, website | Asserted per gateway |
| 6.6 | **Refund semantics tests.** Partial vs full; cap enforcement; null `transaction_id`/`created_by` → 422; only `completed` refundable. | Laravel H2/H3, PHP H8, WP H6 | Laravel, php, theme | Asserted |
| 6.7 | **Recurring/subscription tests.** Confirmed-charge-only; per-cycle idempotency; branding `validate` rejects past_due/cancelled; renew only on success; no period stacking. | Laravel C5, PHP M12, Branding H1/H2/H3 | Laravel, php, website | Asserted |
| 6.8 | **Secret-exposure tests.** No API/log/DB-dump response contains a secret; `PaymentResource` redacts; header logging redacts `Authorization`. | Laravel H15, Node C2, Branding H7 | Laravel, php, theme, node, website | Automated scan on the payment surface |
| 6.9 | **Extend parity gates beyond structure.** `route:parity`/propagation must also assert auth middleware metadata and — where ported — business behavior (a shared "payment parity contract" test per product). | Node H6, cross-product | Laravel, php, theme, node | A payment-route change in Laravel fails the gate until the port catches up functionally, not just structurally |
| 6.10 | **Refund test rewrite.** Rewrite `RefundConcurrencyTest` against a real concurrent harness with the production code path. | Laravel M(test) | Laravel | Test fails if locking is removed |

---

## Phase 7 — Docs & sales-copy reconciliation

Make every document and marketing claim match the shipped implementation.

| # | Task | Findings | Product(s) | Acceptance |
|---|---|---|---|---|
| 7.1 | **Fix `API-PAYMENTS.md`**: add `/api/v1` prefix everywhere; correct refund path; document actual rate limits and webhook-secret source. | Laravel M(docs) | docs | Doc matches `route:list` and running config |
| 7.2 | **Update `PAYMENT-DEPLOYMENT.md`**: correct webhook headers/secrets (dedicated `*_WEBHOOK_SECRET`, `X-Webhook-Signature`), remove the phantom PostgreSQL/MySQL-only claim, document per-gateway ops. | Laravel M(docs), PHP M1 | docs | Doc matches code |
| 7.3 | **Reconcile `PAYMENT-MODEL.md`** with implementation: implement FX (`GATEWAY_BDT_RATE`), subscriptions, and dunning — or mark them deferred. | Laravel M(docs), Branding H3 | docs, website | Model doc and code agree |
| 7.4 | **Fix `.env.example`**: add `PAYMENT_DEFAULT_CURRENCY`, `PAYMENT_WEBHOOK_SECRET`; remove duplicate `ROCKET_WEBHOOK_SECRET`. | Laravel M(docs) | Laravel | `.env.example` is consistent with `config/payment.php` |
| 7.5 | **Branding copy vs implementation.** Align `USER-MANUAL.md`, `PAYMENT-DEPLOYMENT.md`, `PRODUCTION-CHECKLIST.md`, and site copy (auto-renew, refunds within 10 business days) with the actual shipped behavior; implement refunds (§4.1) or correct the copy. | Branding M7, M11 | website, docs | No marketing claim contradicts code; refund policy either implemented or reworded |
| 7.6 | **Ops env hygiene.** Branding prod `.env`: `APP_DEBUG=false`, real `APP_URL`, `LICENSE_PRODUCT_SECRET`, consistent Paddle vars. | Branding L7 | website | Ops checklist item |

---

## Phase 8 — Final acceptance

Run the full verification matrix:

| Gate | Command |
|---|---|
| Laravel suite + style | `cd eskoofy-laravel-app && composer test && ./vendor/bin/pint --test` |
| Raw-PHP parity + suite | `cd eskoofy-php-app && composer test` + app↔php view/lang `diff -qr` clean |
| Theme syntax/lint | `cd eskoofy-wp-theme && php -l inc/…` on touched files + `composer run lint` |
| Node | `cd eskoofy-nodejs-app && npm test && npm run typecheck && npm run route:parity && npm run lint` |
| Website | `cd eskoofy-branding-website && composer test` |
| Route parity | php `routes/api.php` == app `route:list` (see php AGENTS.md) |
| Sandbox E2E | real sandbox round-trip per gateway in Laravel (bKash, Nagad, Rocket, Stripe, PayPal, Paddle) then ports |

Manual smoke (each product):
- Unauthenticated callbacks/webhooks with wrong/empty/replay signatures → rejected, no mutation.
- Student/parent token on any payment/refund/gateway/fee endpoint → 403.
- Underpay/overpay/currency-mismatch → rejected.
- Online payment completes exactly once and updates the fee ledger; refund is partial-correct; recurring charges once per cycle; branding `validate` rejects cancelled subscriptions.
- No secret appears in any response, log, or DB dump.

Update `workplan-implementation-plan.md` with a "Phase 12 — Payment system remediation" section as items land (✅/🟡/⛔ per task).

---

## Order of execution (effort estimate)

| Phase | Effort | Depends on |
|---|---|---|
| 0 Stop the bleeding | 2–4 days | — |
| 1 Shared payment contract | 3–5 days | 0 |
| 2 Completion pipeline & ledger | 2–3 days | 1 |
| 3 Provider conformance & webhooks | 3–6 days (per gateway; sandbox keys needed) | 1, 2 |
| 4 Refunds, recurring, subscriptions, authz | 3–5 days | 1, 2 |
| 5 Data integrity, privacy, ops | 2–3 days | 1 |
| 6 Tests & parity gates | ongoing (parallel with 0–5) | — |
| 7 Docs & copy | 1–2 days | 3–5 |
| 8 Acceptance | 1 day | all |

Total ≈ **17–30 engineering days across products**, with Phases 0–1 front-loaded. Provider conformance (Phase 3) is the highest-risk slice — it needs real sandbox credentials and cannot be fully verified statically.

## Open decisions (resolve before Phase 1)

1. **Node payment depth:** full port of adapters/workers, or explicit `501` + guarded routes until a later phase? (Task 1.9.) Recommended: real adapters, since `route:parity` already promises the surface.
2. **Refund policy for Paddle:** keep refunds dashboard-offline-only (current Laravel stance) or implement provider refunds?
3. **Dunning scope:** do INT subscriptions get dunning emails in the branding site now, or is that deferred copy-only? (`PAYMENT-MODEL.md` claims it.)
4. **Concurrency test DB:** stand up a MySQL/Postgres test service in CI for the concurrency suite, or document that these run locally only?

## Propagation appendix

Per-product file map for the shared-contract work (Phase 1), mirroring `docs/design/FEATURE-PROPAGATION.md`:

| `eskoofy-laravel-app` | `eskoofy-php-app` | `eskoofy-wp-theme` | `eskoofy-nodejs-app` | `eskoofy-branding-website` |
|---|---|---|---|---|
| `app/Services/Payment/**` + `PaymentService.php` | `app/Gateways/**` + `app/Services/PaymentService.php` | `inc/payment-gateways.php` | `lib/payments/**` (new) | `app/Gateways/**` |
| `app/Services/RefundService.php` | `app/Services/RefundService.php` | `views/admin/refunds.php` + `inc/rest-api.php` | `app/api/v1/refunds/**` | `app/Services/LicenseManager.php` |
| `app/Services/PaymentSideEffects.php` | `app/Services/PaymentSideEffects.php` | `inc/rest-api.php` (completion) | `lib/payments/side-effects.ts` | `app/Services/LicenseManager.php` |
| migrations (`payments`/`refunds`/`fee_payments`) | `database/schema.sql` | `inc/database.php` (`esk_*` tables) | `prisma/schema.prisma` | `database/schema.sql` |
| `routes/payments.php` + `routes/refunds.php` | `routes/api.php` + `routes/web.php` | `inc/rest-api.php` routes | `lib/routes.generated.ts` + `lib/route-registry.ts` | `routes/web.php` + `routes/api.php` |
| `config/payment.php` + `.env.example` | `config/payment.php` | `inc/helpers.php` + settings | `config/eskoolfy.ts` + `.env.example` | `config/` + `.env.example` |
| `app/Http/Resources/PaymentResource.php` | API controllers' presenters | REST response handlers | `lib/api-response.ts` + resources | admin/site views |
| policies (`PaymentPolicy`) | `config/access.php` + `RoleMiddleware` | `esk_can_access_page()` caps | middleware + server actions | `AuthMiddleware`/`AdminMiddleware` |