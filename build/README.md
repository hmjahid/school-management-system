# Eskoofy Build Box

Produces the deployable **`bd` / `int`** artifacts for every product from the monorepo.
Variants are build-time profiles of one codebase — never separate forks (monorepo golden rule).

## Usage

```bash
./build/export.sh <product> <variant>
```

| Product | Variants | Artifact |
|---|---|---|
| `app` | `bd`, `int` | `build/dist/eskoofy-app-<variant>.zip` |
| `php` | `bd`, `int` | `build/dist/eskoofy-php-<variant>.zip` |
| `theme` | `bd`, `int` | `build/dist/eskoofy-theme-<variant>.zip` |
| `website` | `int` (only) | `build/dist/eskoofy-website-int.zip` |

Raw staged trees are left in `build/artifacts/` for inspection; the zips go to `build/dist/`.

## How it works

1. `build/profiles/profiles.php` defines the per-variant profile (label, locales,
   gateway config, branding).
2. `export.sh` rsyncs the product folder, excluding dev/local files (`.git`, `node_modules`,
   `vendor`, `.env`, backups, tests where appropriate).
3. Applies the variant: copies the profile's config/data over the default, strips `bn`
   locale for `int`, drops BD-only gateway/branding files for `int`, etc.
4. Runs the product's smoke build (e.g. `npm run build` for the app, `wp i18n make-pot`
   for the theme when `wp-cli` is available).
5. Zips the staged tree into `build/dist/`.

The same logic runs in CI (`.github/workflows/ci.yml`) on every push/PR and smoke-tests the
artifacts before uploading.

## Golden rule

Every BD/INT difference must live in `config/eskoolfy.php` + `build/profiles/*` — never
hardcoded `if (bd)` branching in product code. If you add a new BD/INT difference, model it
as data/config here first.