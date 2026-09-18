# Marketplace Eligibility — Eskoofy (ThemeForest / CodeCanyon / similar)

Scope: applies to all three sellable products in this monorepo:

- `eskoofy-app/` — Laravel 12 school management software (sold as a CodeCanyon-style script)
- `eskoofy-php/` — raw-PHP port (same market as the app; no framework)
- `eskoofy-theme/` — WordPress theme/plugin hybrid

Status: **NOT YET SUBMITTABLE.** Work through the checklist below before any marketplace submission.
Auditors: reviewer, dev, PM.

---

## 1. Quality & QA pass

| # | Check | Where | Done |
|---|-------|-------|------|
| 1.1 | No placeholder text, demo data, or broken links shipped (esp. `school.placeholder_*` fallbacks) | `config/school.php`, public views | ☐ |
| 1.2 | Every route works from a fresh install (`install → migrate/import → seed → core pages 200`) | all 3 products | ☐ |
| 1.3 | Styled 404 / 500 / 419 / 429 error pages (no stack traces) | `resources/views/errors/*`, `eskoofy-php/views/errors/*`, theme | ☐ |
| 1.4 | No `dd()`, `var_dump`, `echo` debug, or error-log leakage in shipped code | grep across all products | ☐ |
| 1.5 | No development-only routes/controllers reachable in production | `routes/*.php`, middleware | ☐ |
| 1.6 | PHP 8.2+ clean, no deprecation notices in browser output | all 3 products | ☐ |

## 2. Licensing & copyright

| # | Check | Where | Done |
|---|-------|-------|------|
| 2.1 | Own or hold commercial licenses for all code, fonts, icons, images, logo | repo-wide audit | ☐ |
| 2.2 | Third-party libs' licenses are marketplace-compatible (MIT/BSD OK; GPL problematic for themes) | `composer.json`, `package.json`, theme assets | ☐ |
| 2.3 | No uncredited assets (Tailwind CDN is fine to use, but verify), no stock images without license | views/theme assets | ☐ |

## 3. Envato mandatory items (ThemeForest)

| # | Check | Where | Done |
|---|-------|-------|------|
| 3.1 | Clean, commented, structured code (PSR-12 for PHP) | all code | ☐ |
| 3.2 | Admin panel / settings for the buyer to configure (school name, branding, colors) | all 3 products | ☐ |
| 3.3 | Documentation — PDF/user guide + install & update instructions | `docs/` per product | ☐ |
| 3.4 | Published support policy (responses, update cadence) | marketplace listing | ☐ |
| 3.5 | No obfuscation, no hidden "call-home" license checks, no backdoors | repo-wide audit | ☐ |
| 3.6 | Responsive across browsers and device widths | all 3 products | ☐ |
| 3.7 | Translation-ready (`.pot/.po` for theme; `lang/` for app + php) | `eskoofy-theme/languages/`, `*/lang/*` | ☐ |

## 4. WordPress specifics (`eskoofy-theme/`)

| # | Check | Where | Done |
|---|-------|-------|------|
| 4.1 | Theme must be GPL-compatible (WP themes must be GPL to be distributed) | license headers, license file | ☐ |
| 4.2 | Prefix every function/class/option/table with `eskoofy_` (no generic names) | theme + plugin code | ☐ |
| 4.3 | Every string internationalized (`__()`, `esc_html__`) with textdomain `eskoofy` | all templates/views | ☐ |
| 4.4 | Output escaping everywhere (`esc_html`, `esc_attr`, `esc_url`, `wp_kses`) | all templates | ☐ |
| 4.5 | Sanitize all input on save (`sanitize_text_field`, `absint`, etc.) | all save handlers | ☐ |
| 4.6 | No `eval`, `base64_decode`, `create_function`, remote includes without disclosure | PHPCS + manual grep | ☐ |
| 4.7 | Pass **Theme Check** plugin with zero errors | theme check run | ☐ |
| 4.8 | Pass WordPress Coding Standards (PHPCS, WP-Extra ruleset — already configured) | `composer run lint` | ☐ |
| 4.9 | Decide: this is a **plugin + theme hybrid** (54 tables, CPTs, REST API, admin CRUD). Envato treats plugins separately; themes must be GPL while plugins don't face the same restriction. Decide theme vs plugin before submitting | product decision | ☐ |

## 5. PHP app / self-hosted script (CodeCanyon-style)

| # | Check | Where | Done |
|---|-------|-------|------|
| 5.1 | Smooth install: DB import + first-boot flow without errors | `eskoofy-php/database/schema.sql`, `eskoofy-app` migrations | ☐ |
| 5.2 | Install wizard or clear install documentation (host, PHP, MySQL requirements) | README per product | ☐ |
| 5.3 | System requirements checker on first run | both PHP products | ☐ |
| 5.4 | Documented update/upgrade path (no data loss) | `docs/operations/RUNBOOKS.md`, release notes | ☐ |
| 5.5 | Demo import or seed data clearly labeled as demo | seeds/schema.sql | ☐ |
| 5.6 | Security review: SQL injection, XSS, CSRF, auth bypass | audit all controllers | ☐ |
| 5.7 | Clean under PHP 8.2+ (no deprecations) | both PHP products | ☐ |
| 5.8 | Licensing/activation mechanism that is transparent and disclosed (if any) | `eskoofy-website` license server | ☐ |

## 6. Branding sanitization

| # | Check | Where | Done |
|---|-------|-------|------|
| 6.1 | Remove or clearly label "Eskoofy" demo branding, logos, and demo content | all 3 products | ☐ |
| 6.2 | Neutral default content so buyers don't inherit your placeholder/school data | seeds, `website_settings`, CMS pages | ☐ |
| 6.3 | Placeholder phone/email/address only as clearly-documented fallbacks, not in shipped demo | `config/school.php` | ☐ |

## 7. Structural blockers (decide before work)

- **7.1** `eskoofy-theme` is a full plugin-theme hybrid (54 DB tables, 7 CPTs, REST API, admin CRUD). Envato theme vs plugin classification must be decided up front — it changes the GPL requirement and review checklist.
- **7.2** The raw-PHP and Laravel apps are identical products on different stacks. Decide whether to sell one, both, or bundle; each needs its own listing, docs, and support policy.
- **7.3** The `eskoofy-website` license server is the activation/validation endpoint. If you ship any license enforcement, disclose it explicitly (Envato requires disclosure).

---

## Definition of done (before submission)

1. Every ☐ above is checked and verified by a reviewer.
2. A fresh install of each product boots clean and passes a route smoke test (see `workplan-implementation-plan.md` baselines).
3. Theme Check: 0 errors; PHPCS: clean; no forbidden functions (`eval`, `base64_decode`, etc.).
4. GPL/Theme vs plugin decision recorded (7.1); marketplace-required documentation and support policy exist.
5. License audit (2.1–2.3) documented with a list of third-party components and their licenses.