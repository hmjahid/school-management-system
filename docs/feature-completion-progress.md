# Feature-Completion Task — Progress Log

Task: audit feature-tracking matrices and make every applicable tracked feature
fully implemented, persistent, and tested across the four product variants and
the branding site. Plan: `docs/design/FEATURE-COMPLETION-PLAN.md`.

Last updated: during the current work session (see per-item "Last checked").

---

## Finished (implemented + tested green)

### 1. Branding — renewal/license-extension correctness  (Wave 0.1–0.5) ✅
- `LicenseManager::renew()` no longer fabricates payments; delegates to
  `createSubscription()` with renewal stacking.
- Paid renewal payments extend the existing license (`PaymentStatusController`);
  manual approval extends too; pending/offline renewals no longer report failure.
- Settings no longer resets `services.*` prices (ownership stays in ServiceController).
- Subscription management: admin pause/resume/cancel + customer self-serve
  cancel/resume (controller + routes + views).
- `custom_request` email template editable; gateway `live_url` overrides honored
  by Stripe/PayPal/Paddle; all four product dashboard URLs configurable (incl. Node).
- Verification: `composer test` → **137 tests / 413 assertions green**; php -l clean.
- Tests added: `tests/Unit/Admin/SettingsAndGatewayConfigTest.php`,
  `tests/Unit/Services/LicenseManagerTest.php` (renewal stacking).

### 2. WP theme — login + dashboard write rate limiting  (Wave 0.13 + 1.1-WP) ✅
- New `inc/throttle.php`: transient-backed fixed-window counter, login limiter
  (5/min per ip+login), dashboard write limiter (120/min per user+ip), lockout
  message on `template-login.php`.
- `inc/front-dashboard.php` emits a 429 page for throttled dashboard writes.
- Verification: `composer test` → **ALL PASS** (`tests/throttle-test.php`); php -l clean.

### 3. php-app — rate limiting (Wave 1.1-php + login throttling) ✅
- New `app/Services/RateLimiter.php` (file-backed, survives session regeneration).
- New `app/Core/Middleware/DashboardWriteThrottle.php` mounted on `/dashboard`
  group; `LoginThrottleMiddleware` on POST /login, /student/login, /guardian/login;
  buckets cleared on successful sign-in.
- Rewrote `ThrottleMiddleware` (was session-scoped + off-envelope `{"error":...}`).
- New `views/errors/429.php`.
- Verification: `composer test` → **375 tests / 1008 assertions green** (was 351).
- Tests added: `tests/Unit/Services/RateLimiterTest.php`,
  `tests/Unit/Core/RateLimitingTest.php`.

### 4. Node — dashboard write rate limiting (Wave 1.1-node) ✅
- New `lib/rate-limit.ts` (dependency-free, edge-safe, mirrors Laravel API).
- Wired into `middleware.ts` for POST/PUT/PATCH/DELETE on /dashboard + /account.
- Verification: `npm test` → **123 tests green**, `tsc --noEmit` clean,
  `npm run route:parity` → PARITY OK.
- Tests added: `tests/rate-limit.test.ts` (10 tests).

---

## In progress — Node settings content tabs (Wave 0.6) 🚧

Goal: replace the dead `SettingsTab` forms (cms / global-labels / about) with real
persistence matching `DashboardSettingController`.

### Done (not yet fully verified)
- `lib/site-labels.ts` — **generated** EN+BN label tree (29 sections each) from
  `eskoofy-laravel-app/lang/{en,bn}/site_frontend.php`; generators added
  (`scripts/dump-site-labels.php` + `scripts/build-site-labels.mjs`, reproducible).
- `lib/site-ui.ts` — `deepMerge`, `flattenSiteLabels`, `listPaths`,
  `normalizeListValues`, `pruneIdentical`, `isSimpleList`, `getSiteUi()` resolver
  (mirror of `SiteFrontend::merged()` / `WebsiteContent`).
