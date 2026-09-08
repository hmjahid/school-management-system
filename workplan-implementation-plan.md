# Workplan Implementation Plan — Eskoofy Multi-Product Restructure & BD/INT Variants

Per-task tracker for `WORKPLAN.md`. Status legend:
- ✅ **done** — implemented + verified
- 🟡 **partial** — implemented with known gaps (listed)
- ⛔ **blocked/gated** — waiting on a decision or external input (reason given)
- ⬜ **not started** — out of scope / future phase

Verification baseline (2026-09-08): `cd eskoofy-app && composer test` → **912 passed, 0 risky**; `pint --test` clean on all touched files.

---

## Phase 0 — Foundations (decision gates)

| # | Task | Status | Notes |
|---|------|--------|-------|
| 0.1 | Confirm final monorepo folder names + website start | ✅ done | Folders locked: `eskoofy-app`, `eskoofy-php`, `eskoofy-theme`, `eskoofy-website`, `build/`. |
| 0.2 | **Gate:** name the host/market for raw PHP that can't run Composer/Laravel | ⛔ blocked | Required to unblock Phase 6. Until named, `eskoofy-php/` stays a README stub. |
| 0.3 | Lock INT scope (gateways, home blocks, omit/add list) | ✅ done | INT = English-only, PayPal/Stripe/Paddle, no BD ministry links, USD/UTC (see `build/profiles/profiles.php`). |
| 0.4 | Verify move won't break deploys | ✅ done | Suite + both artifact boots green from new paths; `docs/RUNBOOKS.md` path notes updated in root AGENTS.md. |

## Phase 1 — Restructure: move Laravel app into eskoofy-app/

| # | Task | Status | Notes |
|---|------|--------|-------|
| 1.1 | Create top-level product folders + `build/` | ✅ done | `eskoofy-app/ eskoofy-php/ eskoofy-theme/ eskoofy-website/ build/{profiles/{bd,int},dist,artifacts}` |
| 1.2 | Move all app files into `eskoofy-app/` | ✅ done | Files moved; git detects renames (staged). `.env`, `vendor`, `node_modules` stay ignored inside `eskoofy-app/`. |
| 1.3 | Product placeholder READMEs | ✅ done | `eskoofy-php/README.md` (gated), `eskoofy-theme/` (WP skeleton: style.css, functions.php, index.php, README), `eskoofy-website/README.md`. |
| 1.4 | Repoint absolute paths in docs/scripts | ✅ done | No hardcoded root paths found in app config; suite passes from new path. |
| 1.5 | Update `AGENTS.md` | ✅ done | Root = monorepo overview; `eskoofy-app/AGENTS.md` = full app guide. |

## Phase 2 — BD/INT profile system (Laravel, config-over-code)

| # | Task | Status | Notes |
|---|------|--------|-------|
| 2.1 | `config/eskoolfy.php` profile config | ✅ done | `variant`, `features.homepage.{ministry_links,ministry_badge}`, `features.payments.*`, `branding`, `int` defaults. All env-drivable. |
| 2.2 | Gate ministry/gov links block | ✅ done | `resources/views/partials/site/footer.blade.php` column gated by `config('eskoolfy.features.homepage.ministry_links')`. |
| 2.3 | Purge hardcoded strings in int-affected views | ✅ done | `grep` for `gov.bd`/Bengali in views → only the gated footer block. Nav/home strings already via `site_ui()` lang files. |
| 2.4 | Payment registry → adapter contract | ✅ done | `GatewayAdapterFactory` central; INT drivers are new adapters, BD untouched. |
| 2.5 | Profile files `build/profiles/{bd,int}` | ✅ done | `build/profiles/profiles.php` = single source for env/locales/gateways per variant. |

## Phase 3 — INT payment gateways (PayPal / Stripe / Paddle)

| # | Task | Status | Notes |
|---|------|--------|-------|
| 3.1 | Define int-gateway contract | ✅ done | Same `GatewayAdapterInterface` as BD adapters (initialize/processCallback/verifyPayment/verifyWebhookSignature/refund). |
| 3.2 | Stripe adapter | ✅ done | `StripeGatewayAdapter`: PaymentIntent init, webhook `payment_intent.succeeded`, verify, HMAC-signed webhook, API refund. |
| 3.3 | PayPal adapter | ✅ done | `PaypalGatewayAdapter`: OAuth token, Checkout order + approval URL, capture on verify, capture-completed webhook, API refund. |
| 3.4 | Paddle adapter | ✅ done | `PaddleGatewayAdapter`: Classic checkout URL, `payment_succeeded`/`refund_issued` alerts, PHP-signature webhook, refund explicitly dashboard-offline. |
| 3.5 | Wire adapters into registry/config | ✅ done | Registered in `GatewayAdapterFactory`; `config/payment.php` + `.env.example` entries; `PaymentGateway::is_configured` supports `paddle`; `PaymentService::supportsRefunds` includes stripe/paypal. |
| 3.6 | Web payment page + INT currency copy | ✅ done | Decision: reuse existing checkout flow. Stripe switched to hosted Checkout (returns `redirect_url`); PayPal approval URL + Paddle checkout URL already fit. No bespoke INT view needed. |
| 3.7 | Tests | ✅ done | `IntlGatewayAdapterTest`: 19 tests / 31 assertions — interface, init, webhook-complete, refund, signature valid/invalid/missing per gateway. Factory + full suite green. |

