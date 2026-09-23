# Eskoofy App — Server Requirements

> Product: `eskoofy-laravel-app` (Laravel 12) · Use this page to size and prepare any server
> before installing. Install steps live in [`SETUP-GUIDE.md`](./SETUP-GUIDE.md); production
> go-live steps live in [`DEPLOYMENT-GUIDE.md`](./DEPLOYMENT-GUIDE.md).

---

## 1. Software requirements

| Software | Minimum | Recommended | Notes |
|---|---|---|---|
| PHP | **8.2** | **8.3** | Laravel 12 requires PHP >= 8.2 |
| Composer | 2.0 | 2.x | Dependency management (CLI/build only, not a runtime service) |
| Node.js | 18 | 20+ | Build-time only — Vite + Tailwind asset compilation |
| npm | 9 | 10+ | Build-time only |
| Web server | nginx / Apache | nginx + PHP-FPM | Document root = `public/` |
| Database | SQLite 3.35+ **or** MySQL 5.7+ / MariaDB 10.3+ / PostgreSQL 10+ | MySQL 8 / MariaDB 11 | SQLite is the dev default; MySQL recommended in production |
| Redis | optional | recommended for production | Cache + queue driver alternative |
| Process manager | — | supervisor / systemd | Runs the queue worker in production |

### PHP extensions

Enabled extensions (most ship on by default):

- Ctype, cURL, DOM, Fileinfo, Filter, Hash, Mbstring, OpenSSL, PCRE, PDO, Session, Tokenizer, XML
- **bcmath** and **zip** (Laravel artisan + installer)
- **gd** or **imagick** (image handling / uploads)
- `pdo_mysql` / `pdo_pgsql` / `pdo_sqlite` matching your database choice

### Build/CLI tooling

| Tool | Where used |
|---|---|
| `zip` / `unzip` | Backup command, artifact packaging |
| `git` | Deploys that pull from the repository |
| `mysql` / `pg_dump` (optional) | External backups managed by you |

> The application needs `shell_exec` / `proc_open` available for a few artisan features; do
> **not** disable them (some shared hosts do — verify with your host).

---

## 2. Hardware sizing

### CPU

| Setup | Minimum vCPU | Recommended |
|---|---|---|
| Development | 1 | 2 |
| Production — small school (< 100 concurrent users) | 1 | 2 |
| Production — medium (100–500) | 2 | 4 |
| Production — large / multi-branch (500+) | 4 | 8 |

### RAM

| Setup | Minimum | Recommended |
|---|---|---|
| Development (`php artisan serve`) | 512 MB | 1 GB |
| Small school (< 100 users) | 1 GB | 2 GB |
| Medium school (100–500 users) | 2 GB | 4 GB |
| Large / multi-branch (500+ users) | 4 GB | 8 GB |

Rough budget: PHP-FPM uses ~20–50 MB per worker; with 4 workers account for ~200 MB
for PHP alone, plus 256–512 MB for MySQL if co-located, plus a few hundred MB for the
queue worker and Vite/dev tooling (dev only).

### Disk

| Component | Space |
|---|---|
| Application code | ~50 MB |
| `vendor/` + `node_modules/` (build) | ~300–500 MB |
| User uploads (documents, photos, logos, media) | 500 MB – 5 GB (varies) |
| Database | 100 MB – 1 GB (varies) |
| Logs | 50–500 MB |
| OS + system packages | 5–10 GB |
| **Total minimum** | **~10 GB** |
| **Recommended** | **25–50 GB** |

Backups (`php artisan backup:database`) write self-contained dumps to `storage/app/backups`
(7 by default) — account for that space. Prefer a separate volume for `storage/` and the
database so backups and scaling are easy.

---

## 3. Network & services

| Item | Requirement |
|---|---|
| Outbound HTTPS | Needed for payment gateway calls, SMS providers, email, OAuth |
| Inbound | HTTP/HTTPS only (80/443) |
| SMTP | Outbound port 25/587/465 for mail (or use an API mailer) |
| DNS | A/AAAA record for `APP_URL`; TLS certificate |

Recommended optional services (free tiers are usually enough to start):

| Service | Purpose |
|---|---|
| Redis | Cache / sessions / queue in production |
| Cloudflare | CDN + SSL + DDoS protection |
| Mailgun / SendGrid / SES | Transactional email |
| Twilio / Vonage / TextLocal / Africa's Talking | SMS delivery |
| Firebase Cloud Messaging | Push notifications |

---

## 4. Web server requirements

- **nginx** (recommended) or **Apache** with `mod_rewrite`.
- PHP-FPM for nginx; `fastcgi` config must reach `public/`.
- HTTPS mandatory in production (Let's Encrypt, Cloudflare, or a paid cert).
- `storage/` and `bootstrap/cache/` must be writable by the web user.

See `DEPLOYMENT-GUIDE.md` § Nginx for a working config.

---

## 5. Shared hosting compatibility

The app **can** run on shared hosting (cPanel / Plesk / DirectAdmin) with caveats:

- PHP 8.2+ must be available (verify before purchase).
- Queue workers are usually impossible — set `QUEUE_CONNECTION=sync` (jobs run inline).
- Use SQLite if MySQL is not provided, or the host's MySQL account.
- Scheduler: shared hosts may offer cron — one line runs `php artisan schedule:run`.
- Pre-build assets locally (`npm run build`) and upload them.
- VPS is **recommended** for production: full control over PHP config, workers, cron, TLS.

---

## 6. Minimum viable production stack (summary)

| Piece | Choice |
|---|---|
| OS | Ubuntu 22.04/24.04 LTS (or Debian) |
| Web | nginx + PHP-FPM (8.2/8.3) |
| DB | MySQL 8 / MariaDB 11 |
| Cache/queue | Redis (or `database` for a small install) |
| Site | VPS, 2 vCPU / 2 GB RAM / 25 GB SSD as a comfortable floor |

---

## Related docs

- [`SETUP-GUIDE.md`](./SETUP-GUIDE.md) — install & configure
- [`DEPLOYMENT-GUIDE.md`](./DEPLOYMENT-GUIDE.md) — production deployment
- [`USER-MANUAL.md`](./USER-MANUAL.md) — usage
- [`../../docs/planning/server-requirements.md`](../../docs/planning/server-requirements.md) — monorepo-level sizing notes
- [`../../docs/operations/PRODUCTION-CHECKLIST.md`](../../docs/operations/PRODUCTION-CHECKLIST.md) — go-live checklist