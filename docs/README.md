# Documentation

This is the **index and map** for every document in the repository. The tree is
organized into topic folders so the root stays clean:

```
docs/
├── README.md        ← you are here (index / map)
├── guides/          How-to guides for developers
├── operations/      Deployment, runbooks, backups, credentials, payments ops
├── design/          Product proposals, research, strategy, cross-product rules
├── features/        Improvement proposals + implementation trackers
├── quality/         QA audits, remediation plans, senior-PM review artifacts
├── planning/        Historical plans, audits and reviews (read-only record)
├── prompts/         Feature-implementation prompt files (used by the propagation gate)
└── notes/           Scratch notes / journal (tracked)
```

## Getting started

- **[`guides/DEVELOPMENT.md`](guides/DEVELOPMENT.md)** — **start here.** How to run the
  Laravel app, the raw-PHP port, the WordPress theme (Docker harness) and the branding
  website on a development server: prerequisites, ports, quick-start commands per
  component, demo credentials, verification and troubleshooting.
- **[`operations/DEMO-CREDENTIALS.md`](operations/DEMO-CREDENTIALS.md)** — seeded demo
  accounts for all products (+ lowercase alias `demo-credentials.md`).
- **[`design/FEATURE-PROPAGATION.md`](design/FEATURE-PROPAGATION.md)** — the
  cross-product feature-consistency rule + runner (`build/propagate/propagate-feature.sh`).

## Guides (`guides/`)

- `DEVELOPMENT.md` — run all products + the website in the development server (ports,
  quick starts, demo accounts, verification, troubleshooting)

## Operations (`operations/`)

- `ADMISSIONS.md` — admission application flow
- `API-PAYMENTS.md` — payment gateway API integration
- `BACKUP-RESTORE.md` — database backup & restore
- `DASHBOARD_TROUBLESHOOTING.md` — common dashboard issues
- `DEMO-CREDENTIALS.md` (+ `demo-credentials.md` alias) — seeded demo account credentials
- `PAYMENT-DEPLOYMENT.md` — deploying payment configuration
- `PERFORMANCE-BASELINE.md` — load-testing baseline
- `PRODUCTION-CHECKLIST.md` — go-live checklist
- `RUNBOOKS.md` — runbooks for common operations

## Design (`design/`)

- `FEATURE-PROPAGATION.md` — cross-product feature-consistency rule + runner
  (`build/propagate/propagate-feature.sh`) + per-product propagation maps
- `PAYMENT-MODEL.md` — suggested freemium + tiered subscription/pricing model for all
  Eskoofy products (community/school/district/enterprise tiers, gateways, trial, roadmap)
- `COMPETITIVE-ANALYSIS.md` — research on international school-management software sites
  (homepage/pricing/social-proof patterns + recommendations for the branding site)
- `BRANDING-SITE-IMPROVEMENTS.md` — actionable implementation suggestions for
  `eskoofy-branding-website/` (pricing toggles, role-based feature showcase, trust/comparison pages,
  conversion CTAs) mapped to the existing pages
- `NODEJS-VARIANT.md` — feasibility + stack recommendation for a proposed 4th product
  (`eskoofy-node`, NestJS + Next.js) and the "website is branding, not a product" framing
- `SMART-SCHOOL-IMPLEMENTATION.md` — implementation plan for the "smart" layer
  (Tier 1 automation engines, Tier 2 analytics/prediction, Tier 3 opt-in AI assistant)

## Features (`features/`)

- `FEATURE-IMPROVEMENTS.md` — senior-engineer feature & engineering improvement proposals
- `FEATURE-IMPROVEMENTS-IMPLEMENTATION.md` — tracker for the above
- `UIUX-IMPROVEMENTS.md` — senior-designer UI/UX improvement proposals (public site + dashboard)
- `UIUX-GUIDELINES.md` — design tokens, component cheat sheet, "new era" checklist,
  do-not-use list
- `UIUX-IMPLEMENTATION-PLAN.md` — tracks the migration of `UIUX-IMPROVEMENTS.md` to the
  component system
- `IMPLEMENTATION-PLAN.md` — current in-flight implementation plan (review fixes / SMTP)
- `MARKETPLACE-ELIGIBILITY.md` — marketplace (ThemeForest/CodeCanyon-style) eligibility checklist

## Quality (`quality/`)

- `QA-PARITY-REPORT.md` — senior-QA audit: eskoofy-php-app & eskoofy-wp-theme parity vs the
  Laravel app (views byte-parity, routes, schema, services, theme page/sidebar/depth gaps)
- `QA-UI-FRONTEND-REPORT.md` — senior-QA UI/frontend audit: theme vs app dashboard
  (sidebar item order + accordion sub-items, shell UI/UX/styles/mobile) + public frontend
  (nav/footer/per-page) + module & site functionality matrix
- `QA-REMEDIATION-PLAN.md` — implementation plan + live status tracker for the two QA
  reports; phased, gates large feature work behind `design/FEATURE-PROPAGATION.md`
- `REVIEW-PROMPT.md` — methodology used for the senior-PM review
- `SENIOR-PM-REVIEW-REPORT.md` — findings from the senior-PM review

## Planning (`planning/`)

Historical plans, audits and reviews (read-only record — do not edit):

- `codebase-audit.md`
- `implementation-plan-24-08-2026.md`
- `improvement-suggestion-24-08-2026.md`
- `plan-build-incomplete-features.md`
- `product-design-review-24-08-2026.md`
- `server-requirements.md`
- `system-architecture-review.md`
- `system-workflow.md`
- `unified-implementation-plan-24-08-2026.md`
- `wordpress-theme-conversion.md`

## Feature prompts (`prompts/`)

- `features-impl-prompt-2.md`
- `features-impl-prompt-4.md` … `features-impl-prompt-16.md`
- `product-polish-and-docs-prompt.md` — rebrand, sidebar parity, documentation and
  feature-propagation implementation session
- `master/` — legacy master prompt files (controller-vs-schema audit, INT
  messaging/branding site, website PWA/geo/language/posts)

## Notes (`notes/`)

- `self-notes.md`
- `self-notes-products.md`
- `Journal.md`
- `name-suggetions.md`

## Build & CI

- `.github/workflows/ci.yml` — GitHub Actions: runs tests + lint, exports both BD/INT
  variants, smoke-tests artifacts, uploads zips.
- `build/export.sh` — local export script (same logic as CI).
- `build/profiles/profiles.php` — single source of truth for BD/INT variant differences.
- `build/propagate/propagate-feature.sh` — cross-product feature propagation gate
  (plan → confirmation → hand-off). See `design/FEATURE-PROPAGATION.md`.
- Tagging convention: see `operations/RUNBOOKS.md` §7.

## Agent instructions

- Root `AGENTS.md` — conventions and commands for working in this repo (kept at root,
  as required).
- Root `README.md` — project overview + this docs map (kept at root).
- Root `WORKPLAN.md` + `workplan-implementation-plan.md` — phase plan and per-task tracker.