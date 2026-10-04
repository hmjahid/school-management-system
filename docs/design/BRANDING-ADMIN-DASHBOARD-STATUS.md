# Eskoofy Branding Website — Admin Dashboard + Product/Variant UX: Build Status

Checked against the code on **2026-10-04**. Source of truth for the plan:
`docs/design/BRANDING-ADMIN-DASHBOARD-UX.md` (see its §11 file list and §12 phases).

Legend: `[x]` done · `[~]` partial · `[ ]` not started.

---

## Phases (§12)

- [x] **Phase 0 — Foundations** (tokens/css, Catalog, Nav, Cache, VariantResolver, schema migration, backfill, vendored charts, admin.js skeleton). Gate met: 328 tests green; backfill idempotent + reports reconciliation vs `payments.variant`; CSP test passes.
- [x] **Phase 1 — Fix the data** (B1–B5, B12 live, plus B14–B20 found during Docker verification). Gate met, with one caveat noted below.
- [ ] **Phase 2 — Variant showcase, public side** (matrix component, `/products`, home 4-card grid, pricing, `/choose` market question, `/compare`, copy sweep).
- [ ] **Phase 3 — Shell + dark mode + command palette** (admin.php rewrite, token sweep, topbar, sidebar, Ctrl+K).
- [~] **Phase 4 — Dashboard widgets** — read models (`Analytics/*` services) exist, but W1–W21 widgets, alert strip, skeletons/empty states are **not built**.
- [ ] **Phase 5 — Move email off the request path** (Cron\ExpiringNotifier, routes/cron.php, crontab docs, close B6/B7).
- [ ] **Phase 6 — Docs & polish** (AGENTS.md updates, README screenshots, `/admin/analytics`, perf budget, a11y sweep).

---

## Bugs: fixed vs outstanding

Fixed and regression-tested live in the MariaDB container (see doc §7.3.1):

- [x] B1 KPI == expiring list predicate
- [x] B2 derived `expired` status from `expires_at`
- [x] B3 all four products rendered incl. `node` colour
- [x] B4 MRR vs collected reconciliation shown explicitly
- [x] B5 dense, zero-filled revenue buckets
- [x] B12 MRR/ARR exclude `plans.active = 0`
- [x] B14 migration columns nullable (`NULL` = unresolved), verified on a simulated old install
- [x] B15 BDT payments normalised to USD in every money aggregate
- [x] B16 `GROUP BY derived_status` (was binding to physical column → "expired" never emitted)
- [x] B17 `countLicensesByPeriod` placeholder/param parity
- [x] B18 `paymentsQuery` builds params exactly once
- [x] B19 MRR scoped to product/variant filters
- [x] B20 variant written at runtime (Register / License / Checkout / LicenseManager)

Outstanding (deliberately deferred / not in Phase 0–1 scope):

- [ ] B6 `LicenseReminderService::notifyExpiring()` still called on every dashboard view (Phase 5)
- [ ] B7 dashboard caching via `App\Services\Cache` wired (Phase 3/4)
- [ ] B8 hardcoded `localhost` product URLs in production UI (Phase 4/6)
- [ ] B9 `<html lang>` via I18n (Phase 3)
- [ ] B10 **partial**: node plans seeded + 4th product, but public home/pricing/nav cards still not updated (Phase 2)
- [ ] B11 `/products/node` recommender CTA link (Phase 2)
- [ ] B13 CSP gate met via `tests/Unit/Core/SecurityHeadersCspTest.php` (done; listed here for completeness)

---

## §11 File list — New

