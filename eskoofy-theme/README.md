# Eskoofy Theme (WordPress)

**Status: SKELETON.** See `WORKPLAN.md` Phase 7.

Single theme repo. BD/INT variants are build-time profiles — translations via
`.pot`/`.po` and branding via a per-variant stylesheet/profile, never forks.

Layout:

- `style.css` — theme header (text domain `eskoofy`, domain path `/languages`)
- `functions.php` — theme setup + `load_theme_textdomain()`
- `index.php` — placeholder template
- `languages/` — `eskoofy.pot` + `bn_BD` / `en_GB` translations (generated on build)

Next steps (when started): template hierarchy, header/footer, BD visual design, `wp-cli`
build script into `build/profiles/{bd,int}` artifacts.