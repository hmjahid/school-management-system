# Cross-Product Feature Consistency System (Eskoofy)

> Golden rule: Eskoofy ships the **same feature set in all 3 products** (Laravel app,
> raw-PHP port, WordPress theme). A feature change in one product must land in all of
> them **unless the task explicitly scopes to one product**.

This document defines the workflow, the runner script, and the default-permission model so
the whole repo stays feature-equivalent.

---

## The rule

1. Default scope = **ALL products**:
   - `eskoofy-laravel-app` — feature owner / source of truth
   - `eskoofy-php-app` — byte-identical port candidates (views must stay byte-identical)
   - `eskoofy-wp-theme` — WP port (templates + `esk-*` plugins)
   - `eskoofy-nodejs-app` — Node.js single-architecture variant (phased port of the app;
     `lib/nav.ts` is the app↔node sidebar parity contract)
   - `eskoofy-branding-website` — sales copy + pricing/license changes (not code parity, but
     feature mention parity)
2. To scope a change to ONE product, the task must state it explicitly
   (e.g. "only in `eskoofy-laravel-app`").
3. **Confirmation gate:** Before implementing any multi-product feature change, the
   implementation run must present the per-product file plan and ask for confirmation
   (see the runner script). No change is applied silently across products.

---

## Feature-defined change types

| Change type | What must be propagated | Examples |
|---|---|---|
| **Code feature** | App controller/service/migration + mirrored php port + theme template/shortcode + node port (route/schema/nav) + website feature copy | New report, new SMS template placeholder |
| **UI/View change** | Blade view in app + the byte-identical Blade view in `eskoofy-php-app/resources/views/` + theme template + node page + `lib/nav.ts` + (if visible) website copy | Sidebar redesign, dashboard stat card |
| **Config/data** | `config/eskoolfy.php` + `build/profiles/profiles.php` + theme options/`esk_*` tables + website pricing data | New payment gateway flag, new language |
| **Branding** | Every product's visible text/badge (see rebrand task) | Product name, logo, URLs |

---

## Runner script

`build/propagate/propagate-feature.sh <feature-name> [--plan] [--dry-run] [--yes]`:

1. Reads `docs/prompts/features-impl-prompt-NN.md` or the raw prompt (stdin if no arg).
2. Parses/derives the per-product file plan (app → php → theme → website) using the
   propagation maps below.
3. **Prints the plan and pauses** for confirmation:
   - `--plan`  → print the plan only, exit.
   - `--dry-run` → print the plan and what would run, make NO edits.
   - `--yes`   → skip the pause (explicit opt-in; a human already confirmed).
   - default  → interactive `[y/N]` prompt; `y`/`Y` + Enter proceeds, anything else aborts.
4. On confirmation, delegates to the default agent/harness to apply the same change across
   each product's mapped paths.
5. Always runs the product verification gates afterward.

> The script NEVER edits files itself — it is an orchestration + confirmation gate. The
> confirmation may be the human-in-the-loop step of an AI coding session: the AI applies
> the change, the script guards the sequence.

---

## Propagation maps (path equivalences)

### Laravel app → raw PHP port (`eskoofy-php-app`)

rule of thumb: **every file under `eskoofy-laravel-app/` that is *code* has a counterpart in
`eskoofy-php-app/app/`**, and **every file under `eskoofy-laravel-app/resources/views/` has a
byte-identical copy under `eskoofy-php-app/resources/views/`**.

| `eskoofy-laravel-app` | `eskoofy-php-app` |
|---|---|
| `app/Http/Controllers/**` | `app/Controllers/**` |
| `app/Models/**` | `app/Models/**` |
| `app/Services/**` | `app/Services/**` (or `app/Helpers/**` where ported) |
| `routes/*.php` | `routes/*.php` (route parity is asserted, see below) |
| `resources/views/**` | `resources/views/**` **byte-identical** |
| `config/**` | `config/**` (profile-merged at build) |
| `database/migrations/**` | `database/schema/**` (roll-forward only; php port keeps its own schema) |
| `public/sw.js`, `public/offline.html`, `public/manifest.json` | same paths |

> **PHI**: the raw PHP port's `/api/v1` route map must always equal
> `eskoofy-laravel-app`'s `route:list` (see `eskoofy-php-app/AGENTS.md`). After an app route change,
> regenerate with the known recipe (route:list → `routes/api.php`).

### Laravel app → WordPress theme (`eskoofy-wp-theme`)

