# Eskoofy Website (Branding + License Server) — User Manual

> Component: `eskoofy-website` (raw PHP 8.2+, marketing/branding site **+ license server**) · Audience: site visitors, customers, and Eskoofy staff who administer the site · Docs language: English

`eskoofy-website` is **not a school product** — it is the **branding/marketing website plus the
license server** that markets and sells the three Eskoofy products (`eskoofy-app`,
`eskoofy-php`, `eskoofy-theme`). It is a single-variant (`int` = English/USD/UTC) raw-PHP
front-controller app with no framework at runtime. This manual covers three surfaces:

1. **Frontend** — the public marketing site (§2)
2. **Backend** — the license server JSON API + payment/webhook flow (§3)
3. **Dashboards** — the customer account portal (§4) and the admin backend (§5)

- Setup, install and deployment: [`SETUP-GUIDE.md`](./SETUP-GUIDE.md)
- Product-vs-website terminology: [`../../docs/design/NODEJS-VARIANT.md`](../../docs/design/NODEJS-VARIANT.md)

---

## 1. Public frontend (marketing site)

| Page | Route | What it does |
|---|---|---|
| Home | `/` | Hero, counters, three product cards, how-it-works, roles, pricing preview (from DB plans), payment methods, testimonials, latest posts |
| Product detail | `/products/{slug}` (`app`, `theme`, `php`) | Benefits, how it runs, per-plan pricing, getting-started, FAQ |
| Pricing | `/pricing` | Monthly/yearly plans per product, USD↔BDT switch for BD visitors, billing toggle, deployment/care add-on, FAQ |
| Features | `/features` | Role tabs (Admin/Teacher/Parent) and module groups |
| Compare | `/compare` | Comparison table vs other school systems |
| About | `/about` | About content + CTA |
| Blog | `/blog`, `/blog/category/{slug}`, `/blog/{slug}` | Paginated posts + categories; increments views; BlogPosting JSON-LD |
| Contact | `/contact` (GET form, POST submit) | CSRF-protected form → `contact_messages` + email to the site contact address |
| Legal | `/refund-policy`, `/terms`, `/privacy` | Policy pages |
| Language | `/language/{locale}` (`en`/`bn`), `/language/geo` | Manual language switch (always wins) + timezone geo hint |

**Checkout / purchase:**
- `GET/POST /checkout` — choose plan + gateway, create a pending `payments` row (variant `bd`/`int`).
- `GET /checkout/status/{reference}` — gateway return/callback page; verifies the payment
  idempotently and, on success, issues a **license** + **subscription**.
- `POST /webhooks/{gateway}` — signature-verified Stripe/PayPal/Paddle webhooks (extend
  subscriptions for auto-renewal).

**SEO / PWA:** canonical + OG/Twitter + hreflang, JSON-LD (Organization, WebSite, WebPage,
Product, FAQ, BlogPosting, BreadcrumbList), `sitemap.xml`, `robots.txt`, `manifest.json`,
service worker (`sw.js`) that never caches `/api`, `/account`, `/admin`, `/checkout`.

---

## 2. Backend — the license server

### 2.1 JSON API (`routes/api.php`, prefix `/api/v1`)

| Method | Endpoint | Purpose | Throttle | Auth |
|---|---|---|---|---|
| `GET` | `/api/v1/ping` | Health check — service name, version, timestamp | — | none |
| `POST` | `/api/v1/licenses/activate` | Bind a license to a domain (+ optional machine id) | 10/min | product secret (if configured) |
| `POST` | `/api/v1/licenses/validate` | Check a license is valid for a domain | 30/min | product secret (if configured) |
| `POST` | `/api/v1/licenses/deactivate` | Release a domain/machine activation | 10/min | product secret (if configured) |
| `GET` | `/api/v1/licenses/status` | List a customer's licenses | 30/min | `X-API-Key: <customer api_token>` |

**Header contract:**
- `X-API-Key` — required for `/licenses/status`; it is the customer's `api_token` (visible and
  regenerable in the account dashboard).
