# Eskoofy Build Box

Produces the deployable **`bd` / `int`** artifacts for every product from the monorepo.
Variants are build-time profiles of one codebase — never separate forks (monorepo golden rule).

## Usage

```bash
./build/export.sh <product> <variant>
```

| Product | Variants | Artifact |
|---|---|---|
| `app` | `bd`, `int` | `build/dist/eskoofy-laravel-app-<variant>.zip` |
| `php` | `bd`, `int` | `build/dist/eskoofy-php-app-<variant>.zip` |
| `node` | `bd`, `int` | `build/dist/eskoofy-nodejs-app-<variant>.zip` |
| `theme` | `bd`, `int` | `build/dist/eskoofy-wp-theme-<variant>.zip` |
| `website` | `int` (only) | `build/dist/eskoofy-branding-website-int.zip` |

Raw staged trees are left in `build/artifacts/` for inspection; the zips go to `build/dist/`.

## How it works

1. `build/profiles/profiles.php` defines the per-variant profile (label, locales,
   gateway config, branding).
2. `export.sh` rsyncs the product folder, excluding dev/local files (`.git`, `node_modules`,
   `vendor`, `.env`, backups, tests where appropriate).
3. Applies the variant: copies the profile's config/data over the default, strips `bn`
   locale for `int` (for `node` the `lang/bn.ts` bundle is dropped), drops BD-only
   gateway/branding files for `int`, etc. The Node artifact ships **source** — the
   deployer runs `npm ci && npm run build` on the server (see `eskoofy-nodejs-app/docs/DEPLOYMENT-GUIDE.md`).
4. Runs the product's smoke build (e.g. `npm run build` for the app, `wp i18n make-pot`
   for the theme when `wp-cli` is available).
5. Zips the staged tree into `build/dist/`.

The same logic runs in CI (`.github/workflows/ci.yml`) on every push/PR and smoke-tests the
artifacts before uploading.

## Golden rule

Every BD/INT difference must live in `config/eskoolfy.php` + `build/profiles/*` — never
hardcoded `if (bd)` branching in product code. If you add a new BD/INT difference, model it
as data/config here first.