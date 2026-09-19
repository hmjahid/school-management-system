# Eskoofy Monorepo — Security Audit

**Status:** READ-ONLY audit, no modifications. **Date:** 2026-09-19.
**Method:** manual source review (grep + targeted reads) across all products +
`composer audit` for known dependency CVEs. Findings are evidence-backed with
`file:line` references; verify counts/line numbers before acting as the codebase
may have moved.

Severity scale used below: **Critical** (remote compromise / data breach) ·
**High** (privilege escalation / sensitive data exposure) · **Medium**
(defense-in-depth gap) · **Low/Info** (hygiene).

**Top-line result:** no known-vulnerability dependencies (`composer audit` clean in
all 4 products), good baseline use of parameterized SQL + escaping + nonces in most
paths. The material problems are **authorization, rate limiting, session-cookie
hardening, and CSRF token comparison** — concentrated in the two raw-PHP products.

---

## 1. Cross-cutting

| # | Severity | Finding | Location | Fix |
|---|---|---|---|---|
| G1 | **High** | **No security response headers** (CSP, HSTS, X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy) set anywhere. `X-Powered-By` / server banner not suppressed. | app: no middleware (checked `app/Http/Middleware`); php/website: no `header()` calls; theme: no `send_headers`/`nocsrf` hook | Add a SecurityHeaders middleware per product; suppress `X-Powered-By`; emit CSP + HSTS (prod) + frame/type/referrer headers |
| G2 | Medium | **Session cookies lack explicit hardening flags.** app: `secure=null` (env), http_only/samesite lax (OK but relies on env); **php + website: `session_start()` with no `session_set_cookie_params()`** → PHP defaults: HttpOnly=off, SameSite none, Secure=off. | `eskoofy-php-app/app/Core/bootstrap.php:5`, `app/Core/Session.php:21`; `eskoofy-branding-website/app/Core/bootstrap.php`; `eskoofy-laravel-app/config/session.php:147-149` | Call `session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>prod])` before `session_start()`; app: force `SESSION_SECURE_COOKIE=true` in production profile |
| G3 | Medium | **CSRF token comparison is not constant-time** (`!==` instead of `hash_equals`). | `eskoofy-php-app/app/Core/bootstrap.php:57`; `eskoofy-branding-website/app/Core/bootstrap.php` (same pattern) | Use `hash_equals((string)$token, (string)$_SESSION['csrf_token'])` |
| G4 | Medium | **Rate limiting (ThrottleMiddleware) implemented but never wired** to any route in either raw-PHP product. | `eskoofy-php-app/app/Core/Middleware/ThrottleMiddleware.php`; `eskoofy-branding-website/app/Core/Middleware/ThrottleMiddleware.php` | Attach to auth, contact, admission, payment, and license endpoints (app already uses `throttle:12,1` / `config('api.rate_limits.*')`) |
| G5 | Info | **No automated secret-scan / dependency-scan in CI** (audit manually clean today; no guard against regressions). | `.github/workflows/*` | Add `composer audit` + gitleaks/trufflehog + `npx audit` steps |

---

## 2. eskoofy-laravel-app (Laravel 12)

Strengths: route-level middleware (`auth`, `role:admin`, `student_guardian`, `permission:*`),
form validation (`validate()`/FormRequest), Sanctum token expiry (60 min), payment webhook
signature verification with `hash_equals`/`hash_hmac` and fail-closed design, parameterized
queries, `@vite`-hashed assets.