- `X-Product-Secret` — when the env var `LICENSE_PRODUCT_SECRET` is set, activate/validate/
  deactivate require this header, compared in constant time. Unset = check skipped.

**Request bodies** use `{ "license_key": "...", "domain": "...", "machine_id": "..." }`.
Domains must be a plain hostname (or `localhost`) — schemes, paths and null bytes are rejected.
Machine IDs are normalised to printable ASCII (≤191 chars).

```bash
# Activate
curl -X POST https://<site>/api/v1/licenses/activate \
  -H "Content-Type: application/json" \
  -H "X-Product-Secret: $LICENSE_PRODUCT_SECRET" \
  -d '{"license_key":"ESK-XXXX-XXXX-XXXX","domain":"school.example.com"}'
```

### 2.2 License engine (`app/Services/LicenseManager.php`)

- **Key format:** `PREFIX-XXXX-XXXX-XXXX` (default prefix `ESK`, ambiguity-free alphabet).
- **Lifecycle:** `issue()`, `activate()`, `validate()`, `deactivate()`, `renew()`,
  `createSubscription()`, `expiryFor()` (monthly / yearly / one-time), `isExpired()`,
  `activeActivationCount()`.
- **Error codes:** `invalid_license`, `license_<status>`, `license_expired`,
  `max_activations_reached`, `no_activation`, `plan_not_found`.
- **Audit:** every issue/activate/reactivate/deactivate/renew writes an `ActivityLog` entry.
- **Products/variants:** products are `app`, `theme`, `php`; the website itself is always `int`;
  the customer's country drives `bd` vs `int` at checkout.

### 2.3 Payments

- **Gateways** (`config/gateways.php`, `app/Gateways/`): `manual` (bank transfer, always
  available), `bkash`, `rocket`, `nagad` (BD) and `stripe`, `paypal`, `paddle` (INT).
- **Region routing:** a BD country shows bKash/Rocket/Nagad (+manual); everywhere else shows
  Stripe/PayPal/Paddle (+manual). Only enabled **and** configured gateways are shown.
- **Currency:** plans store canonical USD; BD customers see BDT via `GATEWAY_BDT_RATE`.
- **Webhook signatures:** Stripe HMAC-SHA256, Paddle OpenSSL verify; all handled in
  `WebhookController@handle`.
- **Manual payments** stay pending until an admin approves them at `/admin/payments`
  (approval issues the license + subscription and emails the customer).

### 2.4 Email & geo-language

- **Mailer** (`app/Services/Mailer.php`) is dependency-free: driver from settings (`smtp` over
  TLS/SSL with AUTH LOGIN, or PHP `mail()`/sendmail). Six templates
  (`welcome`, `license_issued`, `payment_received`, `license_expiring`, `package_available`,
  `contact_message`) can be overridden in the admin.
- **Geo-language** (`GeoLocale` + `LocaleMiddleware`): CDN country headers → Accept-Language →
  remote IP API → `Asia/Dhaka` timezone hint; BD → `bn`, otherwise `en`. A manual
  `/language/{locale}` switch always wins.

---

## 3. Customer account dashboard (`/account`, login required)

| Page | Route | Features |
|---|---|---|
| Dashboard | `/account` | Stats (licenses, active, spend, activations), 6-month spend chart, notifications, upcoming renewals (<45 days), API key + regenerate, profile, recent licenses/payments |
| Licenses | `/account/licenses` | License cards + activation API sample |
| License detail | `/account/licenses/{id}` | Status, subscription, activations, **renew**, revoke |
| Payments | `/account/payments` | Payment history |
| Downloads | `/account/downloads` | Packages for licensed products + client documents |
| Settings | `/account/settings` | Profile, password, language preference |

Customers self-register at `/register` and sign in at `/login`. Renewal creates a payment +
subscription via the chosen gateway (`/account/licenses/{id}/renew`).

---