## Phase 4 — INT content & branding export

| # | Task | Status | Notes |
|---|------|--------|-------|
| 4.1 | Strip `bn` lang in `int` export | ✅ done | `build/export.sh app int` removes `lang/bn`; smoke asserts zip contains no `lang/bn` files. |
| 4.2 | INT homepage block set | ✅ done | Ministry column off via env flag `ESKOOFY_MINISTRY_LINKS=false`; verified boot: `ministry_links=off`, `variant=int`. |
| 4.3 | Branding profile per variant | ✅ done | Profile env: name, locale, currency, timezone per variant (`.env` applied at export). |

## Phase 5 — Build-box

| # | Task | Status | Notes |
|---|------|--------|-------|
| 5.1 | Export script `build/export.sh` | ✅ done | `./build/export.sh app bd`, `app int`, `theme {bd,int}`. rsync app → apply profile `.env` → strip lang → zip `build/dist/eskoofy-app-{variant}.zip`. Idempotent; `build/artifacts` + `build/dist` git-ignored. |
| 5.2 | CI job for export + smoke | 🟡 partial | Scripted exports verified locally (both artifact boots green). No CI config yet (repo has no CI runner). |
| 5.3 | Docs/runbooks + tags | 🟡 partial | Root `AGENTS.md` updated with build-box. Docs stay at repo root (`docs/`) per owner decision; release/tag convention not yet documented. |

## Phase 6 — eskoofy-php (raw PHP) — GATED

| # | Task | Status | Notes |
|---|------|--------|-------|
| 6.1 | Gate: named host unable to run Composer/Laravel | ⛔ blocked | No host named yet. `eskoofy-php/README.md` documents the gate. |
| 6.2–6.6 | Architecture → vertical slice → full port | ⬜ not started | Deadlocked on 6.1. |

## Phase 7 — eskoofy-theme (WordPress)

| # | Task | Status | Notes |
|---|------|--------|-------|
| 7.1 | Scout theme in `eskoofy-theme/` | 🟡 partial | Skeleton in place: `style.css` (text domain), `functions.php` (l10n setup), `index.php`. No real templates/B shapes yet (separate design effort). |
| 7.2 | INT variant via `.po` | 🟡 partial | Translation infra ready; `bn_BD`/`en_GB` `.po` files not yet generated (need design content). |
| 7.3 | Theme build-box integration | ✅ done | `build/export.sh theme {bd,int}`; attempts `wp i18n make-pot` when `wp-cli` present. |
| 7.4 | Theme tests/lint | 🟡 partial | No automated test harness yet; export zip smoke only. |

## Phase 8 — eskoofy-website (marketing/branding) — future

| # | Task | Status | Notes |
|---|------|--------|-------|
| 8.1–8.2 | Brief + implementation | ⬜ not started | Non-blocking; all other phases completed without it. |

---

## Summary

- ✅ Complete: Phase 0 (except gate), Phase 1, Phase 2, Phase 3 (admin extra_attributes + hosted Stripe Checkout now done), Phase 4, Phase 5 (except CI + docs relink), Phase 7 build-box.
- 🟡 Partial: 5.2 (CI), 5.3 (docs/tags), 7.1/7.2/7.4 (theme design + tests).
- ⛔ Blocked: Phase 6 (raw PHP) — waiting on the host/market gate.
- ⬜ Not started: Phase 8 (website), Phase 6.2+.

## Next actions (shortest path to full completion)

1. PM: name the raw-PHP target host or permanently defer Phase 6.
2. Stand up CI (GitHub Actions): tag → export both variants → boot-smoke each artifact (kills 5.2).
3. Sandbox E2E for INT gateways (Stripe/PayPal/Paddle with real test creds); move PayPal webhook verification to the verify-webhook-signature API for production.
4. Update `eskoofy-app/docs/RUNBOOKS.md` + `PRODUCTION-CHECKLIST.md` paths and tagging convention (kills 5.3).
5. Design the WP theme templates + generate `.po` files (7.1/7.2) when design work starts.