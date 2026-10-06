# Master prompt — Zero-cost test (sandbox) payment gateway, more BD payment gateways, branding header/footer responsiveness, admin dashboard variant-filter removal

> Status: **EXECUTING** — this prompt is the single source of truth for the work below.
> Execute every task in order. Do not stop until all acceptance criteria pass.
>
> Origin: user request in-session (sandbox/test gateway with zero cost, more Bangladeshi
> payment gateways + aggregators/UddoktaPay-like services across all products and the
> branding website, branding header/footer fully responsive, remove the variant
> link/filter from the branding admin dashboard, record everything in feature tracking).

---

## Context

Eskoofy is a 4-product monorepo plus a branding/licensing website:

| Folder | Product | Stack |
|---|---|---|
| `eskoofy-laravel-app/` | School management app (**reference product**) | Laravel 12 |
| `eskoofy-php-app/` | Raw PHP rewrite (shared hosting) | Raw PHP 8.2 |
| `eskoofy-wp-theme/` | WordPress theme (plugin-hybrid) | WP theme |
| `eskoofy-nodejs-app/` | Node.js clone of the app | Next.js + Prisma |
| `eskoofy-branding-website/` | Marketing site + license server (NOT a product) | Raw PHP |

BD/INT are build-time profiles — never forks. The Laravel app is the reference; the other
products follow it (`docs/design/FEATURE-PROPAGATION.md`).

### What already exists (do not rebuild)

- **Gateway architecture (all 4 products)**: DB row (`payment_gateways` / `esk_payment_gateways`
  / settings / prisma) holds runtime creds + `test_mode`; bespoke adapters dispatched from a
  factory; `GenericHostedGateway` / `GenericHostedGatewayAdapter` is the config-driven fallback
  for unknown codes (keys: `sandbox_url`/`live_url` picked by `test_mode`, `extra_attributes`
  contract: `checkout_method`, `checkout_url_template` with `{amount} {currency} {reference}
  {invoice} {callback} {cancel} {api_key}`, `verify_url`, `verify_success_path`/`verify_success_value`,
  `refund_url`, `signature_header`).
- Existing gateways: BD = `bkash`, `rocket`, `nagad`, `uddoktapay` (+ offline `cash`,
  `bank_transfer`, `cheque`); INT = `stripe`, `paypal`, `paddle` (+ offline).
- Laravel `PaymentService` already references a `test_gateway` code in refunds
  (`processRefund` short-circuit, `supportsRefunds`) but **no adapter/seeder row exists** —
  this task lands it for real.
- **No zero-credential test gateway exists in any product today** — greenfield.
  `test_mode` only picks sandbox vs live URL; it does not simulate results.
- Branding website has **no `payment_gateways` table** — gateways live in
  `config/gateways.php` + `settings` overrides (`gateway.<code>.<field>`), dispatched by
  `App\Gateways\GatewayFactory`.

---

## Task 1 — Test / sandbox payment gateway (zero cost, all 5 codebases)

A **credential-free test gateway** so admins/developers can verify the full payment
pipeline (init → hosted page → callback → verify → paid → license/fees flow) with
**zero money and zero third-party dependency**.

### 1.1 Shared contract (identical semantics everywhere)

