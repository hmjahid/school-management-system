# Payment & Subscription Model — Eskoofy Products

> Purpose: Define the pricing strategy for Eskoofy products on the branding/website
> Date: September 2026 (revised — supersedes the earlier freemium draft)
> Decision: **Subscription-only. No freemium/community tier. No one-time/lifetime.**

---

## Model (confirmed)

- **Flat subscription per product** — one price per billing period, regardless of
  school size (no student/staff bands).
- **Billing periods:** Monthly and Yearly (yearly ≈ 2 months free).
- **Products:** `app` (Laravel), `theme` (WordPress), `php` (raw PHP) — all three
  subscription-only.
- **No free tier**, no community plan, no lifetime license. Every installation
  requires an active subscription.

## Pricing (canonical USD; BDT derived)

- Plans store a canonical **USD** price (`plans.currency = 'USD'`).
- **BD customers** are charged in **BDT**, derived at checkout via
  `GATEWAY_BDT_RATE` (USD→BDT). The rate is env-configured and adjustable.
- **International customers** are charged in USD.
- Pricing page shows the correct currency by customer country/locale.

### Suggested prices (tune before launch)

| Product | Monthly | Yearly |
|---------|--------:|-------:|
| `app`   | $12 | $120 |
| `theme` | $9  | $90  |
| `php`   | $9  | $90  |

## Payment methods by variant

### BD (Bangladesh) — local gateways (manual renewal)

| Method | Type | Renewal |
|--------|------|---------|
| bKash  | Mobile financial service | Manual (re-purchase each period) |
| Rocket | Mobile financial service | Manual |
| Nagad  | Mobile financial service | Manual |
| Bank transfer | Manual verification | Manual |

BD renewals are **manual** — the subscription is a term license; the school
re-purchases each period (bKash/Rocket/Nagad are one-time charge APIs).

### INT (International) — gateways with auto-renewal

| Method | Type | Renewal |
|--------|------|---------|
| Stripe | Card payment intents | Auto-renew via gateway subscription + webhook |
| PayPal | Orders | Auto-renew via gateway subscription + webhook |
| Paddle | Checkout/subscriptions | Auto-renew via gateway subscription + webhook |
| Bank transfer | Manual verification | Manual |

INT subscriptions auto-renew through the gateway; webhooks update
`subscriptions.status` and extend `licenses.expires_at`.

## License ↔ subscription model

- A **license** is the activation entitlement (`licenses` row, key-based,
  `license_activations`). It carries `expires_at` = current subscription period end.
- A **subscription** (`subscriptions`) links customer → license → plan and tracks
  `current_period_start/end`, `renews_at`, gateway id.
- On payment success: issue license (if new) → create/extend subscription →
  set `licenses.expires_at` to the period end.
- **License validation** (`/api/v1/licenses/*`) rejects a license whose
  subscription is `past_due`/`cancelled`/`expired` or whose `expires_at` passed.

## Subscription management

- Customer dashboard: current plan, renewal date, payment history, renew action.
- Admin: plan CRUD (variant/currency), subscription list + status override,
  payment log, MRR/ARR dashboard.
- Dunning: INT gateways handle retries; BD manual renewals send reminder copy.

## Free trial

- **None** (subscription-only). No trial tier by default; sales-led demo/onboarding
  via contact. Revisit only if a paid-trial (card-on-file) is ever added.

## Implementation roadmap

1. Data model: `plans` (monthly/yearly, USD canonical) + `payments.variant` +
   `GATEWAY_BDT_RATE` config.
2. Gateways: bKash/Rocket/Nagad (BD) + Stripe/PayPal/Paddle (INT) via `.env`,
   sandbox first; `GatewayFactory` routes by customer country/locale.
3. Checkout + subscription lifecycle + webhooks + license-expiry coupling.
4. Pricing/account/admin UI + website copy (en/bn) + tests.

## Key decisions (recorded)

1. **No freemium** — subscription-only (owner decision, overrides earlier draft).
2. **No student/staff bands** — flat per-product subscription.
3. **BD manual renewal; INT auto-renew** (owner decision).
4. **BDT derived from USD at rate** (`GATEWAY_BDT_RATE`), not a separate price list.
5. Credentials via `.env`, sandbox first; live keys added later.