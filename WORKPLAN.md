# Workplan — Eskoofy Multi-Product Restructure & BD/INT Variants

Status: draft · Owner: dev · Repo: `school-management-system` (becoming `eskoofy` monorepo)

## Goal

Turn this single Laravel repo into a 3-product monorepo:

| Product | Folder | Stack | Variants |
|---|---|---|---|
| Management software (current code) | `eskoofy-app/` | Laravel 12 | `bd` (current), `int` (English-only) |
| Raw PHP version | `eskoofy-php/` | Raw PHP (no framework) | `bd`, `int` — **gated** (Phase 5) |
| WordPress theme | `eskoofy-theme/` | WP theme | `bd`, `int` (`.po` translations) |
| Marketing/branding site | `eskoofy-website/` | TBD | single (future, non-blocking) |

**Variant definition**
- `bd` = current Bangladeshi version. Bengali + English, ministry/gov homepage links, bKash/Rocket/Nagad payments.
- `int` = international version. English only. PayPal/Stripe/Paddle (+ more int-standard gateways as landed). No BD home-page links.
- Variants are **build-time profiles of one codebase**, NOT separate forks (except raw PHP, which is a different stack by definition).

**Golden rule (from decision):** every BD/INT difference = data/config, never scattered hardcoded `if (bd)` branching. A variant is "pick profile, build, ship."

---

## Phase 0 — Foundations (decision gates)

| # | Task | Owner | Depends | Done when |
|---|------|-------|---------|-----------|
| 0.1 | Confirm final monorepo folder names + whether `eskoofy-website` starts now or later | PM | — | Names locked in this doc |
| 0.2 | **Gate:** define the concrete target host/market for raw PHP that cannot run Composer/Laravel | PM/BD | — | One named host/profile documented; without it, Phase 5 stays blocked |
| 0.3 | Lock INT scope: list of int-standard gateways, home-page blocks, bulk-mail/eschool features omitted or added | PM | — | Signed INT feature list in this doc |
| 0.4 | Verify current repo can be moved without breaking deploys (no production path assumptions) | Dev | — | Checklist in `docs/RUNBOOKS.md` updated for new path |

## Phase 1 — Restructure: move Laravel app into `eskoofy-app/`

| # | Task | Owner | Depends | Done when |
|---|------|-------|---------|-----------|
| 1.1 | Create top-level folders: `eskoofy-app/ eskoofy-php/ eskoofy-theme/ eskoofy-website/ build/` | Dev | 0.1 | Folders exist |
| 1.2 | Move all current app files (incl. `.env.example`, `composer.json`, `docs/`, `public/`) into `eskoofy-app/` using `git mv` | Dev | 1.1 | History preserved; nothing left at root except product folders, `build/`, `WORKPLAN.md`, `.git/`; `.gitignore` intact |
| 1.3 | Add `eskoofy-php/README.md`, `eskoofy-theme/README.md`, `eskoofy-website/README.md` placeholders with variant/stack notes | Dev | 1.1 | READMEs committed |
| 1.4 | Repoint any absolute paths in docs/scripts/CI to `eskoofy-app/...` | Dev | 1.2 | Full suite runs from new path |
| 1.5 | Update `AGENTS.md` (root) to describe monorepo layout + per-folder conventions | Dev | 1.2 | AGENTS.md accurate |

## Phase 2 — BD/INT profile system (Laravel, config-over-code)