| File | Status |
|---|---|
| `app/Services/VariantResolver.php` | [x] |
| `app/Services/Catalog.php` | [x] |
| `app/Services/Nav.php` | [x] (unused until Phase 3) |
| `app/Services/Cache.php` | [x] (unused until Phase 3/4) |
| `app/Services/Analytics/DateRange.php` | [x] |
| `app/Services/Analytics/KpiService.php` | [ ] |
| `app/Services/Analytics/RevenueService.php` | [x] |
| `app/Services/Analytics/LicenseService.php` | [x] |
| `app/Services/Analytics/TrafficService.php` | [ ] |
| `app/Services/Analytics/MatrixService.php` | [ ] |
| `app/Services/Analytics/GatewayService.php` | [ ] |
| `app/Services/Analytics/ActivityFeed.php` | [ ] |
| `app/Services/Analytics/ChartPayload.php` | [ ] |
| `app/Services/Cron/ExpiringNotifier.php` | [ ] |
| `database/migrations/2026_10_04_add_variants.sql` | [x] |
| `database/backfill_variants.php` | [x] |
| `public/js/admin.js` | [x] (skeleton only; not loaded) |
| `public/js/charts.js` | [x] (not loaded) |
| `public/vendor/charts/chart.umd.min.js` | [x] |
| `public/vendor/charts/apexcharts.min.js` | [x] |
| `views/partials/product_variant_matrix.php` | [ ] |
| `views/admin/partials/{stat_card,panel,chart,data_table,status_pill,empty_state,skeleton,variant_chip,product_pill,range_picker,legend_matrix,command_palette}.php` | [ ] all 12 |
| `views/site/products_index.php` | [ ] |
| `views/admin/analytics.php` | [ ] |
| `public/css/admin.css` | [x] (tokens + primitives; not linked) |

## §11 File list — Modified

| File | Status |
|---|---|
| `database/schema.sql` (+2 nullable variant cols, node plans, copy) | [x] |
| `views/layouts/admin.php` (shell rewrite) | [ ] — still legacy shell |
| `views/admin/dashboard.php` (rewrite §6.2) | [x] — Phase 1 correctness; visuals deferred |
| `views/admin/{licenses,payments,customers,subscriptions,visitors,activities,settings,gateways,backups,cloud-backup}.php` (token sweep) | [ ] all |
| `views/admin/settings.php` ("Products & variants" group) | [ ] |
| `views/site/{home,pricing,compare,choose,choose_result,features}.php` | [ ] all |
| `views/site/products/{app,php,theme,node}.php` (variant strip) | [ ] |
| `views/layouts/main.php` (nav + /products + node + badges) | [ ] |
| `app/Controllers/Admin/DashboardController.php` | [x] (rewritten; thin + Analytics services) |
| `app/Controllers/Admin/AnalyticsController.php` | [ ] |
| `app/Controllers/Site/{Home,Pricing,Choose,ChooseResult}Controller.php` | [~] only `HomeController.php` + `ChooseController.php` exist (product route present); no variant logic |
| `app/Controllers/{Auth/Register,Site/Checkout,Account/Renewal,Admin/License}Controller.php` — set variant | [x] — Register/Checkout/License patched this session; RenewalController already wrote `variant => $isBd ? 'bd' : 'int'` (verified) |
| `app/Services/{ProductRecommender,LicenseReminderService}.php` | [ ] — untouched; ProductRecommender still uses the old conflated "4 products/variants" wording (copy sweep, Phase 2); LicenseReminderService stays in the request path until Phase 5 (B6) |
| `app/Models/{License,Customer,Payment,Visitor}.php` | [ ] |
| `routes/web.php` (+/products, /admin/analytics, cron, palette) | [~] only existing `/products/{slug}` route; no new ones |
| `routes/cron.php` | [ ] |
| `lang/{en,bn}.php` (+~220 keys, renames §3.4) | [~] catalog + variant keys added; full parity sweep not run |
| `config/{app,licensing}.php` | [~] `config/app.php` has `variant` default; licensing untouched |
| `public/manifest.json` (four deployments) | [ ] |
| `AGENTS.md` (variant rules + dashboard architecture) | [ ] |
| `docs/design/BRANDING-SITE-IMPROVEMENTS.md` (cross-ref) | [ ] |

---

## Tests

- [x] `composer test` — **328 tests / 1266 assertions** green (host, DB-free).
- [x] Live verification in `./docker/dev.sh up website`: 21/21 dashboard range×filter combos clean; backfill idempotent + reconciliation warn; upgrade path proven on a simulated old install.
- [ ] Gate-test names in the doc (`LicenseStatusTest`, `DashboardConsistencyTest`, `NoVariantConfusionTest`, `CommandPaletteTest`) do **not** exist as named; Phase 0/1 coverage lives in `tests/Unit/Services/…` and `tests/Unit/Services/Analytics/…`.
- [ ] Stronger derived-status test still worth writing: diff emitted SQL against a seeded matrix of fixtures (would have caught B16).

## Known caveat

- The DB currently holds synthetic edge-case seed data (mixed currencies, expired/suspended/cancelled licenses, a soft-deleted row, retired plan, payment gaps) used for verification. Reset to clean seed before shipping.