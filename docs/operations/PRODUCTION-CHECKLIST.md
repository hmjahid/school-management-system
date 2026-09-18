# Production Security Checklist

Use this checklist before every production deployment. Each item lists where the
setting lives in this repository and how to verify it.

## 1. Application

| # | Item | Where | Verify |
|---|------|-------|--------|
| 1 | `APP_ENV=production` | `.env` | `php artisan about` shows production |
| 2 | `APP_DEBUG=false` | `.env` | `APP_DEBUG` is `false`; generic error messages served outside debug |
| 3 | `APP_URL` matches the real HTTPS origin | `.env` | Links/generated URLs use `https://` |
| 4 | `APP_KEY` is a unique 32-byte key (generated, never shared) | `.env` | `php artisan key:generate --show`; differs on staging/prod |

## 2. Auth & sessions

| # | Item | Where | Verify |
|---|------|-------|--------|
| 5 | Strong admin password set via `ADMIN_PASSWORD` env, **not** the seeder default | `AdminUserSeeder` | Production seeds abort if the default/weak password is used |
| 6 | `ADMIN_EMAIL` set to a real mailbox | `.env` | Seeder uses env-driven email |
| 7 | `SESSION_DRIVER=database` (or redis) | `.env` | Default is `database` in `.env.example` |
| 8 | `SESSION_SECURE_COOKIE=true` on HTTPS | `.env` | `config/session.php` reads `SESSION_SECURE_COOKIE` |
| 9 | `SESSION_ENCRYPT=true` for sensitive session data | `.env` | `config/session.php` reads `SESSION_ENCRYPT` |
| 10 | `SESSION_LIFETIME` set to policy (default 120 min) | `.env` | Session idle timeout enforced |
| 11 | Unused admin accounts disabled/removed | DB | Audit `users` with role `admin` |
| 12 | 2FA/login throttling on auth routes | `routes/web.php`, auth controllers | Failed login throttling configured |

## 3. Transport & infrastructure

| # | Item | Where | Verify |
|---|------|-------|--------|
| 13 | HTTPS enforced (HSTS) | web server + `SecurityHeaders` middleware | All traffic `301` → `https://` |
| 14 | `TRUSTED_PROXIES` set to the load balancer / CDN CIDRs, not `*` | `.env` | `TRUSTED_PROXIES` is a CIDR list in production |
| 15 | PHP execution blocked in `/storage` | `public/.htaccess` (+ nginx config) | Uploaded `.php` files return 403/500, not executed |
| 16 | `/storage`, `/bootstrap/cache`, `.env` not web-served | web server config | Direct requests 404 |
| 17 | Sanity TLS versions (TLS 1.2+) | web server | `openssl s_client -connect host:443 -tls1_2` succeeds |

## 4. Payments & secrets

| # | Item | Where | Verify |
|---|------|-------|--------|
| 18 | No real API keys/secrets in `config/payment.php` — only env-driven values | `config/payment.php`, `.env` | `grep -r` for old test credentials returns nothing |
| 19 | Runtime gateway credentials stored via `payment_gateways` table or env, never committed | DB, `.env` | `PaymentGateway::grantConfig($gateway)` masks secrets |
| 20 | Refund + payment webhooks use server-side secret HMAC with idempotency | `RefundController`, `PaymentService` | Webhook replays return `idempotent: true` |
| 21 | Refund/gateway API base URLs point at live endpoints, not sandbox | `payment_gateways` table / config | Live `getApiConfig()` values verified |
| 22 | `payment` API endpoints behind `auth:sanctum` + policies | `routes/payments.php` | Unauthenticated `/initiate`, `/status` rejected (401) |

## 5. Public site & CMS

| # | Item | Where | Verify |
|---|------|-------|--------|
| 23 | CMS mutations require `role:admin` | Routes/controllers | Non-admin get 403 |
| 24 | Uploaded media MIME-validated and served via download controller | `AdmissionController`, CMS controllers | `.php` uploads rejected |
| 25 | `WebsiteSetting`/`PaymentGateway` serialization hides secrets | Model `$hidden` | API responses never expose secrets |
| 26 | Public result lookup rate-limited | `routes/api.php` | Brute force throttled |

## 6. Hardening & monitoring

| # | Item | Where | Verify |
|---|------|-------|--------|
| 27 | Security headers (CSP, X-Frame-Options, HSTS, nosniff) served | `SecurityHeaders` middleware | `curl -I` shows headers |
| 28 | Dashboard state-changing routes rate-limited | `routes/dashboard.php` | 429 after threshold exceeded |
| 29 | Error monitoring configured (Sentry or log ingestion) | `config/logging.php`, `SENTRY_*` env | Errors alert the team |
| 30 | Queue failures monitored (database `failed_jobs` table) | scheduler/worker | Failed jobs retried/alerts |
| 31 | Scheduled database backups verified by restore drill | scripts + runbook | Restore succeeds on staging |
| 32 | `composer audit` clean for direct dependencies | CLI | No known vulnerabilities |

## Verification commands

```bash
php artisan about                 # env + debug state
php artisan config:show app.debug # config used, not .env
php artisan route:list            # no duplicate names
composer audit                    # dependency vulnerabilities
curl -sI https://your-host/       # security headers
openssl s_client -connect your-host:443 -tls1_2   # TLS 1.2+
```

## Sign-off

Deployment date / commit: 2026-08-31 / main  
Signed off by: mdjahidhasan