| # | Severity | Finding | Location | Fix |
|---|---|---|---|---|
| A1 | **High** | **Authorization relies on route middleware but broad `role:admin` groups are coarse**; fine-grained `permission:*` only on selected routes. A role granted `manage_expenses` cannot access expense pages unless the group mapping is complete. Verify every controller action has a route middleware or policy (audit `routes/dashboard.php` group boundaries). | `routes/dashboard.php:177-235` | Complete the permission matrix; add `Gate::authorize`/Policy in controllers as belt-and-braces |
| A2 | Medium | **CORS is wide**: `paths:['*']`, `allowed_methods:['*']`, `allowed_headers:['*']`, `supports_credentials:true`. Origin allow-list = `APP_URL` only (mitigates), but any future multi-origin deployment would expose credentialed APIs. | `config/cors.php` | Restrict to explicit origins + methods/headers; keep `supports_credentials` only for the real front-end origin |
| A3 | Medium | **Sanctum `stateful` domains default to the full `APP_URL` family**; refresh/remember tokens 30d. Acceptable, but validate `SANCTUM_STATEFUL_DOMAINS` in production (subdomain cookie scoping). | `config/sanctum.php:18,73,86` | Set explicit prod domain list; consider lowering refresh TTL |
| A4 | Medium | **`{!! $article->content !!}` raw HTML render** of news/notifications. Input saved via admin with no explicit sanitization (only `string` validation). Stored-XSS risk if an admin account is compromised or content is bulk-imported. | `resources/views/site/news-show.blade.php:97`; `resources/views/emails/notification.blade.php:68`; `app/Http/Controllers/Web/DashboardNewsController.php:137` | Sanitize with an allow-list (HTMLPurifier / `sanitize_html`) on save; escape `{!! $content !!}` for email via a safe renderer |
| A5 | Medium | **Public API endpoints return schema data without explicit resource filtering** (e.g. `WebsiteContentController`, `TeacherController` cache key `md5(json_encode($request->all()))`). Enumerate accessible fields; confirm no PII leak via unauthenticated routes. | `app/Http/Controllers/Api/WebsiteContentController.php:58`, `Api/TeacherController.php:40` | Add `->hidden()` / API resources; test unauthenticated surface for PII |
| A6 | Low | `refund`/payment controllers log full `$request->all()` (may include card/gateway payloads in production logs). | `app/Http/Controllers/PaymentController.php:255,302-317` | Redact sensitive fields before logging |
| A7 | Low | `DatabaseNotification` model lacks `$fillable/$guarded` (mass-assignment guard). | `app/Models/DatabaseNotification.php` | Add `$guarded = []` or explicit `$fillable` |
| A8 | Info | `.env.example` contains placeholder keys (empty) — fine, but ensure real `.env` never committed (`.gitignore` covers it). | `.env.example` | (no change) — CI secret scan (G5) |

---

## 3. eskoofy-php-app (raw PHP)

Strengths: parameterized SQL everywhere seen (`Database::query(?, $params)`),
Blade `{{ }}` → `e()` escaping, CSRF enforced globally on state-changing methods,
`Auth::hashPassword` bcrypt cost 12, `session_regenerate_id(true)` on login,
Router `_method` only from `$_POST`.

| # | Severity | Finding | Location | Fix |
|---|---|---|---|---|
| P1 | **Critical** | **Zero role/permission authorization in the dashboard.** 466 `Auth::requireAuth()` calls, **0 `requireRole()`**. Any logged-in user (student/guardian) can access every admin module (`/dashboard/users`, `/dashboard/settings`, fees, payroll, backups…). | `app/Controllers/Dashboard/*` (all 68 controllers); `app/Core/Auth.php:71-86` | Route-group `Auth`+`Role`/permission middleware + per-action `requireRole`/`Gate::allows` parity with app route middleware; see IMPLEMENTATION PLAN |
| P2 | **High** | **Payment routes not auth-gated** (`/payments/initiate`, `/payments/status/{id}`, `/payments/receipts/{id}`) — the app gates them with `auth`. | `eskoofy-php-app/routes/web.php` (payment block) | Add `AuthMiddleware`/`requireAuth` to those routes |
| P3 | Medium | **Session cookie flags missing** (see G2) — session cookie is HttpOnly-off by PHP default. | `app/Core/bootstrap.php:5` | `session_set_cookie_params` before start |
| P4 | Medium | **CSRF compare `!==`** (see G3); CSRF token never rotated after login. | `app/Core/bootstrap.php:57` | `hash_equals` + regenerate token on privilege change |
| P5 | Medium | **`_method` spoofing gap**: Router honors `_method=DELETE` from POST, CSRF covers POST — OK, but ensure GET handlers never mutate state (audit). | `app/Core/Router.php:91-95` | Add a guard: reject `_method` on GET; ensure no GET routes mutate |
| P6 | Medium | **Public-form rate limiting absent** (newsletter/contact/admission/scholarship/submit-payment). App uses `throttle:12,1`. | `routes/web.php` (public POSTs) | Wire `ThrottleMiddleware` (see G4) |
| P7 | Medium | **Default admin credential seeded in `schema.sql`** (`admin@eskoofy.com` / bcrypt("password"), super_admin). If a deployment imports schema without changing, admin account is trivially known. | `database/schema.sql:2451-2464` | Remove seed from schema; provide a forced-password-change first-login; document |
| P8 | Medium | **Weak demo credentials** (`principal123`, `teach1234`, `accountant123`, `password`) in seeders + tracked `demo-credentials.md`. | `database/seed_demo.php:54-61`; `eskoofy-laravel-app/archive/project-root/demo-credentials.md` | Gate seeders behind `APP_ENV != production`; mark archive doc clearly as demo-only |
| P9 | Low | Auth role stored in session (`user_role`) — if a session is hijacked, role is trusted. | `app/Core/Auth.php:17,27` | Re-read role from DB per request (or at least on sensitive actions) |

