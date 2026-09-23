# Eskoofy Theme — Server Requirements

> Product: `eskoofy-wp-theme` (WordPress plugin-theme hybrid) · Use this page to size and
> prepare a server. Install steps live in [`SETUP-GUIDE.md`](./SETUP-GUIDE.md); go-live in
> [`DEPLOYMENT-GUIDE.md`](./DEPLOYMENT-GUIDE.md).

---

## 1. Software requirements

| Software | Minimum | Recommended | Notes |
|---|---|---|---|
| WordPress | 6.x | latest 6.x | The theme has no `Requires at least` pin, but 6.x is the tested surface |
| PHP | **8.0+** | **8.2+** | Theme uses `declare(strict_types=1)`, `str_starts_with()`, `?->`, union types |
| MySQL / MariaDB | 5.7+ / 10.x | MariaDB 11 / MySQL 8 | WordPress + custom `esk_*` tables |
| Required plugins | **None** | — | Self-contained plugin-theme hybrid |
| Web server | Apache / nginx + `mod_rewrite` | nginx + PHP-FPM | WordPress pretty permalinks |

### PHP extensions (WordPress baseline plus)

- `pdo_mysql` / `mysqli`, `mbstring`, `xml`, `json`, `curl`, `openssl`, `gd`
- `zip` (theme/plugin uploads, backups if exposed)
- `intl` (recommended by WordPress)

### External CDNs referenced by the theme's CSP

`cdn.tailwindcss.com`, `fonts.bunny.net`, `fonts.googleapis.com` (Inter + Noto Sans Bengali).
If the host blocks outbound CDN traffic, the public site may look unstyled — allow these
or self-host the assets.

---

## 2. Hardware sizing

WordPress is heavier than the raw-PHP product; the same file/DB backing store applies.

| Setup | Min CPU | Min RAM | Recommended |
|---|---|---|---|
| Development / staging | 1 vCPU | 1 GB | 2 GB |
| Small school (< 100 users) | 1 vCPU | 1.5 GB | 2 GB |
| Medium school (100–500 users) | 2 vCPU | 2 GB | 4 GB |
| Large / multi-branch (500+ users) | 4 vCPU | 4 GB | 8 GB |

| Component | Disk |
|---|---|
| WordPress + theme | ~150 MB |
| `wp-content/uploads` (CMS media) | 500 MB – 5 GB |
| Database (WP + `esk_*` tables) | 200 MB – 2 GB |
| OS + packages | 5–10 GB |
| **Total recommended** | **25–50 GB SSD** |

---

## 3. Shared hosting compatibility

The theme runs on ordinary WordPress shared hosting (cPanel/Plesk) — that is its target:

- PHP 8.0+ (8.2 recommended) with the extensions above
- MySQL/MariaDB (WP auto-creates the database schema; `dbDelta` creates the `esk_*` tables on
  activation — **no manual SQL import**)
- Rewrites enabled (pretty permalinks, `/dashboard/`, `/sw.js`)
- HTTPS (AutoSSL / Let's Encrypt)

No VPS or root access required for the basic setup.

---

## 4. Web server requirements

- Apache with `mod_rewrite` + `.htaccess` (WordPress standard) or nginx with the equivalent
  `try_files` block.
- `wp-content/uploads/` writable by the web user.
- HTTPS mandatory in production.

---

## 5. Minimum viable production stack (summary)

| Piece | Choice |
|---|---|
| Hosting | Managed WordPress or shared hosting with PHP 8.2 |
| Web | nginx + PHP-FPM or Apache |
| DB | MySQL 8 / MariaDB 11 |
| Cache | WP page cache / object cache plugin (optional) |

---

## Related docs

- [`SETUP-GUIDE.md`](./SETUP-GUIDE.md) — install & activation
- [`DEPLOYMENT-GUIDE.md`](./DEPLOYMENT-GUIDE.md) — production deployment
- [`USER-MANUAL.md`](./USER-MANUAL.md) — usage
- [`../../docs/planning/server-requirements.md`](../../docs/planning/server-requirements.md) — monorepo-level sizing notes