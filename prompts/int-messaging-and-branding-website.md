# Eskoofy — INT Messaging Integration + Branding & Licensing Website

Master prompt file. Execute this file AND `workplan-implementation-plan.md` Phase 8.
Continue all tasks until execution completes and all verification passes.

> Golden rule (AGENTS.md): never hardcode BD/INT branching inside products. Every
> variant difference is config/data (`config/eskoolfy.php`, `build/profiles/profiles.php`).

---

## Part A — International messaging service integration (INT variant)

The `int` variant of the school management system must align with international
standards for messaging. Today only `bd`-oriented and local-driver support exists
(`SMS_DRIVER=log` default) and the raw PHP port only writes an SMS log row without
actually delivering a message.

### A1. eskoofy-app (Laravel 12)
- Add a real international SMS driver: **Vonage (V10/Nexmo)** alongside existing
  Twilio support. Implementation uses `Illuminate\Support\Facades\Http` against the
  Vonage Messages API (no new Composer dependency required).
- Register `vonage` (and alias `nexmo`) drivers in `app/Providers/SmsServiceProvider.php`
  and `config/sms.php`.
- Make driver selection variant-aware via config only:
  - `build/profiles/profiles.php`: `int` profile sets `SMS_DRIVER=twilio` (defaults stay
    unset otherwise); `bd` profile keeps today's behavior (`log` default).
  - `config/eskoolfy.php`: add `features.sms` flag block documented for INT.
- Add the new environment variables to `eskoofy-app/.env.example`.
- Unit tests for `VonageSmsService` (HTTP faked) covering send success, send failure,
  balance, and status.