| # | Task | Owner | Depends | Done when |
|---|------|-------|---------|-----------|
| 2.1 | Add `config/eskoolfy.php`: `variant`, `enabled_gateways`, `homepage_blocks`, feature flags (`int_payments`, `ministry_links`, `bilingual`) | Dev | 0.3 | Config read by app, default `bd` = current behaviour |
| 2.2 | Gate home-page gov/ministry links block behind `homepage_blocks.ministry_links` + `site_ui` lang keys | Dev | 2.1 | Removing block = flag off; no code delete |
| 2.3 | Verify all BD/homepage text lives in `lang/` (`en` + `bn`), purge hardcoded strings in views across the site | Dev | 2.2 | `grep` for inline text = 0 for int-affected pages |
| 2.4 | Payment registry: ∫strip `payment_gateways`-driven selection to adapter contract so INT can register new drivers with zero BD change | Dev | 2.1 | Adapter contract documented; BD behaviour unchanged |
| 2.5 | Define profile files `build/profiles/bd/{config,lang,branding}` and `build/profiles/int/{...}` capturing every flag above | Dev | 2.2–2.4 | `bd` profile reproduces current build byte-for-byte |

## Phase 3 — INT payment gateways (PayPal / Stripe / Paddle)

| # | Task | Owner | Depends | Done when |
|---|------|-------|---------|-----------|
| 3.1 | Define int-gateway contract (create/verify/refund webhooks) aligned with existing adapters | Dev | 2.4 | Contract doc |
| 3.2 | Implement `StripePaymentGateway` (payments intents + crypto webhook + refund) | Dev | 3.1 | Adapter passes unit tests with HTTP fake |
| 3.3 | Implement `PaypalPaymentGateway` (order/capture + webhook + refund) | Dev | 3.1 | Tests green |
| 3.4 | Implement `PaddlePaymentGateway` (checkout/alerts/subscription, incl. recurring + refunds) | Dev | 3.1 | Tests green |
| 3.5 | Wire adpters into `payment_gateways` runtime table + admin config UI, backend by flag `enabled_gateways.stripe/paypal/paddle` | Dev | 3.2–3.4, 2.1 | Enabling flag in INT profile activates gateway end-to-end; BD untouced |
| 3.6 | Web payment page + invoice copy for INT currency/locale formatting | Dev | 3.5 | INT checkout shows USD/gateway badge |
| 3.7 | Tests: gateway selection per variant, idempotent refunds for each new gateway | Dev | 3.5 | `composer test` green incl. new suites |

## Phase 4 — INT content & branding export

| # | Task | Owner | Depends | Done when |
|---|------|-------|---------|-----------|
| 4.1 | Strip `bn` lang pack + Bengali resources in `int` export build; keep `en` only | Dev | 2.3, 3.6 | INT artifact has no `lang/bn` |
| 4.2 | INT home page block set (hero, features, pricing style, global links) via `homepage_blocks` | Dev | 2.2 | INT homepage renders without gov links |
| 4.3 | Branding profile: name/logo/colors/contact per variant (config, not code) | Dev | 2.1 | `bd` vs `int` artifacts differ in branding only via config |

## Phase 5 — Build-box (export bd/int artifacts)

| # | Task | Owner | Depends | Done when |
|---|------|-------|---------|-----------|
| 5.1 | `build/export.sh` (or artisan command): clone/fetch repo, apply profile, strip locale, write `.env`, run `composer install` + `migrate`, zip to `build/dist/` | Dev | 2.5, 4.1, 4.3 | `build/dist/eskoofy-app-bd.zip` + `eskoofy-app-int.zip` produced idempotently |
| 5.2 | CI job: on tag, run export + smoke test both artifacts (install → migrate → seed demo → core page 200) | Dev | 5.1 | CI green for both variants |
| 5.3 | Document runbooks: `docs/BACKUP-RESTORE.md`, `docs/PRODUCTION-CHECKLIST.md`, `docs/RUNBOOKS.md` under new paths; versioned tags `bd-vNN` / `int-vNN` | Dev | 5.1 | Ops docs match real artifact layout |

## Phase 6 — eskoofy-php (raw PHP) — GATED