- `lib/settings-content.ts` — persistence for `website_settings` (CMS), `site-ui`
  labels, and `about` content (JSON columns).
- `app/(dashboard)/dashboard/settings-actions.ts` — server actions
  `saveCmsSettings`, `saveGlobalLabels`, `saveAboutSettings`.
- `components/dashboard/SettingsContentTabs.tsx` — real forms for all three tabs.
- `app/(dashboard)/dashboard/[...segments]/page.tsx` — wired to the new components.
- Consumers wired to read overrides: `components/site/CMSContentSections.tsx`,
  `app/(dashboard)/dashboard/page.tsx` (setup checklist).
- Removed the old dead `SettingsTab` from `components/dashboard/ExtraScreens.tsx`.

### NOT finished / known issues
- **3 failing tests** in new `tests/settings-content.test.ts` (44/47 passing) —
  Node test suite currently RED:
  1. `isSimpleList` rejects arrays of *objects*: my port only rejects nested
     non-empty arrays, but PHP treats an element that is an associative array
     (`{...}`) as non-simple. Must also reject plain-object elements.
  2. `listPaths` — same root cause (depends on isSimpleList).
  3. `mergeLabelOverrides` list conversion test — uses `footer.links`, which is
     NOT a list path in the real tree; test assumption wrong, need to use a real
     simple-list path (or adjust the helper).
- Public **About page** (`app/(site)/about/*`) not yet wired to consume the
  stored about tree — need to check how it renders and apply `getSiteUi`/about tree.
- Full Node suite + `tsc` not re-run after the settings changes.
- No end-to-end verification that a saved label renders on the public site.

### Next steps for this item
1. Fix `isSimpleList` object handling; correct the 2 affected tests.
2. Verify/consume the `about` tree on the public About page.
3. Re-run `npm test`, `tsc --noEmit`, `npm run route:parity`.

---

## Not started

- Wave 0.7  Node: gateway initiate/callback/webhook handlers (`lib/payments`).
- Wave 0.8–0.9 Node: broken print buttons (RSC onClick), CMS editor save + live
  preview iframe (`CmsScreen.tsx`).
- Wave 0.10–0.12 Node: public `?lang=` wiring, cron endpoint for dispatchers,
  real `/dashboard/onboarding`.
- Wave 1.2  WP: year-end student promotion screen.
- Wave 1.3  Gateway webhooks/callbacks in WP.
- Wave 1.4  Node: SMS carrier delivery drivers.
- Wave 1.5  WP + Node: push notifications (FCM).
- Wave 1.6  WP + Node: scheduled jobs (db backup, scheduled notifications, cron).
- Wave 1.7  WP + Node: recurring fees / auto-invoice generation.
- Wave 1.8  PDF file generation in php-app, WP, Node.
- Wave 1.9  Deferred-job/queue equivalent in php-app, WP, Node.
- Wave 1.10 php-app: activity log writes (currently read-only).
- Wave 1.11 php-app: full PWA manifest (icons, description, orientation, categories).
- Wave 1.12–1.13 WP: public print stylesheets + print views; grouped EN+BN
  global-labels editor.
- Wave 2 Node+WP: exam publish semantics, ledger auto-posting, payroll generation,
  bulk attendance parity.
- Wave 3: regenerate feature matrices (xlsx), update workplan + Node docs
  (`PORTING-STATUS.md`, `NOT-IMPLEMENTED.md`, `MISMATCH-REPORT.md`) + parity doc + README.

---

## Working baseline (pre-change, from the plan)

- Laravel (reference): 1079 tests / 2839 assertions.
- php-app: 351 tests / 943 assertions → now 375 / 1008.
- Branding: 129 tests / 365 assertions → now 137 / 413.
- Node: 113 tests → now 123 (+ 47 new settings tests, 3 failing).
- WP theme: no suite until now; added `tests/throttle-test.php`.
- Nothing committed during this task yet; a pre-existing working-tree change to
  `docs/notes/self-notes-products.md` must be preserved.