## 4. Admin backend (`/admin`, role `admin`/`super_admin`)

| Page | Route | Features |
|---|---|---|
| Dashboard | `/admin` | KPIs, MRR/ARR, 12-month revenue trend, licenses by status/product, expiring ≤30 days, unread messages, quick actions |
| Customers | `/admin/customers`, `/admin/customers/{id}` | List/search, detail, suspend/reactivate |
| Plans | `/admin/plans` (+ create/edit) | CRUD plans (product, price, period, max activations, active) |
| Licenses | `/admin/licenses` (+ create/detail) | Issue license (existing or new customer), suspend/activate, extend by days, view activations |
| Payments | `/admin/payments` | List, CSV export, set status, **approve manual payment** (issues license + emails) |
| Subscriptions | `/admin/subscriptions` | Subscription list + MRR/ARR |
| Blog | `/admin/posts`, `/admin/post-categories` | Post & category CRUD, draft/publish, SEO fields |
| Inbox | `/admin/messages` | Contact messages, mark read |
| Activity | `/admin/activities` | Activity log (50/page) |
| Settings | `/admin/settings` | Site name/tagline/contact/currency, product dashboard URLs, appearance, email/SMTP, add-on prices |
| Account | `/admin/account` | Admin profile & password |
| Services | `/admin/services` | Deployment/care add-on pricing |
| Packages | `/admin/packages` | Upload a ZIP per product and email it to licensed clients |
| Client documents | `/admin/client-documents` | Upload **user manual / setup guide / other** documents and email them to licensed clients |
| Email templates | `/admin/email-templates` | Override subject/body for the six templates |
| Push notifications | `/admin/push-notifications` | Broadcast to account dashboards (+ optional email) |
| Gateways | `/admin/gateways` | Enable/configure bKash/Rocket/Nagad/Stripe/PayPal/Paddle |
| Cache | `/admin/cache` | Clear storage caches / opcache / APCu, bump cache version |
| Backup | `/admin/backup` | Full (SQL + public assets), licenses, users backups — no `mysqldump` needed |

> **Distributing these docs:** the **Client documents** module is designed for exactly this —
> upload this manual and the setup guide and email them to licensed customers.

---

## 5. Verification status

Verified on 2026-09-19 against this working tree:

| Check | Command / method | Result |
|---|---|---|
| Test suite | `cd eskoofy-website && composer test` | ✅ **95 tests passed** (270 assertions) |
| Route→controller→method integrity | static scan of `routes/web.php` + `routes/api.php` | ✅ **113 targets, 0 broken** |
| View resolution | static scan + `View::resolve` | ✅ all static views resolve |
| Bug found & fixed | `payment-status` view | ✅ see below |

**Bug found and fixed during verification:** `PaymentStatusController` rendered
`$this->view('site.payment-status')`, but `App\Core\View::resolve()` converts dashes to
underscores, so it looked for `views/site/payment_status.php` while the file was named
`payment-status.php` — the checkout return page rendered **only the layout** (blank content) for
pending/cancelled payments. Fixed by renaming the view to `views/site/payment_status.php`
(matching the convention used by every other view, e.g. `client-documents` →
`client_documents.php`) and strengthening the regression test to assert `View::resolve()`
points at an existing file rather than merely checking a filename.

---

## Related docs

- [`SETUP-GUIDE.md`](./SETUP-GUIDE.md) — install, configure, deploy the site + license server
- [`../README.md`](../README.md) · [`../AGENTS.md`](../AGENTS.md)
- [`../../docs/design/NODEJS-VARIANT.md`](../../docs/design/NODEJS-VARIANT.md) — product vs website
- [`../../docs/design/PAYMENT-MODEL.md`](../../docs/design/PAYMENT-MODEL.md)
- [`../../docs/operations/DEMO-CREDENTIALS.md`](../../docs/operations/DEMO-CREDENTIALS.md)
- [`../../docs/guides/DEVELOPMENT.md`](../../docs/guides/DEVELOPMENT.md)