| # | Task | Owner | Depends | Done when |
|---|------|-------|---------|-----------|
| 6.1 | **Continuation gate:** only start if 0.2 produced a real host/market; else keep `eskoofy-php/` as README only | PM | 0.2 | Gate decision recorded |
| 6.2 | Architecture: router, DI-lite, auth + roles/permissions, schema port from Laravel migrations, queue-less fallbacks, PDF, gateway drivers | Dev | 6.1 | ARCHITECTURE.md in `eskoofy-php/` |
| 6.3 | Parity checklist vs Laravel BD (admissions, fees, exams/results, SMS, reports) module-by-module | Dev | 6.2 | Checklist tracked; per-module DoD = same tests as Laravel module |
| 6.4 | Port small module (e.g. notices) end-to-end as vertical slice to validate stack | Dev | 6.2, 6.3 | Slice deployed on target host |
| 6.5 | Full module port + bd/int profile + build-box integration | Dev | 6.4 | `build/` exports `eskoofy-php-{bd,int}` |
| 6.6 | Ongoing mass: this is a **second permanent codebase** — verify budget/headcount before commit | PM | 0.2 | Sign-off |

## Phase 7 — eskoofy-theme (WordPress)

| # | Task | Owner | Depends | Done when |
|---|------|-------|---------|-----------|
| 7.1 | Scaffold theme in `eskoofy-theme/` (current BD design) with `load_theme_textdomain` + `.pot/.po` (en/bn) | Dev | 1.3 | Theme repo runs as WP theme; strings internationalized |
| 7.2 | INT variant: stylesheet/branding profile + English `.po`; bd/int switch stays in WP (`WPLANG` / child profile) | Dev | 7.1 | Switchable without code change |
| 7.3 | Theme build-box integration (zip `eskoofy-theme-{bd,int}`) | Dev | 7.2, 5.1 | Artifacts from `build/dist/` |
| 7.4 | Theme tests: lint + smoke on WP test env | Dev | 7.3 | Green in CI |

## Phase 8 — eskoofy-website (marketing/branding) — future

| # | Task | Owner | Depends | Done when |
|---|------|-------|---------|-----------|
| 8.1 | Clarify purpose: brand/theme/app landing, purchase/checkout for both variants, docs site | PM | 0.1 | Brief |
| 8.2 | Implement in `eskoofy-website/` (stack TBD) | Dev | 8.1 | Deployed & linked to payments |
| — | Website is non-blocking; all phases 1–7 can finish without it | | | |

---

## Workflow & principles

1. **One codebase per product.** Variants are profiles; only raw PHP (different stack) is a separate codebase.
2. **All differences live in config/data.** If a feature can't be expressed as a profile flag, flag it in review before coding.
3. **Keep `bd` = today's behaviour.** Every change must leave the `bd` profile functionally identical until an INT feature explicitly needs it.
4. **Tests gate everything.** New gateways + profile system + export artifacts all covered in `composer test`; exports smoke-tested in CI.
5. Repo root becomes the monorepo; `AGENTS.md` and `docs/` move into `eskoofy-app/` with this file staying at root.

## Milestones

| Milestone | Phases | Exit criteria |
|---|---|---|
| M1 — Restructure | 0, 1 | Monorepo layout committed; full suite green from `eskoofy-app/` |
| M2 — Profile system | 2 | `bd` profile == current behaviour; INT flags ready |
| M3 — INT payments | 3, 4 | Stripe/PayPal/Paddle work in INT profile, BD untouched |
| M4 — Build-box | 5 | CI exports + smokes `eskoofy-app-{bd,int}` |
| M5 — Raw PHP (gated) | 6 | Gate decision + (if open) vertical slice live |
| M6 — Theme variants | 7 | `eskoofy-theme-{bd,int}` artifacts from build-box |

## Open questions (for PM)

- [ ] 0.2: target host/market that cannot run Composer/Laravel — needed to unblock Phase 6.
- [ ] 0.3: final INT feature list & gateway order (PayPal/Stripe/Paddle first?)
- [ ] 0.1: start `eskoofy-website` now or later?
- [ ] Naming: keep repo `school-management-system` or rename to `eskoofy` on GitHub?
- [ ] Pool/effort: raw-PHP rewrite budget decision (6.6) made explicitly, not by default.