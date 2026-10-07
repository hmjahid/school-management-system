# Eskoofy Branding Website — Admin Dashboard + Product/Variant UX: Build Status

Checked against the code on **2026-10-04**. Source of truth for the plan:
`docs/design/BRANDING-ADMIN-DASHBOARD-UX.md` (see its §11 file list and §12 phases).

Legend: `[x]` done · `[~]` partial · `[ ]` not started.

---

## Phases (§12)

- [x] **Phase 0 — Foundations** (tokens/css, Catalog, Nav, Cache, VariantResolver, schema migration, backfill, vendored charts, admin.js skeleton). Gate met: 328 tests green; backfill idempotent + reports reconciliation vs `payments.variant`; CSP test passes.
- [x] **Phase 1 — Fix the data** (B1–B5, B12 live, plus B14–B20 found during Docker verification). Gate met, with one caveat noted below.
- [x] **Phase 2 — Variant showcase, public side** (matrix component, `/products`, home 4-card grid, pricing, `/choose` market question, `/compare`, copy sweep).
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
- [x] B10 node plans seeded + 4th product wired through public home/pricing/products/compare (Phase 2)
- [x] B11 `/products/node` recommender CTA link (Phase 2)
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
| `views/partials/product_variant_matrix.php` | [x] (full/compact/admin modes) |
| `views/partials/product_variant_switch.php` | [x] (single-product market-build switcher) |
| `app/Services/ProductMatrix.php` | [x] |
| `views/admin/partials/{stat_card,panel,chart,data_table,status_pill,empty_state,skeleton,variant_chip,product_pill,range_picker,legend_matrix,command_palette}.php` | [ ] all 12 |
| `views/site/products_index.php` | [x] |
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
| `views/site/{home,pricing,compare,choose,choose_result,features}.php` | [x] home/pricing/compare/choose/choose_result; `features` unchanged (no product/variant claims) |
| `views/site/products/{app,php,theme,node}.php` (variant strip) | [x] switcher + matrix + variant-driven prices |
| `views/layouts/main.php` (nav + /products + node + badges) | [~] matrix + switcher CSS and /products nav done; command palette is Phase 3 |
| `app/Controllers/Admin/DashboardController.php` | [x] (rewritten; thin + Analytics services) |
| `app/Controllers/Admin/AnalyticsController.php` | [ ] |
| `app/Controllers/Site/{Home,Choose}Controller.php` | [x] `/products` route + `productVariant` wiring; `ChooseController::RULES`/`OPTIONS` are the single source of the six quiz questions |
| `app/Controllers/{Auth/Register,Site/Checkout,Account/Renewal,Admin/License}Controller.php` — set variant | [x] — Register/Checkout/License patched this session; RenewalController already wrote `variant => $isBd ? 'bd' : 'int'` (verified) |
| `app/Services/ProductRecommender.php` | [x] — returns `product` + `variant` as separate axes; conflated wording gone (regression-tested in `I18nKeyParityTest`) |
| `app/Services/LicenseReminderService.php` | [ ] — stays in the request path until Phase 5 (B6) |
| `app/Models/{License,Customer,Payment,Visitor}.php` | [ ] |
| `routes/web.php` (+/products, /admin/analytics, cron, palette) | [~] `/products` added before `/products/{slug}` (order regression-tested); /admin/analytics + cron are Phase 3/5 |
| `routes/cron.php` | [ ] |
| `lang/{en,bn}.php` (+~220 keys, renames §3.4) | [x] both directions of key parity, placeholder parity, duplicate keys and terminology now regression-tested by `tests/Unit/I18n/I18nKeyParityTest.php` |
| `config/{app,licensing}.php` | [~] `config/app.php` has `variant` default; licensing untouched |
| `public/manifest.json` (four deployments) | [ ] |
| `AGENTS.md` (variant rules + dashboard architecture) | [ ] |
| `docs/design/BRANDING-SITE-IMPROVEMENTS.md` (cross-ref) | [ ] |

---

## Tests

