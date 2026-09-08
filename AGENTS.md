# AGENTS.md — Eskoofy Monorepo

Eskoofy is a 3-product monorepo. Each product lives in its own folder with its own `AGENTS.md`.

## Layout

| Folder | Product | Stack | Status |
|--------|---------|-------|--------|
| `eskoofy-app/` | School management software (current) | Laravel 12 | Active — most work happens here |
| `eskoofy-php/` | Raw PHP rewrite (no framework) | Raw PHP | **Gated** — see `WORKPLAN.md` Phase 6; not started |
| `eskoofy-theme/` | WordPress theme | WP theme (`.po` l10n) | Skeleton only |
| `eskoofy-website/` | Marketing/branding site | TBD | Not started (non-blocking) |
| `build/` | BD/INT export build-box | Shell/Laravel | Profiles + export script |
| `WORKPLAN.md` | Multi-product plan (phases, gates, milestones) | — | Source of truth for structure |
| `workplan-implementation-plan.md` | Per-task implementation tracker | — | Update statuses as work lands |
| `docs/` | Monorepo-level design/review/runbook docs | Markdown | Lives at repo root (owner decision) |

## Variants

- `bd` = current Bangladeshi version (Bengali+English, ministry links, bKash/Rocket/Nagad).
- `int` = international, English-only (PayPal/Stripe/Paddle, no BD home-page links).
- Variants are **build-time profiles of one codebase** — never separate forks.
- Golden rule: every BD/INT difference is config/data (`config/eskoolfy.php` + `build/profiles/*`), never hardcoded `if (bd)` branching.

## Rules for agents

- Most coding happens in `eskoofy-app/` — read `eskoofy-app/AGENTS.md` first for app conventions.
- The Laravel suite runs from inside `eskoofy-app/`: `cd eskoofy-app && composer test` (Pint: `./vendor/bin/pint --test`).
- Keep the `bd` profile byte-for-byte today's behavior unless a task explicitly changes it.