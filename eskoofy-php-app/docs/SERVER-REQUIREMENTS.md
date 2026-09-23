# Eskoofy PHP — Server Requirements

> Product: `eskoofy-php-app` (raw PHP 8.2+, no framework, no Composer at runtime) · Use this
> page to size and prepare a server — including **shared hosting**. Install steps live in
> [`SETUP-GUIDE.md`](./SETUP-GUIDE.md); go-live steps in [`DEPLOYMENT-GUIDE.md`](./DEPLOYMENT-GUIDE.md).

---

## 1. Software requirements

| Software | Minimum | Recommended | Notes |
|---|---|---|---|
| PHP | **8.2** | **8.3** (tested) | No framework at runtime — plain PHP is enough |
| MySQL / MariaDB | MySQL 8 / MariaDB 10.x | MariaDB 11 | 99-table schema (InnoDB, utf8mb4) |
| Web server | Apache 2.4 (`mod_rewrite`) or nginx | nginx + PHP-FPM | Docroot must be `public/` |
| Composer | 2.x | 2.x | **Dev tooling only** (PHPUnit) — not needed at runtime |
| Node.js + npm | 18+ | 20+ | Optional Vite asset build only |

### PHP extensions

- Required: `pdo_mysql`, `mbstring`, `json`, `session`, `ctype`, `openssl`, `fileinfo`, `curl`
- Nice-to-have: `zip`, `gd`, `intl`, `pcntl` (long-running cron)
- `PDO` with the `mysql` driver is mandatory (no SQLite/Postgres in this product)

> The app parses `.env` itself, uses a hand-written autoloader, and needs **no** Composer
> vendor directory at runtime — this is exactly what makes it shared-hosting friendly.

---

## 2. Hardware sizing

| Setup | Min CPU | Min RAM | Recommended |
|---|---|---|---|
| Development (`php -S`) | 1 vCPU | 512 MB | 1 GB |
| Small school (< 100 users) | 1 vCPU | 1 GB | 2 GB |
| Medium school (100–500 users) | 2 vCPU | 2 GB | 4 GB |
| Large / multi-branch (500+ users) | 4 vCPU | 4 GB | 8 GB |

Rough budget: PHP-FPM ~20–50 MB per worker; MySQL co-located needs ~256–512 MB. This product
is deliberately light — it runs comfortably on entry VPS/shared plans.

| Component | Disk |
|---|---|
| Application code | ~30 MB |
| Database | 100 MB – 1 GB |
| Uploads (photos, documents, media) | 500 MB – 5 GB |
| Logs | 50–500 MB |
| OS + packages | 5–10 GB |
| **Total recommended** | **25 GB SSD** |

---

## 3. Shared hosting compatibility

This is the **primary target** for the raw-PHP product — it runs where Composer/Laravel/VPS
are unavailable, provided the host offers:

- PHP 8.2+ with `pdo_mysql`, `mbstring`, `curl`, `openssl`
- MySQL/MariaDB database(s)
- Docroot control so you can point the domain at `public/` (or the shipped root `.htaccess`
  forwards into `public/`)
- Cron access (for `cron.php`) — most cPanel/Plesk plans have it

No queue workers, no VPS root, no Composer, no Node on the server required.

---

## 4. Web server requirements

- Apache with `mod_rewrite` (`.htaccess` provided) or nginx (`try_files` to `index.php`).
- PHP-FPM for nginx; `mod_php` on Apache also works for small sites.
- HTTPS mandatory in production.
- `storage/` writable by the web user.

---

## 5. Minimum viable production stack (summary)

| Piece | Choice |
|---|---|
| Hosting | Shared hosting (cPanel/Plesk) **or** 1 vCPU / 2 GB VPS |
| Web | nginx + PHP-FPM 8.2 (or Apache) |
| DB | MySQL 8 / MariaDB 10/11 |
| Cron | One line: `php public/cron.php` every minute |

---

## Related docs

- [`SETUP-GUIDE.md`](./SETUP-GUIDE.md) — install & configure
- [`DEPLOYMENT-GUIDE.md`](./DEPLOYMENT-GUIDE.md) — production deployment
- [`USER-MANUAL.md`](./USER-MANUAL.md) — usage
- [`../../docs/planning/server-requirements.md`](../../docs/planning/server-requirements.md) — monorepo-level sizing notes