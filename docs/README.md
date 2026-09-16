# Documentation

The repo's documentation is split into operational guides and historical planning
records, organized into the folders below so the root stays clean.

## Map

| Folder | Purpose |
|---|---|
| `./` (this folder) | Operational guides: deployment, payments API, admission flow, backups, runbooks, production checklist, performance baseline, troubleshooting. Also holds the senior-PM review artifacts. |
| `planning/` | Historical plans, audits, and reviews (implementation plans, codebase/job/system architecture reviews, product-design review, system workflow, server requirements, WordPress conversion). |
| `prompts/` | Feature-implementation prompt files (`features-impl-prompt-*.md`) plus `master/` legacy master prompt files used to drive prior build sessions. |
| `notes/` | Scratch notes (tracked): `self-notes.md` (working log), `self-notes-products.md` (per-product log), `Journal.md`, and `name-suggetions.md` (branding ideas). |

## Operational guides (this folder)

- `ADMISSIONS.md` — admission application flow
- `API-PAYMENTS.md` — payment gateway API integration
- `BACKUP-RESTORE.md` — database backup & restore
- `DASHBOARD_TROUBLESHOOTING.md` — common dashboard issues
- `DEMO-CREDENTIALS.md` — seeded demo account credentials
- `FEATURE-IMPROVEMENTS.md` — senior-engineer feature & engineering improvement proposals
- `UIUX-IMPROVEMENTS.md` — senior-designer UI/UX improvement proposals (public site + dashboard)
- `UIUX-GUIDELINES.md` — design tokens, component cheat sheet, new-era checklist, do-not-use list
- `UIUX-IMPLEMENTATION-PLAN.md` — tracks the migration of `UIUX-IMPROVEMENTS.md` to the component system
- `PAYMENT-DEPLOYMENT.md` — deploying payment configuration
- `PERFORMANCE-BASELINE.md` — load-testing baseline
- `PRODUCTION-CHECKLIST.md` — go-live checklist
- `QA-PARITY-REPORT.md` — senior-QA audit: eskoofy-php & eskoofy-theme parity vs the
  Laravel app (views byte-parity, routes, schema, services, theme page/sidebar/depth gaps)
- `QA-UI-FRONTEND-REPORT.md` — senior-QA UI/frontend audit: theme vs app dashboard
  (sidebar item order + accordion sub-items, shell UI/UX/styles/mobile) + public frontend
  (nav/footer/per-page) + module & site functionality matrix
- `QA-REMEDIATION-PLAN.md` — implementation plan + live status tracker for the two QA
  reports (`QA-PARITY-REPORT.md` + `QA-UI-FRONTEND-REPORT.md`); phased, gates large
  feature work behind `FEATURE-PROPAGATION.md`
- `RUNBOOKS.md` — runbooks for common operations
- `IMPLEMENTATION-PLAN.md` — current in-flight implementation plan (review fixes / SMTP)
- `REVIEW-PROMPT.md` — methodology used for the senior-PM review
- `SENIOR-PM-REVIEW-REPORT.md` — findings from the senior-PM review
- `COMPETITIVE-ANALYSIS.md` — research on international school-management software sites
  (homepage/pricing/social-proof patterns + recommendations for the branding site)
- `BRANDING-SITE-IMPROVEMENTS.md` — actionable implementation suggestions for
  `eskoofy-website/` (2026 benchmark: pricing toggles, role-based feature showcase,
  trust/comparison pages, conversion CTAs) mapped to the existing pages
- `PAYMENT-MODEL.md` — suggested freemium + tiered subscription/pricing model for all
  Eskoofy products (community/school/district/enterprise tiers, gateways, trial, roadmap)
- `FEATURE-PROPAGATION.md` — cross-product feature-consistency rule + runner
  (`build/propagate/propagate-feature.sh`) + per-product propagation maps
- `NODEJS-VARIANT.md` — feasibility + stack recommendation for a proposed 4th product
  (`eskoofy-node`, NestJS + Next.js) and the "website is branding, not a product" framing
- `SMART-SCHOOL-IMPLEMENTATION.md` — implementation plan for the "smart" layer
  (Tier 1 automation engines, Tier 2 analytics/prediction, Tier 3 opt-in AI assistant);
  config-driven BD/INT, parity + confirmation gates per `FEATURE-PROPAGATION.md`

## Planning / reviews (`planning/`)

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
- `product-polish-and-docs-prompt.md` — rebrand, sidebar parity, documentation and feature-propagation implementation session
- `master/` — legacy master prompt files (controller-vs-schema audit, INT messaging/branding site, website PWA/geo/language/posts)

## Notes (`notes/`)

- `self-notes.md`
- `self-notes-products.md`
- `Journal.md`
- `name-suggetions.md`

## Build & CI

- `.github/workflows/ci.yml` — GitHub Actions: runs tests + lint, exports both BD/INT variants, smoke-tests artifacts, uploads zips.
- `build/export.sh` — local export script (same logic as CI).
- `build/profiles/profiles.php` — single source of truth for BD/INT variant differences.
- `build/propagate/propagate-feature.sh` — cross-product feature propagation gate (plan → confirmation → hand-off). See `FEATURE-PROPAGATION.md`.
- Tagging convention: see `docs/RUNBOOKS.md` §7.

## Agent instructions

- Root `AGENTS.md` — conventions and commands for working in this repo (kept at root, as required).
- Root `CLAUDE.md` — Claude Code agent configuration (kept at root).
- Root `README.md` — project overview (kept at root).
