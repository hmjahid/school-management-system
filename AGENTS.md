# AGENTS.md — Eskoofy Monorepo

Eskoofy is a **3-product** monorepo. Each product lives in its own folder with its own
`AGENTS.md`. The `eskoofy-website/` folder is **NOT a product** — it is the **branding
website + license server** that markets and sells the products (see `docs/NODEJS-VARIANT.md`
for the "product vs website" terminology and a proposed 4th Node product).

## Layout

| Folder | Product | Stack | Status |
|--------|---------|-------|--------|
| `eskoofy-app/` | School management software (current) | Laravel 12 | Active — most work happens here |
| `eskoofy-php/` | Raw PHP rewrite (no framework) | Raw PHP | **Deferred** — see `WORKPLAN.md` Phase 6 (gate 0.2); completed port on hold |
| `eskoofy-theme/` | WordPress theme | WP theme plugin-hybrid (`.po` l10n) | Complete — active sync with app |
| `eskoofy-website/` | Marketing/branding site + license server | Raw PHP | Complete — int-only |
| `build/` | BD/INT export build-box + feature-propagation gate | Shell/Laravel | Profiles + `export.sh` + `propagate/propagate-feature.sh` |
| `WORKPLAN.md` | Multi-product plan (phases, gates, milestones) | — | Source of truth for structure |
| `workplan-implementation-plan.md` | Per-task implementation tracker | — | Update statuses as work lands |
| `docs/` | Monorepo-level design/review/runbook docs | Markdown | Lives at repo root (owner decision) |

## Feature consistency across products (IMPORTANT)

Eskoofy ships the **same feature set in all 3 products** (app / raw-PHP / WP theme), plus
sales copy in `eskoofy-website`. Default scope for any feature change = **ALL products**.
Unless a task explicitly scopes to one product:

- Apply the change to `eskoofy-app` **and** `eskoofy-php` **and** `eskoofy-theme` **and**
  the marketing copy in `eskoofy-website`.
- **Confirm before implementing** (see `docs/FEATURE-PROPAGATION.md` + the runner
  `build/propagate/propagate-feature.sh`).
- php view parity = copy `resources/views/**` byte-identical; php route parity =
  `routes/api.php` equals app `route:list`.
- Theme parity: mirrored `views/admin/*.php` + `inc/front-dashboard.php` route +
  `inc/admin-shell.php` (title/icon/sidebar group) + `inc/database.php` for table changes.

## Variants

- `bd` = current Bangladeshi version (Bengali+English, ministry links, bKash/Rocket/Nagad).
- `int` = international, English-only (PayPal/Stripe/Paddle, no BD home-page links).
- Variants are **build-time profiles of one codebase** — never separate forks.
- Golden rule: every BD/INT difference is config/data (`config/eskoolfy.php` + `build/profiles/*`), never hardcoded `if (bd)` branching.

## Rules for agents

- Most coding happens in `eskoofy-app/` — read `eskoofy-app/AGENTS.md` first for app conventions.
- The Laravel suite runs from inside `eskoofy-app/`: `cd eskoofy-app && composer test` (Pint: `./vendor/bin/pint --test`).
- Keep the `bd` profile byte-for-byte today's behavior unless a task explicitly changes it.