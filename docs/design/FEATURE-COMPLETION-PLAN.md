# Feature completion plan — all products + branding website

Goal: every feature tracked in `docs/feature-tracking/*.xlsx` is **fully implemented and
working** in every product where it applies, and the branding website markets/implements
them all. The Laravel app is the reference.

## Baseline (verified 2026-10-01, all green)

| Product | Suite | Other gates |
|---|---|---|
| `eskoofy-laravel-app` | 1079 passed / 2839 assertions | Pint clean |
| `eskoofy-php-app` | 351 passed / 943 assertions | — |
| `eskoofy-wp-theme` | `php -l` + PHPCS baseline | — |
| `eskoofy-nodejs-app` | 113 passed | `tsc --noEmit` ✅ · ESLint 0 errors · `route:parity` PARITY OK |
| `eskoofy-branding-website` | 129 passed / 365 assertions | — |

The two matrices were built from a source review that turned out to be **stale in both
directions**: rows 2 (onboarding banner) are already implemented in the theme and Node, while
several rows marked `done` in `lib/nav.ts` render **non-persisting stub forms**. So this plan
is driven by the fresh code audit below, not by the matrix cells.

## Audit result — real gaps

Legend: **[C]** correctness defect (feature exists but is broken/fake — worse than missing) ·
**[M]** missing entirely · **[P]** partial.