| `eskoofy-laravel-app` | `eskoofy-wp-theme` |
|---|---|
| `resources/views/dashboard/**.{blade.php}` | `views/admin/{slug}.php` + route in `inc/front-dashboard.php` + title/icon/group in `inc/admin-shell.php` |
| `resources/views/partials/dashboard/sidebar.blade.php` | `inc/admin-shell.php` → `esk_admin_sidebar_sections()` + `esk_admin_shell_groups()` |
| `resources/views/site/*.blade.php` | `template-*.php` / `archive-*.php` / `single-*.php` / `front-page.php` |
| `app/Http/Controllers/**` | `inc/admin-ajax.php` + `inc/rest-api.php` handlers |
| `resources/js/app.js` | `assets/js/main.js` (vanilla port) |
| `config/eskoolfy.php` features | `inc/database.php` `esk_*` tables + `inc/plugin-loader.php` flags |

### Laravel app → Node.js variant (`eskoofy-nodejs-app`)

The Node variant is a **phased port** (see `docs/design/VARIANT-BLUEPRINT.md` +
`eskoofy-nodejs-app/docs/PORTING-STATUS.md`); propagate what is already ported.

| `eskoofy-laravel-app` | `eskoofy-nodejs-app` |
|---|---|
| `resources/views/partials/dashboard/sidebar.blade.php` | `lib/nav.ts` (the parity contract; enforced by `npm run route:parity`) |
| `routes/dashboard.php` (route + permission) | `app/(dashboard)/**` page + `lib/modules.ts` entry |
| `app/Http/Controllers/Api/**` | `app/api/v1/**/route.ts` (same `{success,message,data[,meta]}` envelope) |
| `app/Models/**` + migrations | `prisma/schema.prisma` (keep table names + documented gotchas) |
| `lang/{en,bn}/**` | `lang/en.ts` + `lang/bn.ts` (same keys) |
| `config/eskoolfy.php` + `build/profiles/*` | `config/eskoolfy.ts` (profiles as data) |

### App/theme → website (`eskoofy-branding-website`)

| Change | `eskoofy-branding-website` |
|---|---|
| New feature/module | `/products/{slug}` copy mentions it; `/features` list updated |
| Pricing / plans | `config` + `/pricing` + license API plans table |
| Branding | Nav/footer/footer copy |

---

## Confirmation flow (what a human approves)

When a task says "apply to all products unless I specify", the flow is:

1. **Plan** — list the concrete files per product (e.g. `eskoofy-laravel-app/…`, `eskoofy-php-app/…`).
2. **Present for confirmation** — show the list, ask "Apply to all products?".
3. **Await explicit yes** (interactive prompt / human reply).
4. **Apply** — only the confirmed products get changes.
5. **Verify** — run per-product test gates (app + php suites, theme `php -l` + lint,
   website smoke).

---

## Prompt-file convention

Every implementation prompt under `docs/prompts/features-impl-prompt-NN.md` gets a final
"Propagation" section:

```md
## Propagation

| Product | Apply? | Files |
|---------|--------|-------|
| eskoofy-laravel-app | ✅ | … |
| eskoofy-php-app | ✅ | … |
| eskoofy-wp-theme | ✅ | … |
| eskoofy-nodejs-app | ✅ | … |
| eskoofy-branding-website | ✅ | … |

Confirmation: [ ] required / [x] granted
```

Default = all products, confirmation required. ✍️

---

## Verification after propagation

| Gate | Command |
|---|---|
| App suite | `cd eskoofy-laravel-app && composer test` |
| Raw-PHP parity | `cd eskoofy-php-app && composer test` (Blade-engine parity) |
| Theme syntax | `cd eskoofy-wp-theme && php -l inc/…` on touched files |
| Theme lint | `cd eskoofy-wp-theme && composer run lint` |
| Route parity | php port `routes/api.php` vs app `route:list` (see php AGENTS.md) |
| Website smoke | `cd eskoofy-branding-website && composer test` |

---

## Anti-patterns (do NOT)

- Back-porting silently without the confirmation gate.
- Forking a variant-specific behaviour into product code (BD/INT is config, not code).
- Assuming a theme `esk-*` page exists because the app has a route — always check
  `inc/front-dashboard.php` + `views/admin/` first; missing pages are a parity gap to fill.
- Touching `eskoofy-laravel-app/archive/**` or the historical `docs/planning/*` (historical record).