- [x] `composer test` — **407 tests / 1664 assertions** green (host, DB-free). Run `composer test`, not `./vendor/bin/phpunit` — the bare binary emits a harmless `No configuration file found at ~/.esmtprc` warning.
- [x] Live verification in `./docker/dev.sh up website`: 21/21 dashboard range×filter combos clean; backfill idempotent + reconciliation warn; upgrade path proven on a simulated old install.
- [x] Phase 2 coverage added: `tests/Unit/Services/ProductMatrixTest.php` (shape, prices, "not offered" rule, links), `tests/Unit/Views/ProductVariantMatrixViewTest.php` (all three modes, warnings promoted to exceptions), `tests/Unit/Views/SitePagesRenderTest.php` (every page Phase 2 touched, rendered with the controllers' real data shapes), `tests/Unit/Core/SiteRoutesDispatchTest.php` (real routes + real controllers against `FakeDatabase`), `tests/Unit/I18n/I18nKeyParityTest.php` (two-way key parity, duplicate keys, placeholder parity, terminology).
- [ ] Gate-test names in the doc (`LicenseStatusTest`, `DashboardConsistencyTest`, `CommandPaletteTest`) do **not** exist as named. `NoVariantConfusionTest` is now covered in substance by the terminology assertion in `I18nKeyParityTest` plus the two-axis assertions in `ProductMatrixTest`; the other two remain Phase 3/4 work.
- [ ] Stronger derived-status test still worth writing: diff emitted SQL against a seeded matrix of fixtures (would have caught B16).

## Phase 2 — what landed

| Surface | File | Notes |
|---|---|---|
| Matrix service | `app/Services/ProductMatrix.php` | 4 rows × 2 columns; one USD price list, BDT derived and flagged; a product with no active plans is an explicit `offered => false` cell pointing at `/custom-order?product=…&variant=…` |
| Matrix component | `views/partials/product_variant_matrix.php` | `full` / `compact` / `admin`; products get a rounded brand pill, variants a square hatched chip; `''` suppresses the heading, `false` the legend/footnote |
| Product switcher | `views/partials/product_variant_switch.php` | Real `?variant=` links, so it works without JS and is shareable |
| Catalog page | `views/site/products_index.php` + `/products` | Registered before `/products/{slug}`; order is regression-tested |
| Homepage | `views/site/home.php` | Four cards driven by `Catalog::keys()`, each with its variant badge strip |
| Pricing | `views/site/pricing.php` | Four product rows over one `plans` source; month/year and market-build toggles ship **both** currencies in the markup, so the swap is an attribute read and the two can never disagree |
| Chooser | `views/site/choose.php`, `choose_result.php`, `ChooseController`, `ProductRecommender` | Sixth question is the market build; the result returns `product` **and** `variant` and rings that one cell of the compact matrix |
| Compare | `views/site/compare.php` | Eskoofy's own matrix leads; the competitor table answers "how are we better" second |
| Product pages | `views/site/products/{app,php,theme,node}.php` | Prices follow `productVariant` instead of the visitor's country, so a BD visitor can inspect the international build; `node` gained the pricing block its seeded plans were already advertising |
| Copy | `lang/{en,bn}.php` | Matrix, products page, chooser and node keys; the conflated "products/variants" phrasing is gone and now fails the build if reintroduced |

Two decisions worth remembering:

- **Node.js is offered in both variants.** `database/schema.sql` seeds all four products with monthly + yearly plans and no `variant` column on `plans`, so every one of the eight cells is purchasable. "Not offered" is a *rendered state* for when a product has no active plans, not the current situation.
- **A product page never reads `$variant`.** `View::share('variant', …)` already publishes the site's own build profile; the per-page value is `productVariant` so it cannot shadow the layout's language/currency context.

## Known caveat

- The DB currently holds synthetic edge-case seed data (mixed currencies, expired/suspended/cancelled licenses, a soft-deleted row, retired plan, payment gaps) used for verification. Reset to clean seed before shipping.

## Shipped — enterprise admin dashboard (Phases 3 + 4 + 6)

Landing work checked against `docs/prompts/branding-admin-enterprise-dashboard-prompt.md` (all six sections) on 2026-10-08. `composer test`: **456 tests / 1883 assertions green**; `php -l` clean across the edited tree.

**§1 Nav IA — single source of truth.** `app/Services/Nav.php` drives the sidebar *and* the Ctrl+K palette. Regrouped to 6 groups / 24 items (`Dashboard`, `Customers & inbox`, `Storefront`, `Content`, `Vigilance`, `Account`). Badges capped at `99+` at the data level (`Nav::items()`); the expiring-licenses dot is `License::countExpiringSoon(30)`-driven. Sidebar/palette group labels move into the PHP labels, not into `lang/` slugs.

**§2 Shell polish.** Fixed-width 276px sidebar with right border in both themes; `data-nav-label` tooltips (topbar + sidebar) live in the layout, styled in `admin.css` with a reduced-motion guard.

**§3 Sidebar UX.** `setUpSidebarScrollbar()` in `public/js/admin.js`: 6px overlay scroller, 28px-min drag thumb, 600ms idle fade, ResizeObserver + rAF update, coarse-pointer skip; wired into boot and the rail toggle (`window.ESKSidebarScrollbar.update()`). Rail mode adds `data-esk-scroll` tooltips via `::after` and repositions the attention dot.

**§4 Dashboard.** Rebuilt `views/admin/dashboard.php`:
- Hero KPI grid (`.esk-stat--hero`, revenue tile spans 2, compact `money_short()` feet).
- Trending row: `RevenueService::topPlans()` + `fastestGrowingGateway()` and `TrafficService::topCountries()` — each fetches the current window and the prior one and emits `delta_pct ∈ [0,100] | null` (`null` = no prior data, never a fake 0.0). Cached in the `'revenue'`/`'traffic'` groups (TTL 300/600) keyed on range + filters.
- ARR gauge (radial) driven by a new `analytics.arr_target` setting (numeric, validated `SettingsController`, `Settings::$defaults`).
- Fresh-install onboarding checklist swaps out the empty-chart sea.
- Section re-order (MRR into orders, activity into the heatmap row) + panel footer actions (Manage → / Map → / Configure →).
- `num_short()` / `money_short()` in `helpers.php`; `charts.js` compacts axis ticks for `currency`/`compact` specs while tooltips stay full precision.

**§5 Tokens + micro-interactions.** Panel hover lift (`.esk-panel`, fine-pointer only, lands flush into the existing `translateY` active state), hero/trending/onboarding styles ~ tokens `--motion-fast`/`--motion`; cache-bust bumped to `admin.css?v=8`, `admin.js?v=3`, `charts.js?v=3`.

**§6 Tests.** 13 new: `TrafficServiceTest` (top-countries delta carry / no-prior-fallback / empty), `RevenueServiceTest` (plan ranking + deltas, limit bound to current window only, new-plan degradation, gateway gainer / busy-when-no-growth / null), `AdminDashboardViewTest` (hero+trending render with valid chart specs, ARR gauge present-vs-degraded, fresh-install onboarding with the empty panels gone). `AggregateDatabaseStub` gained one-shot `onOnce()` rules — the current/prior windows are textually identical SQL (DateRange changes only bound params), so the current window is scripted as consumed-once and the prior falls through to the permanent rule.