---

## 4. eskoofy-wp-theme (WordPress)

Strengths: **202 `$wpdb->prepare` calls**; `$wpdb->delete($t,['id'=>absint(..)])` formatting;
nonce + `current_user_can('manage_options')` on all 5 AJAX handlers; `check_admin_referer`
on 63/85 admin views (rest are read-only report/dashboard/list pages); `esc_html/esc_attr/
esc_url` on output; `wp_kses_post` on content; results lookup shortcode nonce-protected;
no unescaped `$_GET/$_POST` echoes; safe redirects.

| # | Severity | Finding | Location | Fix |
|---|---|---|---|---|
| T1 | **High** | **Per-role dashboard gate is coarse**: `esk_can_access_dashboard()` grants any role listed in `esk_role_caps` full access to every dashboard module page. No per-module capability check at route level (each `views/admin/*.php` is gated only by "can access dashboard"). A "librarian" role with any granted cap can open payroll/fees/settings. | `inc/front-dashboard.php:230-247`; `inc/helpers.php:506-521` | Add per-page capability gate in `esk_front_dashboard_pages()` map (mirror app's `permission:*`/`role:admin` groups) |
| T2 | Medium | **Gateway API keys stored as plain WP options** (`api_key`/`api_secret`/`webhook_secret`) readable by any admin and in DB dump. | `inc/payment-gateways.php:122-123,229,405,496`; `views/admin/settings.php` | Encrypt at rest (wp_salt via openssl) or store only in config/env; never echo secrets back into the settings form |
| T3 | Medium | **Admin views without nonce on save/delete paths** — verify the 22 views flagged as nonce-less are truly read-only; if any accepts POST, add `check_admin_referer`. | `views/admin/{attendance,exams,admissions,reports,onboarding,student-detail,events-calendar}.php` et al. | Audit each; add nonce on any POST branch |
| T4 | Low | **REST `esk/v1/results/lookup`** returns result data keyed by admission number (guessing low-entropy admission numbers may disclose results). | `inc/rest-api.php` (`results/lookup`); `inc/shortcodes.php:25-42` | Add per-school rate limit + require session cookie/CSRF for shortcode lookup; consider CAPTCHA |
| T5 | Low | **`esk_repair_site_url()`** auto-rewrites `siteurl`/`home` — if triggered by a request-driven path it could be an open redirect/domain pivot. Ensure it is CLI/`wp`-only. | `inc/demo-content.php` (`esk_repair_site_url`) | Gate to `wp-cli` / admin nonce + `current_user_can('manage_options')` only |

---

## 5. eskoofy-branding-website (license server)

Strengths: license keys generated with `random_int` from an alphabet; activation enforces
`max_activations`; SQL parameterized; admin/account actions use middleware
(`AuthMiddleware`/`AdminMiddleware`); session regenerates on login.

| # | Severity | Finding | Location | Fix |
|---|---|---|---|---|
| W1 | **Critical** | **License `activate`/`validate`/`deactivate` are unauthenticated AND unthrottled.** Anyone can brute-force license keys against `/api/v1/licenses/activate`; success is distinguishable from failure by response. With 25-char keys this is slow, but machine/domain params are client-supplied so a stolen key is freely activatable on any machine. | `routes/api.php`; `app/Controllers/Api/LicenseApiController.php` (activate/checkLicense/deactivate have no auth); `ThrottleMiddleware` never attached | Rate-limit hard on these endpoints (IP + key hash); optionally require a product secret header; validate `domain` is a plausible hostname; HMAC-signed requests |
| W2 | **High** | **Activation trusts client-supplied `machine_id` and `domain`** (no challenge/response, no proof of possession). A leaked key can be activated on unlimited machines up to `max_activations`. | `app/Services/LicenseManager.php:227-309` | Add per-machine nonce + signed activation handshake; store fingerprint; consider device-graph abuse monitoring |
| W3 | Medium | **CSRF compare `!==`** (see G3) + no session cookie flags (see G2) + **ThrottleMiddleware never wired** (see G4) — admin login is brute-forceable. | `app/Core/bootstrap.php`; `app/Core/Middleware/ThrottleMiddleware.php` | Wire throttle on `/login`; `hash_equals`; cookie params |
| W4 | Low | `X-API-Key` status endpoint uses DB equality lookup (prepared) — fine, but the key is returned to the customer in plaintext on regenerate; ensure transport-only. | `app/Controllers/Account/DashboardController.php` (regenerate API token) | (no code change) — document that API keys are shown once |
| W5 | Info | Custom `.env` parser is naive (no multi-line/quoted values) — could mis-parse secrets containing `=` or spaces. | `app/Core/bootstrap.php` (`.env` loader) | Use `vlucas/phpdotenv` or a robust parser |

---

## 6. Repo-level / build / CI / PWA

| # | Severity | Finding | Location | Fix |
|---|---|---|---|---|
| R1 | **High** | **`docker/theme-test` WP admin default: `admin` / `admin`** (username AND password), MySQL `wpuser`/`wppass`, `WP_DEBUG=1` default. Local-only, but a leaked stack would be trivially compromisable; bad default hygiene. | `docker/theme-test/docker-compose.yml`, `README.md` | Require non-default creds; set `WP_DEBUG=0` unless explicitly enabled |
| R2 | Medium | **Demo credentials tracked in repo** (`archive/project-root/demo-credentials.md`; seeders). Not secrets per se but normalize to "demo only". | `eskoofy-laravel-app/archive/project-root/demo-credentials.md`; `eskoofy-php-app/database/seed_demo.php` | See P8 |
| R3 | Low | **PWA caches**: app `sw.js` correctly uses network-first for `/dashboard/` + `/api/` (good). Theme `sw.js` — confirm dashboard/api paths excluded from cache (app pattern is the reference). | `eskoofy-laravel-app/public/sw.js:30`; `eskoofy-wp-theme/pwa/sw.js` | Keep dashboard/admin/api out of SW cache; add cache-busting version bump on release |
| R4 | Info | `.htaccess` present in app + php `public/` (Laravel defaults) — verify deny rules for dotfiles/env in production hosting; add one for website + theme as relevant. | `eskoofy-laravel-app/public/.htaccess`; `eskoofy-php-app/public/.htaccess` | Add `FilesMatch` deny for `.env`/dotfiles; disable directory listing |

---

## 7. Prioritized remediation summary (also see IMPLEMENTATION PLAN)

1. **php dashboard authorization (P1)** — Critical, closes vertical privilege escalation.
2. **License server brute-force + auth (W1/W2/W3)** — Critical, closes licensing abuse.
3. **Session-cookie hardening everywhere (G2)** + **CSRF `hash_equals` (G3)** + **throttle wiring (G4)**.
4. **php payment routes auth (P2)**; **default creds (P7/P8/R1)**; **theme per-module caps (T1)**.
5. Defense-in-depth: security headers (G1), CORS tightening (A2), stored-XSS sanitization (A4),
   gateway key encryption (T2), nonce audit (T3), PWA/htaccess hygiene (R3/R4), CI secret scan (G5).