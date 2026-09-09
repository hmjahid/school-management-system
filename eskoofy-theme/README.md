# Eskoofy Theme (WordPress)

**Status: Ready for design.** See `WORKPLAN.md` Phase 7.

Single theme repo. BD/INT variants are build-time profiles — translations via
`.pot`/`.po` and branding via a per-variant stylesheet/profile, never forks.

## Layout

- `style.css` — theme header + base styles (Tailwind-free, vanilla CSS)
- `functions.php` — theme setup, nav menus, widget areas, text domain
- `header.php` / `footer.php` — site chrome
- `sidebar.php` — widget area
- `single.php` / `page.php` / `front-page.php` — content templates
- `archive.php` / `search.php` / `404.php` — listing/error templates
- `searchform.php` — accessible search form
- `languages/` — `eskoofy.pot` + `bn_BD` / `en_GB` `.po`/`.mo` files

## Development

```bash
cd eskoofy-theme
composer install
composer run lint       # PHPCS with WordPress-Theme ruleset
composer run lint:fix   # auto-fix
```

## Build

```bash
./build/export.sh theme bd    # → build/dist/eskoofy-theme-bd.zip
./build/export.sh theme int   # → build/dist/eskoofy-theme-int.zip
```

The export script attempts `wp i18n make-pot` when `wp-cli` is available.
