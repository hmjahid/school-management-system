# Eskoofy Theme — Deployment Guide

> Product: `eskoofy-wp-theme` (WordPress plugin-theme hybrid) · Production deployment for the
> `bd` (Bangladesh) or `int` (international) variant. Install & activation live in
> [`SETUP-GUIDE.md`](./SETUP-GUIDE.md); sizing in [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md).

Ship the **variant artifact** `eskoofy-wp-theme-bd.zip` / `eskoofy-wp-theme-int.zip`
(`build/export.sh theme bd|int`) — translations (`.po`/`.mo`) and branding are already baked
in per variant.

---

## 1. Prerequisites

- A host meeting [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md) (WordPress 6.x, PHP 8.0+,
  MySQL/MariaDB).
- A working WordPress install (site + database created by the WP installer).
- TLS for the domain.

---

## 2. Install the theme

1. **Upload** the artifact contents into `/wp-content/themes/eskoofy/`
   (Appearance → Themes → Add New → Upload Theme, or copy via FTP/SFTP).
2. **Activate** the theme. Activation (`after_switch_theme`) automatically:
   - creates every `esk_*` table via `dbDelta` (`inc/database.php`),
   - encrypts any plaintext gateway secrets (AES-256-GCM `esk1:`),
   - creates the demo users,
   - ensures Home and Blog pages exist and flushes rewrite rules,
   - seeds demo content (idempotent, guarded by the `esk_demo_seeded` option).
3. Finish setup in **Settings → Eskoofy** (school name/tagline/contact, currency, hero CMS,
   branding, theme preset).

> No manual SQL import — the tables are created on activation.

## 3. Variant notes

- **BD**: Bengali (`bn_BD`) translations + BD branding profile;  bKash/Rocket/Nagad gateways.
- **INT**: English (`en_GB`) + int branding; Stripe/PayPal/Paddle.
- Variants are build-time — the artifact is already the profile you asked for. Do **not** mix
  `.po` files across variants at runtime on one site.

## 4. Web server

- WordPress pretty permalinks must work — enable `mod_rewrite` (Apache `.htaccess`) or the
  equivalent nginx `try_files`.
- Re-save **Settings → Permalinks** after first activation to flush rewrite rules
  (and whenever `/dashboard/` or `/sw.js` 404s).
- Document root is the normal WP root (the theme lives under `wp-content/themes/eskoofy`).

nginx example — standard WordPress:

```nginx
server {
    listen 443 ssl;
    server_name eskoofy.example.com;
    root /var/www/html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## 5. Go-live checklist

- Change every demo password (`admin@school.com`, `principal`, `teacher.*`, etc.) —
  see [`../../docs/operations/DEMO-CREDENTIALS.md`](../../docs/operations/DEMO-CREDENTIALS.md).
- Set the school branding/contact in Settings → Eskoofy.
- Configure payment gateways (System login → Dashboard → settings; stored as encrypted
  `esk1:` options) and the SMS gateway (`esk_sms_options`).
- Point `/login/`, `/dashboard/`, `sw.js` and `/manifest.json` smoke checks at 200.
- Enable HTTPS + force redirect; disable any proxy that strips the `X-Esk-Session` cookies
  your users rely on (keep cookies secure).

## 6. Security

- The shipped root `.htaccess` blocks `inc/` and `views/` from direct HTTP access **except**
  static assets (`.css/.js/...`) — keep that carve-out or the dashboard renders unstyled.
- `ESK_ENABLE_WPADMIN_MENU` defaults off; leave it off in production.
- Use encrypted gateway secrets (the theme migrates plaintext secrets to AES-256-GCM on
  activation — do not store API keys in plaintext options).

## 7. Backups

- WordPress-level: `wp-content/uploads/` + the DB (WP tables + `esk_*` tables).
- Standard tooling works (UpdraftPlus, WP-CLI `db export`, host snapshots).
- Restore = reinstall WP + restore DB + re-upload theme + re-activate (tables are recreated
  only if missing — restoring the DB carries the `esk_*` tables with it).

## 8. Update / rollback

**Update:**

1. Backup the DB + `wp-content/uploads`.
2. Replace `wp-content/themes/eskoofy/` with the new artifact.
3. Re-activate the theme (or run `php wp eval "..."` to trigger `after_switch_theme`) so new
   tables/options are applied; anything additive is idempotent.

**Rollback:** restore the previous theme folder; if schema changed, restore the DB backup.

## 9. Verification

```bash
curl -I https://eskoofy.example.com/            # 200 + security headers
curl -I https://eskoofy.example.com/dashboard/  # 200 (rewrites working)
curl -I https://eskoofy.example.com/sw.js       # 200 (PWA route)
```

Go-live checklist: [`../../docs/operations/PRODUCTION-CHECKLIST.md`](../../docs/operations/PRODUCTION-CHECKLIST.md).

## Related docs

- [`SETUP-GUIDE.md`](./SETUP-GUIDE.md) — install & activation
- [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md) — sizing the server
- [`USER-MANUAL.md`](./USER-MANUAL.md) — usage
- [`../../docker/theme-test/README.md`](../../docker/theme-test/README.md) — local test env
- [`../../build/README.md`](../../build/README.md) — the export/build box
- [`../../docs/operations/PRODUCTION-CHECKLIST.md`](../../docs/operations/PRODUCTION-CHECKLIST.md)