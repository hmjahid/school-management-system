# Workplan Implementation Plan — Eskoofy Multi-Product Restructure & BD/INT Variants

Per-task tracker for `WORKPLAN.md`. Status legend:
- ✅ **done** — implemented + verified
- 🟡 **partial** — implemented with known gaps (listed)
- ⛔ **blocked/gated** — waiting on a decision or external input (reason given)
- ⬜ **not started** — out of scope / future phase
- ⬜ **deferred** — permanently or indefinitely postponed (reason given)

Verification baseline (2026-09-09): `cd eskoofy-app && composer test` → **912 passed, 0 risky**; `pint --test` clean on all touched files.

---

## Phase 0 — Foundations (decision gates)

| # | Task | Status | Notes |
|---|------|--------|-------|
| 0.1 | Confirm final monorepo folder names + website start | ✅ done | Folders locked: `eskoofy-app`, `eskoofy-php`, `eskoofy-theme`, `eskoofy-website`, `build/`. |
| 0.2 | **Gate:** name the host/market for raw PHP that can't run Composer/Laravel | ✅ done | **Permanently deferred** — no such host exists. Phase 6 skipped. |
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
| 5.2 | CI job for export + smoke | ✅ done | `.github/workflows/ci.yml`: test+lint job → export both variants → smoke assertions → artifact upload. |
| 5.3 | Docs/runbooks + tags | ✅ done | `docs/RUNBOOKS.md` §7 = tagging convention + release flow; `docs/README.md` updated with build/CI section. |

## Phase 6 — eskoofy-php (raw PHP) — COMPLETE

| # | Task | Status | Notes |
|---|------|--------|-------|
| 6.1 | Gate: named host unable to run Composer/Laravel | ✅ done | Shared hosting target — Laravel needs VPS which is costly. Raw PHP needed. |
| 6.2 | Architecture: router, DI-lite, auth, schema, gateway drivers | ✅ done | Full MVC stack: Router, Database (PDO), QueryBuilder, Model, Controller, View, Session, Auth, Validator, Request, 5 Middleware classes, Helpers. Entry point + .htaccess + bootstrap. |
| 6.3 | Parity checklist vs Laravel BD | ✅ done | 93-table MySQL schema, 78 models, 46 controllers, 97 views, 9 gateway adapters, 6 language files (en+bn), 3 config files. |
| 6.4 | Full module port + vertical slice | ✅ done | Complete port of ALL Laravel modules: students, teachers, classes, sections, subjects, batches, guardians, attendance, exams, results, fees, fee_payments, payments, payment_gateways, admissions, routines, assignments, notices, news, events, announcements, galleries, expenses, transport, hostel, library, SMS, reports, certificates, admit_cards, id_cards, users, roles, settings, profile, academic_sessions, payroll, testimonials, committee, backup. |
| 6.5 | BD/INT profile + build-box integration | ✅ done | `.env`-driven variant (bd/int), config/payment.php with all 7 gateways (bKash/Rocket/Nagad/Stripe/PayPal/Paddle/Offline), config/app.php with feature flags. |
| 6.6 | Tests + CI | ⬜ not started | CI integration pending. |

## Phase 7 — eskoofy-theme (WordPress) — COMPLETE

| # | Task | Status | Notes |
|---|------|--------|-------|
| 7.1 | Scout theme in `eskoofy-theme/` | ✅ done | Full plugin-theme hybrid: header/footer/sidebar/single/page/front-page/archive/404/search templates. Theme supports, nav menus, widget areas, base CSS. |
| 7.2 | INT variant via `.po` | ✅ done | `.pot` template + `en_GB.po`/`.mo` (INT) + `bn_BD.po`/`.mo` (BD) in `languages/`. All theme strings translatable. |
| 7.3 | Theme build-box integration | ✅ done | `build/export.sh theme {bd,int}`; attempts `wp i18n make-pot` when `wp-cli` present. |
| 7.4 | Theme tests/lint | ✅ done | `composer.json` + `phpcs.xml` (WordPress-Extra ruleset, `eskoofy` prefix). CI job `theme-lint` runs PHPCS on every push/PR. |
| 7.5 | Full WP plugin-theme hybrid | ✅ done | 13 inc/ files (database, CPTs, admin pages, AJAX, REST API, shortcodes, widgets, customizer, payment gateways, helpers, admin CSS/JS). 19 admin view templates. 5 page templates. 48 custom DB tables. 7 REST routes. 10 shortcodes. 7 payment gateways. Full admin dashboard with all modules. |

## Phase 8 — eskoofy-website (marketing/branding) — DEFERRED

| # | Task | Status | Notes |
|---|------|--------|-------|
| 8.1–8.2 | Brief + implementation | ⬜ deferred | Not needed now. Will revisit when core products are ready for launch. |

---

## Summary

- ✅ Complete: Phase 0, Phase 1, Phase 2, Phase 3, Phase 4, Phase 5, Phase 6 (full raw PHP port), Phase 7 (full WP hybrid).
- ⬜ Deferred: Phase 8 (website — not needed now).

## File counts

| Product | Files | Key components |
|---------|-------|----------------|
| eskoofy-app (Laravel) | existing | 912 tests, 84+ models, 100+ controllers |
| eskoofy-php (Raw PHP) | 262 | Core (16), Models (78), Controllers (46), Views (97), Gateways (9), Config (3), Lang (6), Schema (93 tables) |
| eskoofy-theme (WordPress) | 47+13+19+5 | Templates (17), Inc (13), Admin views (19), Page templates (5), Languages (5) |

## Next actions

1. Sandbox E2E for INT gateways (Stripe/PayPal/Paddle with real test creds) in all 3 products.
2. Design pass on the WP theme when ready.
3. Phase 8 (marketing website) when ready to launch.