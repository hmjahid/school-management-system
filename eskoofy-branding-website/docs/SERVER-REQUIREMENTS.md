# Eskoofy Website — Server Requirements

> Product: `eskoofy-branding-website` (marketing site + **license server**, raw PHP 8.2,
> no framework, no Composer at runtime) · Use this page to size and prepare a server.
> Install steps live in [`SETUP-GUIDE.md`](./SETUP-GUIDE.md); go-live in
> [`DEPLOYMENT-GUIDE.md`](./DEPLOYMENT-GUIDE.md).

---

## 1. Software requirements

| Software | Minimum | Recommended | Notes |
|---|---|---|---|
| PHP | **8.2+** | **8.3** | Raw PHP front-controller; no framework at runtime |
| MySQL / MariaDB | 5.7/8 / 10.x | MySQL 8 / MariaDB 11 | 15-table licensing schema (utf8mb4) |
| Web server | Apache (`mod_rewrite`, `.htaccess`) or nginx | nginx + PHP-FPM | Docroot **must** be `public/` |
| Composer | 2.x | 2.x | **Dev tooling only** (PHPUnit) — not needed at runtime |

### PHP extensions

- Required: `pdo_mysql`, `curl`, `openssl`, `zip`, stream sockets
  (payment gateways, Paddle webhook verification, backup/package ZIPs, SMTP)
- Mail: `mbstring`, `filter`, `session` (standard on mainstream hosts)

### Writable directories

`storage/{backups,documents,packages,logs,cache,framework}` must be writable by the web user
(backups, package uploads, client documents, logs, caches).

---

## 2. Hardware sizing

The site is a lightweight PHP app plus the license API. The real DB growth comes from
`activity_logs`, `visitors`, `payments` and `license_activations`.

| Setup | Min CPU | Min RAM | Recommended |
|---|---|---|---|
| Staging / small lane (< 10k visits/mo) | 1 vCPU | 512 MB | 1 GB |
| Production (10k–100k visits/mo) | 1–2 vCPU | 1 GB | 2 GB |
| Larger / busy licensing fleet | 2 vCPU | 2 GB | 4 GB |

| Component | Disk |
|---|---|
| Application code + PWA assets | ~50 MB |
| Packages + client documents (uploads) | 500 MB – 10 GB (varies with sales) |
| Database (licenses, activations, visitors, logs) | 200 MB – 5 GB |
| Backups (ZIPs) | 500 MB – 5 GB |
| OS + packages | 5–10 GB |
| **Total recommended** | **25 GB SSD** |

---

## 3. Network & services

| Item | Requirement |
|---|---|
| Inbound | HTTP/HTTPS only (80/443) |
| Outbound HTTPS | Payment gateways (Stripe/PayPal/Paddle/bKash/Rocket/Nagad), SMTP, optional geo-lookup (`GEO_IP_API_URL`) |
| SMTP | Transactional email (license reminders, package emails) |
| DNS + TLS | `APP_URL` + certificate (the license client calls this host) |
| License API reachability | The products call `/api/v1/activate|validate|deactivate|status|ping` — this host must be reachable (HTTPS) from every customer install |

---

## 4. Web server requirements

- Document root = **`public/`** (front controller `public/index.php`).
- Apache: shipped `public/.htaccess` rewrites non-file/non-dir to `index.php`, blocks
  `.env`, `.json`, `.md`, `.log`, `.sql` and dotfiles, sets security headers.
- nginx: `try_files $uri $uri/ /index.php?$query_string;` + PHP-FPM (see the
  [Setup Guide](SETUP-GUIDE.md#5-web-server)).
- CSRF is enforced on state-changing POST/PUT/PATCH/DELETE **except** `/api/*` and
  `/webhooks/*` (signature-verified).
- HTTPS mandatory — customers' installs validate licenses over TLS.

---

## 5. Minimum viable production stack (summary)

| Piece | Choice |
|---|---|
| Hosting | Shared hosting (cPanel/Plesk) or a 1–2 vCPU VPS |
| Web | nginx + PHP-FPM 8.2 (or Apache) |
| DB | MySQL 8 / MariaDB 11 |
| Mail | SMTP or API mailer |
| TLS | Let's Encrypt / AutoSSL / Cloudflare |

---

## Related docs

- [`SETUP-GUIDE.md`](./SETUP-GUIDE.md) — install & configure
- [`DEPLOYMENT-GUIDE.md`](./DEPLOYMENT-GUIDE.md) — production deployment
- [`USER-MANUAL.md`](./USER-MANUAL.md) — usage
- [`../../docs/planning/server-requirements.md`](../../docs/planning/server-requirements.md) — monorepo-level sizing notes