| ID | Product | Verdict | Feature | Evidence of the gap |
|---|---|---|---|---|
| C1 | branding | [C] | **Renewal never extends the license** | `PaymentStatusController.php:52` only acts when `license_id` is empty, but renewals always set it; `PaymentController.php:115` same for manual approval; `RenewalController.php:103` errors on the manual gateway's `success=false` pending result |
| C2 | branding | [C] | `LicenseManager::renew()` is dead + wrong | `LicenseManager.php:404-473`; zero callers; passes the license row as `$order`, ignores the gateway result, extends expiry before payment |
| C3 | branding | [C] | Admin → Settings silently resets service prices | `SettingsController.php:60-62` writes `services.*` defaults; `views/admin/settings.php` has no such fields |
| C4 | branding | [C] | Subscriptions are read-only | `SubscriptionController` has only `index()` |
| C5 | branding | [P] | bKash `live_url` not editable; `custom_request` email template not in `EmailTemplateController::KEYS` | `GatewayController.php:16-20`, `EmailTemplateController.php:13-20` |
| C6 | Node | [C] | `cms` / `global-labels` / `about` settings tabs are **dead forms** (no `action`, no values, no fetch) while `lib/nav.ts:167-168` claims `status:"done"` | `components/dashboard/ExtraScreens.tsx:10-40` |
| C7 | Node | [C] | `POST /payments/initiate` → 405; **no** gateway initiate/callback/webhook handler at all | `app/(site)/payments/page.tsx:84` → GET-only `app/(site)/[...path]/page.tsx`; webhook paths fall into the generic CRUD engine and **INSERT into `payments`** |
| C8 | Node | [C] | `onClick` inside async server components — every print button is a no-op | `FinalScreens.tsx:56`, `app/(site)/results/page.tsx:240`, `results/pdf/page.tsx:150`, `payments/receipts/[id]/page.tsx:61` (no `"use client"`) |
| C9 | Node | [C] | CMS editor posts to `/dashboard/cms/{page}/save` — route does not exist | `components/dashboard/CmsScreen.tsx:141` |
| C10 | Node | [P] | Public `?lang=` is dead — no layout passes `searchParams` to `resolveRequestLocale` | `app/(site)/layout.tsx:9`, `app/layout.tsx:25`, `app/(dashboard)/layout.tsx:76` |
| C11 | Node | [C] | Cloud-backup dispatcher has **zero callers**; scheduled backups can never fire | `lib/cloud-backup.ts:297-312` |
| C12 | Node | [C] | `/dashboard/onboarding` is a hardcoded stub (5 of 6 steps permanently `false`) | `components/dashboard/MiscScreens.tsx:136-144` |
| C13 | WP | [C] | `/login` has **no** rate limiting, lockout or backoff | `functions.php:397-449`; app: `AuthSessionController.php:30-52` |
| M1 | php-app, WP, Node | [M] | Dashboard write rate-limiting (120/min) | app `DashboardWriteThrottle.php`; php dashboard group `routes/web.php:87-799` has `AuthMiddleware` only; theme has no limiter; `middleware.ts` has none |
| M2 | WP | [M] | Year-end student promotion | zero `promote` code in the theme |
| M3 | WP, Node | [M] | Gateway webhooks/callbacks | theme: `verify_webhook()` implemented on 9 gateways and **called by nothing**, no POST webhook route; Node: falls into generic CRUD |
| M4 | Node | [M] | SMS carrier delivery | lifecycle only; `sendSmsCampaign` stops at `status:"queued"` |
| M5 | WP, Node | [M] | Push notifications (FCM) | theme: `esk_device_tokens` table written by nothing; Node: Prisma model only |
| M6 | WP, Node | [M] | Scheduled jobs / cron | theme: 1 hook (cloud backup) vs app's 5; Node: no cron at all |
| M7 | WP, Node | [M] | Recurring fees / auto-invoice | `recurring_payment_profiles` unused in both |
| M8 | php-app, WP, Node | [M] | Downloadable PDF | no PDF lib in any of the three |
| M9 | php-app, WP, Node | [M] | Queue / async jobs | none; WP sends bulk SMS synchronously inside the request |
| M10 | php-app | [C] | Activity log is **read-only** — matrix says Yes, nothing is ever written | `ActivityController.php:41,46` only reads `activity_log` |
| M11 | php-app | [P] | PWA manifest returns `'icons' => []` and no description/orientation/categories | `routes/web.php:58-70` |
| M12 | WP | [P] | No public-site print stylesheet; no print view for admit cards, ID cards, receipts, payslips | 0 `@media print` outside `app-dashboard.css` |
| M13 | WP | [P] | Global Labels editor is 22 flat fields; app has 28 grouped sections with EN **+ BN** inputs | `inc/admin-shell.php:10-33` vs `global-labels.blade.php:7-35` |
| P1 | Node | [P] | Exam publish semantics (`status` + `is_published`) | 0 hits for `isFullyPublished` |
| P2 | Node | [P] | Ledger double-entry auto-posting | 4 financial reports map to generic CRUD over `ledger_entries` |
| P3 | Node | [P] | Payroll run generation | `GeneratePayslips` only previews existing rows |
| P4 | WP | [P] | Bulk attendance filters by class only; app filters batch+section, and has per-student remarks | `views/admin/attendance-mark.php:57` |

---

## Wave 0 — correctness defects

Order matters: C1–C4 are money/data bugs, C6–C12 are "looks done, isn't" gaps that make the
matrix lie.

- **0.1 (C1)** branding: on a paid payment with `license_id` set, call
  `LicenseManager::createSubscription()` — in `PaymentStatusController::show()` and
  `PaymentController::approveManual()`. Give `RenewalController` the same
  `status === 'pending'` branch `CheckoutController.php:128-131` has.
- **0.2 (C2)** branding: delete the dead `LicenseManager::renew()`.
- **0.3 (C3)** branding: stop `SettingsController::update()` writing `services.*`.
- **0.4 (C4)** branding: add subscription cancel/resume (admin) + self-serve cancel (account).
- **0.5 (C5)** branding: add bKash `live_url` to the gateway form; add `custom_request` to
  `EmailTemplateController::KEYS`; add a success flash to `CustomRequestController::markRead()`;
  make the Node dashboard URL configurable.
- **0.6 (C6)** Node: real `cms` / `global-labels` / `about` tabs in the settings tab bar,
  reading `website_settings` and saving through a server action.
