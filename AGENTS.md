# AGENTS.md — Eskoofy Monorepo

Eskoofy is a **4-product** monorepo (app / raw-PHP / WP theme / Node.js variant). Each product
lives in its own folder with its own `AGENTS.md`. The `eskoofy-branding-website/` folder is
**NOT a product** — it is the **branding website + license server** that markets and sells the
products (see `docs/design/NODEJS-VARIANT.md` for the "product vs website" terminology; the
Node variant is `eskoofy-nodejs-app/`, a clone of the app).

## Layout

| Folder | Product | Stack | Status |
|--------|---------|-------|--------|
| `eskoofy-laravel-app/` | School management software (current) | Laravel 12 | Active — most work happens here |
| `eskoofy-php-app/` | Raw PHP rewrite (no framework) | Raw PHP | **Deferred** — see `WORKPLAN.md` Phase 6 (gate 0.2); completed port on hold |
| `eskoofy-wp-theme/` | WordPress theme | WP theme plugin-hybrid (`.po` l10n) | Complete — active sync with app |
| `eskoofy-nodejs-app/` | Node.js clone of the app (schema + routes + sidebar + i18n + CRUD + public site) | Next.js App Router + Prisma + Tailwind | Clone functional (verified end to end); pixel-level Blade parity + integrations pending — see `eskoofy-nodejs-app/docs/PORTING-STATUS.md` |
| `eskoofy-branding-website/` | Marketing/branding site + license server | Raw PHP | Complete — int-only |
| `build/` | BD/INT export build-box + feature-propagation gate | Shell/Laravel | Profiles + `export.sh` + `propagate/propagate-feature.sh` |
| `WORKPLAN.md` | Multi-product plan (phases, gates, milestones) | — | Source of truth for structure |
| `workplan-implementation-plan.md` | Per-task implementation tracker | — | Update statuses as work lands |
| `docs/` | Monorepo-level design/review/runbook docs | Markdown | Lives at repo root (owner decision) |

## Feature consistency across products (IMPORTANT)

Eskoofy ships the **same feature set in all products** (app / raw-PHP / WP theme / Node variant),
plus sales copy in `eskoofy-branding-website`. Default scope for any feature change = **ALL products**.
Unless a task explicitly scopes to one product:

- Apply the change to `eskoofy-laravel-app` **and** `eskoofy-php-app` **and** `eskoofy-wp-theme` **and**
  `eskoofy-nodejs-app` (the ported surface — see its `docs/PORTING-STATUS.md`) **and**
  the marketing copy in `eskoofy-branding-website`.
- **Confirm before implementing** (see `docs/design/FEATURE-PROPAGATION.md` + the runner
  `build/propagate/propagate-feature.sh`).
- php view parity = copy `resources/views/**` byte-identical; php route parity =
  `routes/api.php` equals app `route:list`.
- Theme parity: mirrored `views/admin/*.php` + `inc/front-dashboard.php` route +
  `inc/admin-shell.php` (title/icon/sidebar group) + `inc/database.php` for table changes.
- Node parity: `lib/nav.ts` is the app-sidebar contract (`npm run route:parity`), schema lives in
  `prisma/schema.prisma` (same table names), API uses the same `{success,message,data[,meta]}` envelope.

## Variants

- `bd` = current Bangladeshi version (Bengali+English, ministry links, bKash/Rocket/Nagad).
- `int` = international, English-only (PayPal/Stripe/Paddle, no BD home-page links).
- Variants are **build-time profiles of one codebase** — never separate forks.
- Golden rule: every BD/INT difference is config/data (`config/eskoolfy.php` + `build/profiles/*`), never hardcoded `if (bd)` branching.

## Rules for agents

- Most coding happens in `eskoofy-laravel-app/` — read `eskoofy-laravel-app/AGENTS.md` first for app conventions.
- The Laravel suite runs from inside `eskoofy-laravel-app/`: `cd eskoofy-laravel-app && composer test` (Pint: `./vendor/bin/pint --test`).
- Keep the `bd` profile byte-for-byte today's behavior unless a task explicitly changes it.