# Eskoofy Monorepo — Security Implementation Plan

Derived from `docs/security/SECURITY-AUDIT.md` (2026-09-19). **Nothing in this
document has been implemented yet** — this is the roadmap. Each item maps to a
finding ID (`G#`, `A#`, `P#`, `T#`, `W#`, `R#`).

> **Execution status — 2026-09-19: COMPLETE.** All phases below were executed in
> one pass (see `workplan-implementation-plan.md` Phase 10 for the per-item log).
> Verification: app 927 tests, php 314 tests, website 95 tests all green;
> `composer audit` clean in all 4 products; app↔php view/lang parity `diff` clean.
> Residual items (documented, not fixed): per-route permission parity for
> teacher/accountant/librarian content modules in the php product (blocked
> roles are enforced centrally via `config/access.php`); machine-fingerprint
> challenge/response for license activation (rate limit + product-secret gate
> added instead); theme gateway-key UI is read-only (no write path to encrypt).

Rules that apply to every change:
- **Feature consistency (root AGENTS.md):** default scope = ALL products. Where a
  control is parity-relevant, implement in app **and** php **and** theme (and
  website where it hosts shared logic). Keep php views byte-identical where possible.
- **Golden rule:** BD/INT differences are config/data only — no `if (bd)` code.
- **Verify per product** with the commands in §7 after each phase.
- Do not break the byte-identical php view tree: security changes that touch views
  must be mirrored app→php (then re-`diff`).

---

## Phase 0 — Prereqs & baseline (no code, ~1h)

| Task | Finding | Product(s) | Owner check |
|---|---|---|---|
| 0.1 Pin a security contact + severity SLA | — | all | README/SECURITY note |
| 0.2 Snapshot current `composer audit` (all 4 clean) into a known-good baseline | G5 | all | CI gate later |
| 0.3 Confirm which prod hosting envs exist (app/php/website/theme) and their domains — needed for CORS/headers/session-secure values | G1/G2/A2 | all | infra |

---

## Phase 1 — Critical: authorization & license-server abuse (highest ROI)

### 1.1 php dashboard role/permission enforcement (P1) — **Critical**
Scope: `eskoofy-php-app`.
- Mirror the app's route middleware model: implement route-group middleware
  `Auth` + `Role`/`permission` in `app/Core/Router.php` (middleware already
  dispatched at `Router.php:111` — add named resolvers for `auth`, `role:*`,
  `permission:*` like the theme's `esk_role_caps` map or the app's permission matrix).
- Apply `role:admin` / `permission:*` to every `/dashboard` group + submodule group
  in `routes/web.php`, matching `eskoofy-laravel-app/routes/dashboard.php` group boundaries
  (lines 177-235).
- Belt-and-braces: add `Auth::requireRole(...)` (already exists) to sensitive
  controller actions (users, settings, fees, payroll, backups, bulk).
- Acceptance: a `student`-role login is blocked (403/redirect) from `/dashboard/users`,
  `/dashboard/settings`, `/dashboard/backup`; an `admin` passes.

### 1.2 License server: rate limit + auth (W1/W2/W3) — **Critical**
Scope: `eskoofy-branding-website`.
- Wire `ThrottleMiddleware` to `/api/v1/licenses/{activate,validate,deactivate}`
  (IP-based, strict — e.g. 5/min) and to `/login` (e.g. 10/5min, lockout).
- Add optional product-secret header (`X-Product-Secret`) required for activation;
  constant-time compare.
- Validate `domain` (hostname pattern) + cap `machine_id` length (already 191).
- Switch CSRF compare to `hash_equals` (W3) and add `session_set_cookie_params`
  before `session_start` (G2).
- Acceptance: 6 rapid activation attempts → 429; invalid domain rejected 422.

---

## Phase 2 — High: payment gates, default creds, theme module caps

### 2.1 php payment routes auth (P2) — **High**
- Add `Auth`/`requireAuth` to `/payments/initiate`, `/payments/status/{id}`,
  `/payments/receipts/{id}` in `eskoofy-php-app/routes/web.php` (parity with app lines 47-49).
- Mirror any controller-side guard too.

### 2.2 Remove default/weak credentials (P7/P8/R1) — **High**
- `eskoofy-php-app/database/schema.sql`: remove the seeded super_admin (`admin@eskoofy.com`/“password”) or replace with a random generated password + mandatory first-login change. Ship a dedicated `seed_admin` script instead.
- `eskoofy-php-app/database/seed_demo.php` + theme `inc/demo-content.php`: gate all seeding behind a non-production flag (`APP_ENV`/WP `ESK_DEMO=1`); strengthen passwords; keep doc-credentials file clearly demo-only.
- `docker/theme-test/docker-compose.yml` + README: fail fast if `WP_ADMIN_PASSWORD` is unset or `admin`; default `WP_DEBUG=0`.

### 2.3 Theme per-module capability gating (T1) — **High**
- Extend `esk_front_dashboard_pages()` in `inc/front-dashboard.php` so each page has an optional capability/permission key; enforce in the route renderer (alongside `esk_can_access_dashboard()`).
- Map modules to caps mirroring app `permission:*` names (e.g. `manage_fees`, `manage_payroll`, `manage_users`); extend `esk_role_caps` docs.
- Acceptance: a librarian with only `manage_library` cannot open `/dashboard/payroll`.

---

## Phase 3 — Medium: session/CSRF/header hardening (all products)