| attribute | value |
|---|---|
| code | `test_gateway` (Laravel's refund code already expects exactly this) |
| label | `Test / Sandbox` |
| credentials | **none** — `is_configured`/`configured` is always `true` |
| active by default | `true` in dev/sandbox profile; seeder row `is_active = 1` (safe: it never charges) |
| test_mode | irrelevant (no live URL); ignore for this gateway |

**Flow**

1. `initialize`/`process` creates (or uses) the payment row, then **redirects to a local
   sandbox page** owned by the product — never an external host:
   - Laravel: `GET /payments/sandbox/{payment}` (name it `payments.sandbox` route)
   - php app: same route shape in `routes/web.php`
   - theme: front-dashboard page `esk-payment-sandbox`
   - node: `app/(dashboard)/…` or public route mirroring Laravel's URL
   - branding: `GET /checkout/sandbox/{reference}`
2. The sandbox page shows gateway label, amount + currency, reference/invoice id, and
   three submit buttons: **Simulate success**, **Simulate failure**, **Cancel** —
   plus a short explainer ("free test payment, no money moves").
3. Each button POSTs (CSRF-protected where the product has CSRF) `simulate=success|failure|cancel`
   back to the product's callback endpoint with the payment identified.
4. Callback behaviour:
   - `success` → mark payment `paid` (via the same verify/issue path used by real gateways),
     `transaction_id = TEST-<reference>`, then redirect to the product's normal
     success surface (fees receipt / license page / payment-status page).
   - `failure` → mark `failed`, redirect back with an error flash.
   - `cancel` → mark `cancelled`/`failed` (per product's existing status vocabulary),
     redirect with a cancelled flash.
5. **Verify is idempotent**: a `paid` test payment verifies as paid again without side effects.

**Admin diagnostics panel (all 4 products + branding admin)**

On the existing payments/gateways admin screen add a compact table with one row per gateway:
`code · label · active · test_mode · configured(yes/no) · adapter/driver resolved` and a
"Run test payment" action linking to the sandbox flow. This makes "is this gateway
wired up correctly?" answerable without spending money. Branding website: same idea on
`views/admin/gateways.php` (its GatewayController already lists gateways — add
configured/driver columns + test-checkout link if missing).

### 1.2 Per-product deliverables

**`eskoofy-laravel-app`**

- `app/Services/Payment/TestGatewayAdapter.php` implementing `GatewayAdapterInterface`
  (`initialize` returns `['redirect_url' => route('payments.sandbox', …), 'transaction_id' => …]`,
  `handleCallback`/`verify` honouring the simulate contract, `supportsRefunds` = false).
- Match arm in `GatewayAdapterFactory` for `test_gateway`.
- Seeder row in `PaymentGatewaySeeder` (`is_active = 1`, no creds).
- Sandbox routes in `routes/payments.php` + a Blade view
  `resources/views/payments/sandbox.blade.php` (no-JS needed beyond the 3 forms).
- Diagnostics table on the existing gateways admin view.
- Tests: adapter unit test (success/failure/cancel/verify-idempotent) + feature test of the
  sandbox page round-trip.

**`eskoofy-php-app`** — same under `app/Gateways/TestGateway.php`, factory match, seeder row,
`routes/web.php` + view (byte-identical Blade to the app where shared), `php -l` clean.

**`eskoofy-wp-theme`** — register `test_gateway` in `esk_init_payment_gateways()`
(`inc/payment-gateways.php`), front route in `inc/front-dashboard.php`, sandbox view under
`views/admin/` or front partial, idempotent seeding, no external calls.

**`eskoofy-nodejs-app`** — `lib/payments/gateways.ts` test gateway branch + types,
sandbox route/page, seed row in the gateway seed, tests.

**`eskoofy-branding-website`** — `app/Gateways/TestGateway.php` implementing
`PaymentGatewayInterface`: `process()` stores `TEST-*` transaction id and returns
`redirect_url = /checkout/sandbox/{reference}`; sandbox view renders the 3 buttons;
POST handled by `CheckoutController` (or a small dedicated controller) applying the same
success/failure/cancel semantics — success flows into the **existing** paid path in
`PaymentStatusController::show()` (license issue / renewal). Add `test_gateway` to
`config/gateways.php` (`driver => 'test_gateway'`, `name => 'Test / Sandbox'`) and to
`GatewayFactory` dispatch + `gatewaysForCountry()` (available in **both** BD and INT —
it is a dev tool, gate it with an env `TEST_GATEWAY_ENABLED` default `true` in dev only
if a natural config seam exists; otherwise always available).
Unit tests in `tests/Unit/Gateways/`.

### 1.3 Acceptance criteria (Task 1)

1. Fresh checkout choosing `test_gateway` never leaves the local host and never calls an
   external API.
2. Simulate success ends with the payment `paid`, `transaction_id` `TEST-…`, and the normal
   post-payment artefact (receipt / license) produced.
3. Simulate failure and cancel leave consistent non-paid statuses + friendly flashes.
4. Re-verifying a paid test payment is a no-op.
5. Diagnostics table shows every gateway with configured/adapter state.
6. Product test suites pass with the new tests included.

---

## Task 2 — More Bangladeshi payment gateways / aggregators (all 5 codebases + sales copy)

Add **8** new BD gateways (the UddoktaPay-like aggregator/hosted class), served by the
**config-driven GenericHosted gateway** in the 4 products and by thin hosted classes in
the branding website.

| code | label | notes |
|---|---|---|
| `shurjopay` | ShurjoPay | checkout redirect (IPN/verify) |
| `portwallet` | PortWallet | hosted checkout + verify |
| `cellfin` | Cellfin | bank/wallet aggregator (EBL/DBBL style) |
| `purse` | Purse | mobile financial services aggregator |
| `cashby` | Cashby | merchant payout + checkout |
| `upay` | UPay | Trust Axiata Pay |
| `mycash` | MyCash | MFS aggregator |
| `payer` | Payer | merchant gateway |

### 2.1 Rules

- **Default `is_active = false`, `test_mode = true`, empty credentials** — the uddoktapay
  pattern: admin enables + fills creds when ready. Nothing changes for existing installs
  until an admin opts in (bd profile behaviour stays byte-for-byte today's unless configured).
- Seed rows carry draft sandbox/live URLs (mark them `draft` in comments — URLs must be
  verified against official docs before real use; do not invent extra fields, keep the
  existing gateway table shape):
  - `shurjopay`: sandbox `https://sandbox.shurjopay.io/` live `https://payment.shurjohub.com/`? —
    **verify**; `portwallet`: `https://sandbox.portwallet.com/cloud-payment/`;
    `cellfin`: `https://sandbox.cellfin.io` (draft); `purse`: `https://sandbox.purse.com.bd` (draft);
    `cashby`: `https://sandbox.cashby.com.bd` (draft); `upay`: `https://sandbox.upay.ltd` (draft);
    `mycash`: `https://sandbox.mycash.com.bd` (draft); `payer`: `https://sandbox.payer.com.bd` (draft).
- Each row's `extra_attributes` (or product equivalent) carries the GenericHosted contract:
  `checkout_method` (POST/GET), `checkout_url_template` with `{amount} {currency} {reference}
  {invoice} {callback} {cancel} {api_key}` placeholders as applicable, `verify_url`,
  `verify_success_path`/`verify_success_value` (default `status`/`COMPLETED`), `signature_header`
  where known (`X-Webhook-Signature` default).
- **Factory**: no bespoke class needed — verify `GenericHostedGatewayAdapter` (and php/theme/node
  equivalents) actually resolves unknown codes from the DB row/settings; if the factory throws
  for unknown codes, make it fall back to GenericHosted. Only add a bespoke class if a gateway
  genuinely cannot be expressed config-driven.
- Feature flags: optional keys under `config/eskoolfy.php` → `features.payments`
  (e.g. `shurjopay => false`) **only if the existing flag seam makes that trivial**;
  **do not** touch `restore.gateways` lists (existing restore behaviour must not change).
- Branding website: add the 8 to `config/gateways.php` as `driver => 'generic_hosted'`
  (new thin `GenericHostedGateway` class extending the hosted pattern used by
  `UddoktapayGateway` — reuse, don't fork) + gateways available for BD country via
  `gatewaysForCountry()` (check how uddoktapay is included there and follow it), env-driven
  keys like `SHURJOPAY_*`.
- **Sales copy parity**: branding `/features` (or pricing/payments section) + product pages
  mention the expanded BD payment coverage (bKash, Rocket, Nagad, UddoktaPay, ShurjoPay,
  PortWallet, Cellfin, Purse, Cashby, UPay, MyCash, Payer) in EN (and BN keys if the string
  catalog requires it — follow `I18nKeyParityTest`).
- Theme: register + seed idempotently; `.po`/`.pot` source strings for any new admin labels.

### 2.2 Acceptance criteria (Task 2)

1. All 8 gateways appear (inactive) in every product's gateway admin list and in the
   branding admin gateways screen.
2. Activating one with sandbox creds routes checkout through GenericHosted using
   `sandbox_url` (asserted by unit test on the request shape; no live network in tests).
3. Existing installs with no config changes behave identically (seeder idempotent, no
   status/flag changes to bkash/rocket/nagad/uddoktapay/int gateways).
4. `restore.gateways` lists unchanged.
5. Sales copy lists the new gateways consistently on the branding site.

---

## Task 3 — Branding website header + footer fully responsive

Files: `eskoofy-branding-website/views/layouts/main.php` (inline header, ~line 179) and
`eskoofy-branding-website/views/site/partials/footer.php`. Tailwind CDN + drawer already exist.

Fix the real breakages at **320–767 px** (verify by reasoning over the classes; keep
desktop ≥ md pixel-identical):

**Header**

- CTA cluster (dark toggle, login, Get demo, register, language switch) overflows next to
  the brand at xs — tighten `gap`s (`gap-1.5 sm:gap-3`), shrink paddings/labels below `sm`,
  hide "Get demo" below `sm` (it stays in the drawer), keep hamburger always visible below `md`.
- Brand: allow truncation (`min-w-0` + `truncate` on the name, `h-9 w-9 sm:h-11 sm:w-11` logo,
  `text-base sm:text-xl`).
- Language toggle: compact below `sm` (keep the same form).
- No horizontal scroll: `nav` gets `flex-wrap` guard or the cluster `shrink-0` where needed.

**Footer**

- Link columns currently stack 1-up below `lg`: make the main grid
  `sm:grid-cols-2 lg:grid-cols-12` so phones/tablets get 2 columns (brand + contact columns
  still span correctly on `lg`).
- Tighten xs paddings (`px-4 pt-10 sm:pt-14`), keep the legal row `sm:grid-cols-2 lg:grid-cols-4`.
- Long emails/addresses already `break-all` — confirm no overflow.
- Bottom bar already `flex-col md:flex-row` — add `text-center sm:text-left` where needed.

Acceptance: no horizontal overflow at 320/375/768 widths; ≥ `md` rendering unchanged apart
from intentional spacing polish; drawer still works; dark mode untouched.

---

## Task 4 — Remove the variant link/filter from the branding admin dashboard

Scope: `eskoofy-branding-website/views/admin/dashboard.php` + `Admin\DashboardController`.

- Remove the **Variant** `<select>` from the filters form (the one beside Product).
- Remove `variant` persistence from the `$rangeLink` closure (range presets must keep the
  **product** filter only).
- Remove the variant chip from the "Clear filters" status line; keep product.
- Remove now-unused view vars fed only by that select (`'variants' => VariantResolver::all()`
  if nothing else on the page uses it) and stop reading `variant` from the dashboard query
  (`filtersFromRequest` may keep `variant` support for services/tests — the **UI + link
  plumbing** goes). Do not break `LicenseService`/`RevenueService` signatures.
- **Keep** the public 4×2 product×variant matrix and `?variant=` on product pages — this task
  touches only the admin dashboard filter.
- Update any dashboard test asserting the variant filter (replace with product-filter assertions).

---

## Task 5 — Docs, feature tracking, consistency

1. **Feature tracking (REQUIRED)**:
   - `docs/feature-tracking/build-product-matrix.py` — under Finance add/refresh rows:
     `Payment gateways — test/sandbox gateway (zero-cost diagnostics)` and
     `Payment gateways — BD extended set (ShurjoPay/PortWallet/Cellfin/Purse/Cashby/UPay/MyCash/Payer)`
     with per-product `S("Yes")`/`S("Yes","Not tested")` matching what actually landed;
     regenerate `products-feature-matrix.xlsx`.
   - `docs/feature-tracking/build-branding-matrix.py` — add: `Test/sandbox gateway driver`,
     `BD hosted gateway set (8 new)`, `Admin dashboard variant filter removal`,
     `Header/footer responsive redesign`; regenerate `branding-website-feature-matrix.xlsx`.
   - `workplan-implementation-plan.md` — task block with ✅ + evidence if that file tracks
     comparable tasks.
2. **Docs**: short section in each product's payments docs / `docs/design/` payment doc (if one
   exists) covering the test gateway flow + how to enable a new BD gateway; branding README
   env table gains `*_TEST_MODE`/sandbox URL keys for the new gateways.
3. **Parity**: `eskoofy-php-app` views stay byte-identical to the app's where shared;
   theme strings go into the `.po`/`.pot` source.
4. AGENTS command/feature tables only if a natural place exists (do not churn docs).

---

## Verification (all must pass before "DONE")

| Gate | Command |
|---|---|
| App suite | `cd eskoofy-laravel-app && composer test` |
| App style | `cd eskoofy-laravel-app && ./vendor/bin/pint --test` |
| Raw-PHP suite | `cd eskoofy-php-app && composer test` |
| Raw-PHP view parity | `diff -r eskoofy-laravel-app/resources/views eskoofy-php-app/resources/views` (only pre-existing diffs) |
| Theme syntax | `php -l` on every touched `inc/` + `views/` file |
| Theme lint | `cd eskoofy-wp-theme && composer run lint` (non-gating) |
| Node tests | `cd eskoofy-nodejs-app && npm test` |
| Node types | `cd eskoofy-nodejs-app && npm run typecheck` |
| Node lint | `cd eskoofy-nodejs-app && npm run lint` |
| Website suite | `cd eskoofy-branding-website && composer test` |
| Trackers | both XLSX load via openpyxl with the new rows |

## Out of scope

- No live gateway calls in CI/tests; no real credentials anywhere.
- No changes to `restore.gateways` or existing gateway defaults/flags.
- No rework of the public product×variant matrix or `VariantResolver`.
- No commits unless the user explicitly asks.

---

## Propagation

| Product | Apply? | Areas |
|---------|--------|-------|
| eskoofy-laravel-app | ✅ | adapter, factory, seeder, routes, view, admin diagnostics, tests |
| eskoofy-php-app | ✅ | gateways, factory, seeder, routes, views (parity), tests |
| eskoofy-wp-theme | ✅ | payment-gateways inc, front route, views, seeding, strings |
| eskoofy-nodejs-app | ✅ | lib/payments, routes, seed, tests |
| eskoofy-branding-website | ✅ | gateways, config, checkout/status flow, admin gateways, header/footer, admin dashboard, sales copy, tests |

Confirmation: [x] granted (user request: "all 4 products and the branding website").
