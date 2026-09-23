# Eskoofy Child Theme

A child theme for **Eskoofy** (same pattern as the Elementor child theme).
Its job is to give you a **safe place to put custom styles/scripts/templates so
that updating the parent Eskoofy theme never breaks or overwrites them.**

## Setup

1. Install this folder as a theme next to the parent: `wp-content/themes/eskoofy-child/`
   (the parent must stay installed as `wp-content/themes/eskoofy`).
2. In WP Admin → Appearance → Themes, activate **Eskoofy Child**.

> **If you renamed the parent folder** (e.g. installed it as `eskoofy-wp-theme`),
> change the `Template:` line in `style.css` to match that folder name — otherwise
> WP will report the parent as missing.

## How it works

- **Templates.** WP loads a filename first from this folder, then from the parent.
  To customise any page, copy the parent template here
  (`front-page.php`, `header.php`, `footer.php`, `template-*.php`, `single-*.php`,
  `archive-*.php`, `page.php`, `index.php`, `404.php`, `search.php`, `sidebar.php`,
  `searchform.php`) and edit the copy. Parent updates never touch it.
- **`functions.php`.** With a child theme active, WP runs *only* this file and skips
  the parent's. Because Eskoofy is a plugin-hybrid (all of `inc/` — admin shell, REST,
  DB tables, gateways, shortcodes — boots from the parent `functions.php`), line one
  re-requires it. **Do not remove that line.**
- **Styles/scripts.** Add overrides to the files below — all enqueued after the
  parent's bundles:

| File | Scope | When loaded |
|---|---|---|
| `custom.css` | Public site | after `eskoofy-style` on every page |
| `custom.js` | Public site | footer, after `esk-main` |
| `custom-admin.css` | WP admin + Eskoofy dashboard | after `eskoofy-admin-shell-style` on esk pages |

- **Dependency.** The child has no meaning without the parent — WP forbids
  deactivating or deleting the parent while this child is active. Parent theme
  updates touch only the parent folder, so everything in this folder survives.

## Layout

```
eskoofy-wp-theme-child/
├── style.css              theme header only (Template: eskoofy)
├── functions.php          re-requires parent bootstrap + enqueues custom files
├── custom.css             ← your front-end CSS overrides
├── custom.js              ← your front-end JS overrides
└── custom-admin.css       ← your admin CSS overrides
```