### 3.1 Session-cookie flags (G2)
- `eskoofy-php-app`: in `app/Core/bootstrap.php` (and `Session.php` constructor) call
  `session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=><env prod>,'httponly'=>true,'samesite'=>'Lax'])` before `session_start()`.
- `eskoofy-branding-website`: same in its `bootstrap.php`.
- `eskoofy-laravel-app`: ensure prod `.env` sets `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax` (document in `.env.production.example`).

### 3.2 Constant-time CSRF (G3)
- php + website bootstrap: replace `$token !== $_SESSION[...]` with
  `hash_equals((string)$token, (string)$_SESSION['csrf_token'])`.
- Rotate CSRF token on login / privilege change (both products).

### 3.3 Rate-limit public forms (G4 / P6)
- php: attach `ThrottleMiddleware` to newsletter, contact, scholarship, admissions
  apply, submit-payment (app parity `throttle:12,1`). App already correct.
- website: attach to `/login` and license endpoints (Phase 1.2).

### 3.4 Security headers (G1)
- Add per-product middleware/hook emitting: `Content-Security-Policy` (start
  report-only), `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`,
  `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`,
  `Strict-Transport-Security` (prod only). Suppress `X-Powered-By`.
  - app: middleware in `app/Http/Middleware` + `Kernel.php` web group.
  - php/website: `header()` calls in `public/index.php`.
  - theme: `send_headers` hook in `functions.php` (after dashboard shell detection).

### 3.5 CORS tightening (A2)
- app `config/cors.php`: restrict `allowed_methods` to actual verbs, `allowed_headers`
  to needed set, explicit prod origin(s) via env; keep `supports_credentials` only for
  the trusted front-end origin. php/website `CorsMiddleware` — same allow-list approach.

---

## Phase 4 — Medium: app & theme data-safety

### 4.1 Stored-XSS sanitization (A4)
- app: sanitize `content` (news, notifications, site pages) with an allow-list
  sanitizer on save (e.g. HTMLPurifier or a curated `sanitize_html` service); keep
  rich-text intent but strip scripts/event handlers. Mirror same rules in php (same views).
- Review `{!! $content !!}` email template to render sanitized/markdown-safe output.

### 4.2 Theme gateway keys at rest (T2)
- Encrypt `api_key`/`api_secret`/`webhook_secret` in `esk_payment_gateways` using
  `wp_salt`-derived AES-256 (openssl); decrypt only in gateway adapters; never
  re-render secrets into the settings form (mask).

### 4.3 Theme nonce audit (T3)
- Walk the 22 nonce-less `views/admin/*.php`; add `check_admin_referer` to any that
  accept POST. Add capability checks where a page is admin-only.

### 4.4 App API field hygiene (A5) + logging redaction (A6) + model guard (A7)
- Add API resources/`hidden` for public endpoints; redact payment payloads in logs;
  add `$guarded` to `DatabaseNotification`.

---

## Phase 5 — Low/Info hardening

| Task | Finding | Product(s) |
|---|---|---|
| Results-lookup rate limit + CSRF/session requirement (shortcode + REST) | T4 | theme |
| `esk_repair_site_url()` → CLI/admin-only gating | T5 | theme |
| `.htaccess`: deny dotfiles/.env, disable listing (add to website + theme too) | R4 | all |
| PWA: confirm dashboard/api/admin never cached; bump cache version on release | R3 | app, theme |
| `.env` parser → use phpdotenv or robust parser | W5 | website |
| Session role: re-read user role from DB per request | P9 | php |
| `_method` spoofing: reject `_method` on GET; audit no GET-mutation | P5 | php |
| Doc-only: mark `demo-credentials.md` demo-only; add SECURITY note to README | P8/R2 | all |

---

## Phase 6 — CI & verification (G5)

1. Add to CI: `composer audit` (fail on advisories), secret scanning (gitleaks or
   trufflehog), and a hard-coded-credential grep.
2. Add a lightweight "security smoke" test per product:
   - php/website: unit tests asserting `hash_equals` CSRF path + session cookie flags
     (session header inspection).
   - app: tests for permission matrix (student blocked from admin routes).
   - theme: test that a librarian cap cannot open payroll page.
3. Update `workplan-implementation-plan.md` as items land.

---

## Phase 7 — Final acceptance

Run the full verification matrix from `SECURITY-AUDIT.md` §7:
- Re-run `composer audit` in all 4 products → clean.
- Re-run all test suites: `cd eskoofy-laravel-app && composer test`; `cd eskoofy-php-app && composer test`;
  `cd eskoofy-branding-website && composer test`; `cd eskoofy-wp-theme && composer run lint`.
- Re-verify php↔app byte-identical views/langs (`diff -qr`).
- Manual pentest smoke: unauthorized role reaches nothing admin; license brute-force
  returns 429; session cookie shows HttpOnly+SameSite; headers present.

---

## Suggested order of execution (effort estimate)

| Phase | Effort | Depends on |
|---|---|---|
| 0 baseline | 1h | — |
| 1 critical (P1 + W1/W2/W3) | 1-2 days | 0 |
| 2 high (P2, P7/P8/R1, T1) | 1-2 days | 1 |
| 3 medium session/CSRF/headers/CORS | 1 day | 0 |
| 4 medium app/theme data-safety | 1-2 days | 3 |
| 5 low/info hardening | 1 day | 3 |
| 6 CI + tests | 1 day | 1-5 |
| 7 acceptance | 0.5 day | all |

Total ≈ **5-8 engineering days** across products, with the two Critical items (php
authorization, license brute-force) front-loaded.