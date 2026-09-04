# Documentation

The repo's documentation is split into operational guides and historical planning
records, organized into the folders below so the root stays clean.

## Map

| Folder | Purpose |
|---|---|
| `./` (this folder) | Operational guides: deployment, payments API, admission flow, backups, runbooks, production checklist, performance baseline, troubleshooting. Also holds the senior-PM review artifacts. |
| `planning/` | Historical plans, audits, and reviews (implementation plans, codebase audit, product-design review, system workflow, server requirements, WordPress conversion). |
| `prompts/` | Feature-implementation prompt files (`features-impl-prompt-*.md`) used to drive prior build sessions. |
| `notes/` | Scratch notes: `self-notes.md` (working log) and `name-suggetions.md` (branding ideas). |

## Operational guides (this folder)

- `ADMISSIONS.md` — admission application flow
- `API-PAYMENTS.md` — payment gateway API integration
- `BACKUP-RESTORE.md` — database backup & restore
- `DASHBOARD_TROUBLESHOOTING.md` — common dashboard issues
- `DEMO-CREDENTIALS.md` — seeded demo account credentials
- `PAYMENT-DEPLOYMENT.md` — deploying payment configuration
- `PERFORMANCE-BASELINE.md` — load-testing baseline
- `PRODUCTION-CHECKLIST.md` — go-live checklist
- `RUNBOOKS.md` — runbooks for common operations
- `IMPLEMENTATION-PLAN.md` — current in-flight implementation plan (review fixes / SMTP)
- `REVIEW-PROMPT.md` — methodology used for the senior-PM review
- `SENIOR-PM-REVIEW-REPORT.md` — findings from the senior-PM review
- `system-architecture-review.md` — architecture review
- `plan-build-incomplete-features.md` — plan for incomplete features

## Planning / reviews (`planning/`)

- `codebase-audit.md`
- `implementation-plan-24-08-2026.md`
- `improvement-suggestion-24-08-2026.md`
- `product-design-review-24-08-2026.md`
- `server-requirements.md`
- `system-workflow.md`
- `unified-implementation-plan-24-08-2026.md`
- `wordpress-theme-conversion.md`

## Feature prompts (`prompts/`)

- `features-impl-prompt-2.md`
- `features-impl-prompt-4.md` … `features-impl-prompt-16.md`

## Notes (`notes/`)

- `self-notes.md`
- `name-suggetions.md`

## Agent instructions

- Root `AGENTS.md` — conventions and commands for working in this repo (kept at root, as required).
- Root `CLAUDE.md` — Claude Code agent configuration (kept at root).
- Root `README.md` — project overview (kept at root).