- **0.7 (C7)** Node: `lib/payments/gateways/*` + `POST /payments/initiate`,
  `POST /payments/callback/{gateway}`, `POST /payments/webhook/{gateway}` (never enveloped),
  signature verification, status page wiring.
- **0.8 (C8)** Node: extract the print buttons into `"use client"` components.
- **0.9 (C9)** Node: wire `POST /dashboard/cms/{page}/save`; add the live-preview iframe +
  `openMediaBrowser()` integration.
- **0.10 (C10)** Node: pass `searchParams.lang` into `resolveRequestLocale` in all layouts.
- **0.11 (C11)** Node: a cron endpoint that calls `dispatchCloudBackup()` and the backup +
  notification schedulers.
- **0.12 (C12)** Node: `/dashboard/onboarding` renders the real `getSetupChecklist()` data.
- **0.13 (C13)** WP: transient-based login throttling (5 attempts / 60 s decay, keyed
  `ip|email`, cleared on success) + a lockout notice in `template-login.php`.

## Wave 1 — missing features

- **1.1 (M1)** Dashboard write throttle: `Throttle:120,1` on the php dashboard group; a
  transient limiter in the theme's dashboard route renderer; a limiter branch in
  `middleware.ts`.
- **1.2 (M2)** WP: `esk-promote` page (source/target class+section+batch, keep-roll,
  promote-all, activity row).
- **1.3 (M3)** WP: `POST /esk/v1/payments/webhook/{gateway}` calling `verify_webhook()`;
  Node: real webhook/callback handlers.
- **1.4 (M4)** Node: SMS drivers (Twilio/Vonage/log) behind `lib/sms/`, driven by the cron.
- **1.5 (M5)** WP + Node: FCM push (settings, device-token registration endpoint, dispatcher).
- **1.6 (M6)** WP: `backup:database` daily, `notifications:process-scheduled` 5-min,
  `payments:process-recurring` daily; `scheduled_notifications` table. Node: same three via
  the cron endpoint.
- **1.7 (M7)** WP + Node: recurring-fee profile CRUD + invoice generation.
- **1.8 (M8)** PDF: php-app (minimal self-contained generator), WP, Node.
- **1.9 (M9)** A deferred-job equivalent: `jobs`/`failed_jobs` writes in php-app, a
  `schedule_single_event` queue in WP, a `pending_jobs` runner in Node.
- **1.10 (M10)** php-app: `ActivityLogger` service + writes from the dashboard controllers.
- **1.11 (M11)** php-app: full manifest (icons, description, orientation, categories,
  `application/manifest+json`, cache header).
- **1.12 (M12)** WP: public-site `@media print` + print views for admit cards, ID cards,
  receipts, payslips.
- **1.13 (M13)** WP: grouped Global Labels editor with EN + BN inputs across the app's sections.

## Wave 2 — partial → full

- **2.1 (P1)** Node: `isFullyPublished()` in the exam result screens + portal.
- **2.2 (P2)** Node: `lib/ledger.ts` double-entry auto-posting on payments/expenses.
- **2.3 (P3)** Node: payroll run generation.
- **2.4 (P4)** WP: bulk attendance batch+section filter + per-student remarks.

## Wave 3 — tracking + docs

- **3.1** Update `docs/feature-tracking/build-product-matrix.py` with the new evidence and
  regenerate `products-feature-matrix.xlsx`.
- **3.2** Append Phase 14 to `workplan-implementation-plan.md`.
- **3.3** Update `eskoofy-nodejs-app/docs/{PORTING-STATUS,NOT-IMPLEMENTED,MISMATCH-REPORT}.md`.
- **3.4** Update `docs/parity/product-parity.md`.
- **3.5** Update the root `README.md`; regenerate the branding matrix if anything changed.

## Rules

- No `variant === 'bd'` branching; BD/INT differences stay config/data.
- Byte-identical app→php view copies stay byte-identical (`diff` clean).
- Every change keeps a suite green; new behaviour gets a test.