### A2. eskoofy-php (raw PHP)
- New SMS service layer under `app/Services/Sms/`:
  - `SmsServiceInterface` (`send`, `getBalance`, `getStatus`),
  - `LogSmsService` (default, keeps today's behavior),
  - `TwilioSmsService` (REST via `file_get_contents`/streams, no Composer),
  - `VonageSmsService` (Messages API),
  - `SmsManager` factory driven by `config/sms.php` + env.
- `config/sms.php` with `default` driver and per-driver keys; `.env.example` additions.
- Wire `app/Controllers/Dashboard/SmsController.php` to actually deliver via `SmsManager`
  and record per-recipient status in `sms_logs`.
- Add `sms_status` column to `database/schema.sql` (DDL-only, no destructive migration).
- Unit tests for all drivers + manager.

### A3. eskoofy-theme (WordPress)
- New `inc/sms-gateway.php` helper that delivers SMS through Twilio **or** Vonage using
  `wp_remote_post` when the site is configured (options), and falls back to logging.
- Wire the existing admin SMS page submission to call the helper instead of only listing.
- Standard WordPress HTTP timeout/SSL handling; no external library.

### A4. Nothing else
- Do not touch `bd` behavior. `bd` keeps `SMS_DRIVER=log` today's default.

---

## Part B — eskoofy-website: enterprise branding & licensing management site (Phase 8)

Build `eskoofy-website/` as a **fully functional, self-contained** website (stack: raw
PHP + the reusable zero-dependency Core from `eskoofy-php/app/Core`) that markets and
sells the Eskoofy products, and operates as a **license server** with full customer and
payment management. Paddle will be added as a payment gateway later; the payment layer
must be provider-abstraction ready (`PaymentGatewayInterface`) with a working
"manual/offline" provider now.

### B1. Foundation
- Reuse `eskoofy-php` Core exactly (Router, Database, QueryBuilder, Model, Controller,
  View, Session, Auth, Validator, Request, bootstrap, Middleware, Helpers). No Composer
  at runtime. PDO/MySQL, PHP 8.2+.
- `index.php` front controller, `.htaccess` rewrite to `public/`, `.env.example`.

### B2. Data model (`database/schema.sql`, MySQL)
Tables with sensible FKs, indexes, timestamps, soft-delete where useful:
- `customers` — accounts (name, email, password hash, company, country, locale, token).
- `plans` — product, name, description, price USD, currency, period (monthly/yearly/one-time), max_activations, features (JSON), active.
- `licenses` — license_key (unique, checoded), customer_id, plan_id, status
  (active/expired/suspended/cancelled), starts_at, expires_at, max_activations, metadata.
- `license_activations` — license_id, domain/url, machine_id, ip, activated_at,
  deactivated_at, unique per (license_id, domain or machine_id).
- `payments` — customer_id, license_id, plan_id, gateway, transaction_id, amount,
  currency, status (pending/paid/failed/refunded), paid_at, raw (JSON).
- `subscriptions` — customer_id, license_id, plan_id, status, current_period_start/end,
  renews_at, gateway_subscription_id.
- `contact_messages`, `activity_logs` (audit trail).
- Seed: admin user, default plans (App monthly/yearly, Theme one-time) in USD.

### B3. License server (the heart)
`app/Services/LicenseManager.php`:
- Generate strong license keys (e.g. `XXXX-XXXX-XXXX-XXXX` from secure random).
- Issue a license on successful payment; auto-calculate expiry from plan period.
- Activate: record activation (domain/machine), enforce `max_activations`.
- Validate: check key format, status, expiry, active activation.
- Deactivate: revoke activation for domain/machine.
- Renew: extend expiry / create subscription renewal, record payment.
- Public (no auth) API under `routes/api.php`:
  - `POST /api/v1/licenses/activate`
  - `POST /api/v1/licenses/validate`
  - `POST /api/v1/licenses/deactivate`
  - `GET /api/v1/licenses/status`
  - JSON responses (`success`, `data`, `message`), API-key protected (customer key or
    license-key-based signed requests).

### B4. Marketing / site pages
`views/site/` using a shared layout: Home (hero, products: App + Theme, features,
testimonials/trust), Features, Pricing (plans grid), Products detail (App, Theme), About,
Contact (form → `contact_messages`). English-only, USD pricing, UTC dates — international
standard. Seed content in English.

### B5. Customer dashboard
- Register / login / logout (email + password).
- My dashboard: active licenses, activations count, expiry dates, renewal button.
- License detail: activations table, activate/deactivate, copy key.
- Payments: history with gateway, amount, status.
- Renewals: renew a license (creates payment record; manual gateway completes it).

### B6. Admin backend
Login-protected area:
- Dashboard stats (customers, licenses, revenue, activations, expiring soon).
- Customers CRUD.
- Plans CRUD.
- Licenses: list/search, issue manually, suspend, cancel, extend (view detail + activations).
- Payments: list, mark paid/failed/refund.
- Contact messages inbox.
- Audit log viewer.

### B7. Payment abstraction (Paddle later)
`app/Gateways/PaymentGatewayInterface` + `PaymentGatewayFactory` + working
`ManualGateway` (records payment, marks paid). Paddle gateway to be added later — the
factory must make that a config drop-in. Currency USD, amounts in dollars in UI.

### B8. Tests (PHPUnit, dev-only)
Cover: LicenseManager (issue/activate/validate/deactivate/renew/expiry),
api endpoint success/failure, customer auth, plan pricing, manual payment flow.
`phpunit.xml` + `tests/bootstrap.php`.

### B9. Build / CI
- `.github/workflows/ci.yml`: add `website-test` job (composer install dev, import schema,
  run phpunit). Add website artifact to the `export` job via `build/export.sh website bd|int`
  (website is INT-first; still profile-bounded using profiles.php).
- `build/export.sh`: add `website` product case producing `build/dist/eskoofy-website-{profile}.zip`
  (excludes tests, .env, public/assets source maps).

---

## Part C — Definition of done
1. `cd eskoofy-app && composer test` green (Pint too).
2. `cd eskoofy-php && composer test` green.
3. `cd eskoofy-website && composer test` green (new suite).
4. Theme lint (`composer run lint`) green.
5. `workplan-implementation-plan.md` Phase 8 and new INT-messaging rows marked done.
6. `bd` profile behavior unchanged (log SMS default, byte-for-byte-ish today's output).