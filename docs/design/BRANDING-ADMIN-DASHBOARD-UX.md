# Branding Website — Enterprise Admin Dashboard + Product/Variant Showcase

> Status: **proposed** · Owner: TBD · Version: 1.0 · Date: 2026-10-04
> Scope: `eskoofy-branding-website/` only (the marketing site + license server).
> Complements `docs/design/BRANDING-SITE-IMPROVEMENTS.md`, `docs/design/VARIANT-BLUEPRINT.md`,
> `docs/design/PAYMENT-MODEL.md`, `WORKPLAN.md`.
> **This plan does not touch the 4 school products** (`eskoofy-laravel-app`,
> `eskoofy-php-app`, `eskoofy-wp-theme`, `eskoofy-nodejs-app`). It changes how the
> branding site *presents* them and how the license server *reports* on them.

---

## 1. The problem, stated honestly

Two separate problems are bundled in this request. They must be solved in order,
because the second one is a **terminology bug that the first one will hard-code into
the UI** if we build dashboards first.

### 1.1 Problem A — the word "variant" means two different things

`bd` / `int` are the real variants. But the branding site calls the **four products**
"variants" everywhere:

| File | Current string | Should be |
|---|---|---|
| `lang/en.php:703` | `choose.result_title` → *"Your recommended variant"* | *"Your recommended product"* |
| `lang/en.php:727` | `choose.all_variants` → *"All four variants"* | *"All four products"* |
| `lang/en.php:734` | `custom_order.sub` → *"…on any Eskoofy variant?"* | *"…on any Eskoofy product?"* |
| `lang/en.php:721` | `choose.reason.javascript` → *"we recommend the Node.js variant"* | *"…the Node.js product"* |
| `lang/en.php:772` | `node_page.badge` → *"JavaScript variant"* | *"JavaScript product"* |
| `ProductRecommender.php:9-11` | *"the best-fit Eskoofy variant out of the four products/variants"* | *"product out of the four products"* |

Meanwhile the **real** variants are invisible on the public site. They exist only as
`payments.variant` (`CheckoutController.php:91`, `RenewalController.php:66`) and as an
implicit currency switch — `views/site/partials/services_addon.php` and the pricing view
both call `GatewayFactory::isBdCountry()` on `customers.country` and swap `$` → `৳`.

Result: a visitor reads "four variants" and thinks BD/INT are two of them. A buyer in
Dhaka sees `৳` prices and cannot tell whether that is a *different product* or the *same
product in a different market*. The site's own `AGENTS.md` even declares the website
`int`-only while the checkout code still branches on `bd`. **Nobody can be blamed for
being confused — the site taught them the wrong word.**

### 1.2 Problem B — the admin dashboard is a flat CRUD panel

`views/admin/dashboard.php` (346 lines) is good enough to prove the license server works.
It is not good enough to run a business from:

- **4 KPI tiles** with no sparklines, no prior-period comparison on 3 of the 4, no trend.
- **One 12-month bar chart** of revenue. Nothing else is charted.
- **No charts for the things that actually matter**: activation rate, renewal/churn,
  new-vs-renewal revenue, gateway mix, traffic→sale conversion, MRR movement.
- **Two `$closure`s that emit SVG by string concatenation** (`dashboard.php:12-45`,
  `:47-73`) living inside a view. No tooltips, no legend interaction, no dark mode,
  not reusable by `visitors.php` or `account/dashboard.php`, untestable.
- **Data-integrity bugs already shipped** (all confirmed by reading the SQL):
  - `DashboardController.php:32` — the `expiring_soon` KPI has **no `status` filter**,
    but the list directly beneath it (`:87`) filters `status = 'active'`. Suspended and
    cancelled licenses inflate the number and never appear in the list. The tile
    contradicts its own table.
  - `dashboard.php:53` — the donut's `'expired' => '#cbd5e1'` swatch is **dead code**.
    `updateStatus()` only ever writes `active|suspended|cancelled`; expiry is derived from
    `expires_at`, never stored. Meanwhile licenses that are `status='active'` but past
    `expires_at` land in the donut's **green "active"** bucket, so the donut and the
    `active_licenses` KPI (which correctly excludes them) disagree.
  - `dashboard.php:75` — `$productColors` has **no `node` key**, so the Node.js bar renders
    in the same blue as the Laravel app.
  - `DashboardController.php:39-40` — MRR/ARR come from `subscriptions ⋈ plans`, revenue
    comes from `payments`. **These two are not reconciled anywhere**, so the dashboard can
    show non-zero MRR against zero collected revenue with no explanation.
  - `DashboardController.php:47` — the 12-month window uses
    `DATE_SUB(DATE_FORMAT(NOW(),'%Y-%m-01'), INTERVAL 11 MONTH)` against a first-of-month
    cursor, which can **silently drop a partial month** at the left edge.
  - `DashboardController.php:23` — **`LicenseReminderService::notifyExpiring()` fires
    customer emails on every single dashboard page view.** An admin refreshing the page
    is a mail loop. Must move to cron.
  - **No caching.** 8 round-trips to MySQL on every dashboard load, one of them a
    15-subquery aggregate.
  - `layouts/admin.php:88-92` — the "Product dashboards" strip ships **hardcoded
    `localhost:8000/8051/8080/3000` fallbacks** into a production UI, and
    `layouts/admin.php:79` hardcodes `<html lang="en">`.
- **Node.js is a second-class citizen** — absent from the home grid (`home.php:92-129`
  renders 3 columns), absent from `pricing.php`, has **no `plans` rows** in
  `schema.sql`, no `nav.node` lang key, yet has a route (`/products/node`), a view, a CMS
  page (id 11), a recommender entry and an admin dashboard link.

---

## 2. Goals / non-goals

### Goals

- **G1** — An admin dashboard that a paid operator can run the business from: KPI tiles
  with sparklines + period-over-period deltas, ≥10 real charts, traffic→revenue funnel,
  MRR movement, renewal/churn, and drill-through from every chart to the filtered list.
- **G2** — **Zero taxonomy confusion.** `Product` = 4 deployment targets. `Variant` = 2
  market profiles (`bd`, `int`). The word "variant" is used for exactly one concept
  everywhere: code, UI copy, lang keys, admin, docs.
- **G3** — A **Product × Variant matrix** that shows all 8 combinations at a glance, in
  the admin *and* on the public site, so "4 products × 2 variants" is self-explanatory
  rather than something a visitor has to assemble in their head.
- **G4** — `bd` vs `int` becomes a **first-class, queryable, reportable dimension**:
  own column, own resolver, own filters, own charts.
- **G5** — Dark mode, date-range filtering, command palette, skeleton + empty states.
- **G6** — The existing data-integrity bugs are fixed and covered by tests.

### Non-goals

- No changes to the `/api/v1` license contract (`activate`/`validate`/`deactivate`/
  `status`/`ping`) — customers depend on it; frozen.
- No pricing changes. **USD stays canonical**; BDT remains derived at the current rate.
  We are *labelling* the split, not introducing a second price list.
- No school-management modules ported into the branding site (per its `AGENTS.md`).
- No CSS build step. Tailwind stays CDN-only; chart libs are vendored UMD.
- Not touching `eskoofy-laravel-app` / `-php-app` / `-wp-theme` / `-nodejs-app`.

---

## 3. The taxonomy fix (do this first — G2)

### 3.1 Canonical definitions

| Axis | Values | Meaning | Stored where |
|---|---|---|---|
| **Product** | `app`, `php`, `theme`, `node` | *What you deploy.* Different runtime, different hosting. | `plans.product`, `licenses.product`, `payments` (via plan) |
| **Variant** | `bd`, `int` | *Which market build.* Same code, different config profile: language, currency, ministry links, gateways. | **NEW** `customers.variant`, **NEW** `licenses.variant`, existing `payments.variant` |
| **Term** | `monthly`, `yearly` | Billing period. | `plans.period` |
| **Add-on** | `deployment`, `care` | Optional services. | `licenses.metadata.addons` |

### 3.2 The naming rule (non-negotiable)

> **`Product`** = one of the four. **`Variant`** = `bd` or `int`, never anything else.
> The words *version*, *edition*, *flavour*, *flavor*, *tier* are banned as synonyms for
> either. *Tier* is reserved for plan price levels if ever needed.

### 3.3 Single source of truth: `App\Services\VariantResolver`

One service owns the concept. Nothing else branches on it.

```php
final class VariantResolver
{
    public const BD  = 'bd';
    public const INT = 'int';

    /** @return list<string> */           public static function all(): array;      // ['bd','int']
    public static function isValid(mixed $v): bool;
    public static function label(string $v, string $locale = 'en'): string;        // 'Bangladesh (BD)' / 'International'

    // Resolution order — first non-null wins:
    //   1. explicit column (customers.variant / licenses.variant)
    //   2. latest payments.variant for that customer
    //   3. GatewayFactory::isBdCountry($customer->country)
    //   4. 'int'
    public static function forCustomer(array $customer): string;

    // Snapshot at issue time — immutable, so historical reports never shift.
    public static function forNewLicense(array $customer): string;

    // Display only. Prices are USD-canonical (schema.sql comment); ৳ is derived.
    public static function toBdt(float $usd): float;
    public static function displayAmount(float $usd, string $variant): array;     // ['amount'=>..,'symbol'=>'৳','derived'=>bool]
}
```

`licenses.variant` is a **snapshot**, deliberately denormalised. If a Bangladeshi
customer later moves and becomes `int`, last year's BD revenue must not silently
re-bucket. `payments.variant` is already a snapshot and stays authoritative for
revenue; `licenses.variant` is authoritative for license counts. The dashboard shows
MRR from subscriptions and collected revenue from payments, and **reconciles them
explicitly** (fixes the §1.2 bug).

### 3.4 Copy migration (lang keys)

`lang/en.php` + `lang/bn.php` — both files, both directions, keeping the 727-key parity
invariant intact. Renames must keep a BC alias so nothing 500s mid-deploy:

| Old key | New key | en value |
|---|---|---|
| `choose.result_title` | `choose.result_title` | "Your recommended product" |
| `choose.all_variants` | `choose.all_products` | "All four products" |
| `choose.reason.javascript` | *(value only)* | "You preferred JavaScript, so we recommend the Node.js product." |
| `node_page.badge` | *(value only)* | "JavaScript product" |

New keys: `variants.label`, `variants.bd`, `variants.int`, `variants.bd_short`,
`variants.int_short`, `variants.bd_desc`, `variants.int_desc`, `products.label`,
`matrix.title`, `matrix.sub`, `matrix.legend`, `matrix.cell_empty`, plus ~40 admin keys
(§6.5). Add a `tests/Unit/I18n/I18nKeyParityTest.php` assertion so `en`/`bn` can never
drift again.

---

## 4. Product × Variant showcase — the pattern

This is the answer to "showcase bd/int and the 4 products clearly without confusion".
It is **one component, used in five places**, so the mental model is learned once.

### 4.1 The component: `views/partials/product_variant_matrix.php`

A responsive **4-row × 2-column grid**. Rows are products (with product brand colour +
icon). Columns are variants (with a distinct chip treatment). Every cell is a link.

```
                        ┌─── BANGladesh (BD) ───┐ ┌─ INTERNATIONAL (INT) ─┐
                        │ 🇧🇩 ৳ · bKash/Rocket/Nagad│ │ 🌐 $ · Stripe/PayPal   │
┌───────────────────────┼────────────────────────┼─────────────────────────┤
│ ▣ Eskoofy School App  │  142 licenses           │  38 licenses            │
│   Laravel 12 · own    │  ৳1,842,000 · 31 active │  $4,560 · 12 active     │
│   server / VPS        │  ▸ View →               │  ▸ View →               │
├───────────────────────┼────────────────────────┼─────────────────────────┤
│ ▣ Eskoofy School PHP  │  96 · ৳980,000          │  4 · $120               │
│   raw PHP · shared    │  ▸ View →               │  ▸ View →               │
├───────────────────────┼────────────────────────┼─────────────────────────┤
│ ▣ Eskoofy WP Theme    │  210 · ৳2,140,000      │  61 · $5,490            │
├───────────────────────┼────────────────────────┼─────────────────────────┤
│ ▣ Eskoofy Node.js     │  —  not offered yet     │  7 · $840  (custom order)│
└───────────────────────┴────────────────────────┴─────────────────────────┘
```

Rules that make it unambiguous:

1. **Axis labels are always present** — column headers literally say "Variant", row
   gutter says "Product". A one-line legend sits above: *"Product = what you deploy.
   Variant = which market build. Same product, different configuration."*
2. **A "not offered" cell is a first-class state**, not a blank. Node × BD renders an
   explicit `—` + `variant.matrix.cell_empty` copy + a "Request this combination" CTA.
   This kills the current silent-blank ambiguity.
3. **Product and variant are visually orthogonal** — products get a rounded pill in the
   product's brand colour; variants get a square-corner chip with a diagonal hatch.
   You can tell at a glance which axis you're reading.
4. **One currency per column**, derived from USD at the live rate, with an explicit
   footnote: `৳ derived from $ at 110.00`. Nobody thinks there are two price lists.
5. **Empty cells and zero cells differ** — `0` renders as `0` with muted styling;
   "not offered" renders as `—`. Never conflate them.

### 4.2 Where it goes

| Surface | Mode | Data source | Purpose |
|---|---|---|---|
| `/admin` (new widget) | full, with MRR + drill-through | live queries | operator oversight |
| `/products` (**new page**) | full, public, price-forward | `plans` + settings | canonical explainer; target of `/products/*` breadcrumb |
| `/pricing` | rows = products, variant toggle | `plans` | replaces the 3-section layout |
| `/compare` | as a leading section | `plans` | Eskoofy's own matrix before the competitor table |
| `/choose` result | compact 4×2, recommended cell ringed | `ProductRecommender` | quiz output |
| `views/site/home.php` | inline badge strip per product card | static + `plans` | fix the missing 4th card |

---

## 5. Design system

### 5.1 Tokens

Extends the existing Tailwind CDN palette. New tokens go in `views/layouts/admin.php`'s
`<style>` block as CSS custom properties (no build step), so both light and dark themes
read from one source.

```css
:root{
  --brand:#2563eb;                 /* from appearance.brand_color */
  --bg:#f1f5f9; --surface:#fff; --surface-2:#f8fafc;
  --border:#e2e8f0; --border-strong:#cbd5e1;
  --text:#0f172a; --text-muted:#64748b; --text-subtle:#94a3b8;
  --success:#16a34a; --warning:#f59e0b; --danger:#ef4444; --info:#0ea5e9;
  /* product brand colours — one per product, used by matrix + charts + nav */
  --p-app:#4f46e5; --p-php:#0284c7; --p-theme:#7c3aed; --p-node:#059669;
  /* variant accents */
  --v-bd:#16a34a; --v-int:#2563eb;
  --radius:14px; --radius-sm:10px;
  --shadow-1:0 1px 2px rgb(15 23 42/.06);
  --shadow-2:0 10px 30px -12px rgb(15 23 42/.18);
}
[data-theme=dark]{
  --bg:#0b1220; --surface:#111a2e; --surface-2:#16203a;
  --border:#1f2c4a; --border-strong:#2c3b60;
  --text:#e8eefb; --text-muted:#94a3b8; --text-subtle:#64748b;
  --shadow-1:0 1px 2px rgb(0 0 0/.4);
  --shadow-2:0 16px 40px -16px rgb(0 0 0/.7);
}
```

Applied via the Tailwind CDN's `tailwind.config` inline script:

```js
tailwind.config={theme:{extend:{colors:{
  surface:'var(--surface)','surface-2':'var(--surface-2)',
  ink:'var(--text)','ink-muted':'var(--text-muted)','ink-subtle':'var(--text-subtle)',
  edge:'var(--border)','edge-strong':'var(--border-strong)',
  app:'var(--p-app)','php':'var(--p-php)',theme:'var(--p-theme)',node:'var(--p-node)',
  vbd:'var(--v-bd)',vint:'var(--v-int)',
},borderRadius:{card:'var(--radius)','card-sm':'var(--radius-sm)'},
boxShadow:{card:'var(--shadow-1)','card-lg':'var(--shadow-2)'}}}}}
```

Because `body` gets `data-theme`, **every existing hardcoded `bg-white` /
`text-slate-800` / `border-slate-200` in the 30 admin views breaks in dark mode.** Phase 0
 (§8) is a mechanical token sweep across those views — it must land before dark mode
ships, or dark mode will look broken everywhere.

### 5.2 Component set (new `views/admin/partials/`)

| Component | File | Notes |
|---|---|---|
| `stat_card.php` | | label, value, delta chip, `<canvas>` sparkline, optional icon badge, drill-through href |
| `panel.php` | | title, subtitle, actions slot, body — the single card wrapper |
| `chart.php` | | lazy-loads a vendored bundle, renders `<canvas>`, passes JSON config, themed |
| `data_table.php` | | sticky header, zebra, numeric `tabular-nums`, status pill, row link, empty + skeleton states |
| `status_pill.php` | | one place for all status→colour mapping (fixes the scattered ternaries) |
| `empty_state.php` | | icon, headline, body, optional CTA |
| `skeleton.php` | | shimmer blocks matched to each panel's real layout |
| `variant_chip.php` | | the BD/INT square-hatch chip |
| `product_pill.php` | | the product-coloured rounded pill |
| `range_picker.php` | | global date-range control |
| `legend_matrix.php` | | the products-vs-variants explainer |

### 5.3 Charts — vendored, not CDN

Per the decision: **self-hosted Chart.js + ApexCharts**, UMD bundles committed under
`public/vendor/charts/`, loaded with `defer`. Rationale: zero build step, zero runtime
Composer (per the product's `AGENTS.md`), works offline/air-gapped, and needs **no CSP
change** (`SecurityHeadersMiddleware` already allowlists the Tailwind CDN — we add zero
new external origins).

| Chart | Library | Why |
|---|---|---|
| Revenue trend (line/area, gradient fill) | Chart.js | cheap, 12–24 points |
| New vs renewal revenue (stacked bar) | Chart.js | |
| Cumulative revenue (line on secondary axis) | Chart.js | |
| Activation / deactivation flow (dual line) | Chart.js | |
| MRR movement waterfall | Chart.js | floating bars `[start, end]` |
| Sparklines in KPI tiles | ApexCharts `sparkline` | purpose-built, no axes |
| Revenue by variant (donut) | ApexCharts `donut` | built-in dataLabels + legend |
| Product share (radial bar) | ApexCharts `radialBar` | |
| Gateway mix (horizontal bar) | ApexCharts `bar` `horizontal` | |
| Traffic→sale funnel | ApexCharts `funnel` | native funnel shape |
| Country split (donut) | ApexCharts `donut` | |
| Revenue heatmap (product × month) | ApexCharts `heatmap` | |

Rules: `maintainAspectRatio:false` inside a fixed-height wrapper; `responsive:true`;
`animation` on first paint only; **respect `prefers-reduced-motion`**;
re-theme on `data-theme` mutation via a `MutationObserver`; tooltips formatted by
currency + variant; every chart `role="img"` with an `aria-label` summary and a
`<table class="sr-only">` fallback for screen readers. Chart config is built in PHP
(`App\Services\ChartPayload`) and passed as JSON — never hand-written in the view.

---

## 6. Admin dashboard information architecture

### 6.1 App shell (`views/layouts/admin.php`, rewritten)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ ▣ Eskoofy Admin │ ⌘K Search…          [30d ▾] [All ▾] [🌙] [🔔3] [Admin ▾]   │  topbar 64px
├────────────┬─────────────────────────────────────────────────────────────────┤
│ OVERVIEW   │                                                                 │
│  Dashboard │                                                                 │
│ SALES      │   ┌─ content (max-w-[1600px], px-6, py-6) ──────────────────┐   │
│  Licenses  │                                                          │   │
│  Plans     │                                                          │   │
│  Payments  │                                                          │   │
│  …         │                                                          │   │
│ SYSTEM     │                                                          │   │
└────────────┴─────────────────────────────────────────────────────────────────┘
   264px, collapsible → 72px icon rail
```

New vs current:

- **Global search / command palette** affordance in the topbar (Ctrl+K, or `/`).
- **Date-range picker** (`views/admin/partials/range_picker.php`): presets
  `Today · 7d · 30d · 90d · 12mo · YTD · All time` + custom from/to. Persisted in
  session; applied to every widget; the active window is printed in each panel header
  so no chart is ambiguous.
- **Product filter** (`All · App · PHP · Theme · Node`) and **variant filter**
  (`All · BD · INT`) as dropdowns in the topbar. Filters compose and cross-link to
  `/admin/licenses?product=node&variant=bd`.
- **Dark-mode toggle** — `System / Light / Dark`, persisted to `localStorage`, applied
  as `data-theme` on `<html>` **before first paint** via a tiny inline script (no flash).
  Seeds from the existing `appearance.dark_default` setting.
- **Notification bell** with a dropdown: expiring licenses, pending payments, unread
  messages, failed backups. Replaces the current "unread message(s)" crammed into a KPI tile.
- Sidebar gains **collapsible icon rail**, **active-section indicator bar**, group
  collapse, and a keyboard-accessible `<nav>` with `aria-current="page"`.
- Remove the hardcoded `localhost` product-dashboard links from the *dashboard body*
  (they move into a proper widget, §6.3 W12); they should never render in production UI.
- `<html lang>` from `I18n::current()`; admin chrome strings move to lang keys.

### 6.2 Dashboard layout (top to bottom, `views/admin/dashboard.php` rewritten)

```
Row 0  [Alert strip]  only when something needs action (expiring / failed payment /
                       backup failure / new custom order). Dismissible, links to the fix.

Row 1  KPI row — 5 stat cards, each with sparkline + period-over-period delta
       ┌──────────┬──────────┬──────────┬──────────┬──────────┐
       │  MRR     │Revenue   │Active    │Customers │Renewal   │
       │          │(period)  │licenses  │(period)  │rate      │
       └──────────┴──────────┴──────────┴──────────┴──────────┘

Row 2  ┌─ Revenue trend (span 2) ────────────┐ ┌─ Revenue by variant ─┐
       │ area + cumulative line, range-aware  │ │ BD vs INT donut      │
       └──────────────────────────────────────┘ └──────────────────────┘

Row 3  ┌─ PRODUCT × VARIANT MATRIX (span 2) ────────────────────────────┐
       │  the §4.1 showcase widget, full width — the centrepiece         │
       └────────────────────────────────────────────────────────────────┘

Row 4  ┌─ New vs renewal revenue ─┐ ┌─ Licenses by status ─┐ ┌─ Product share ─┐
       │ stacked bar by month      │ │ donut (status fixed) │ │ radialBar        │
       └───────────────────────────┘ └──────────────────────┘ └─────────────────┘

Row 5  ┌─ Activation flow (span 2) ─┐ ┌─ Traffic → sale funnel ─┐
       │ activations vs deactivations │ │ visitors→product→cart→paid│
       └─────────────────────────────┘ └──────────────────────────┘

Row 6  ┌─ MRR movement waterfall (span 2) ─┐ ┌─ Gateway mix ─────────┐
       └───────────────────────────────────┘ └───────────────────────┘

Row 7  ┌─ Expiring soon (list) ─┐ ┌─ Renewal risk heatmap ─┐ ┌─ Country split ─┐
       └────────────────────────┘ └─────────────────────────┘ └─────────────────┘

Row 8  ┌─ Top customers by LTV (span 2) ─┐ ┌─ Activity feed (timeline) ─┐
       └─────────────────────────────────┘ └────────────────────────────┘

Row 9  ┌─ Recent payments ────────────────┐ ┌─ Recent licenses ──────────┐
```

### 6.3 Widget specification

| # | Widget | Chart | Data | Interactions |
|---|---|---|---|---|
| W1 | **MRR** | sparkline | `subscriptions ⋈ plans`, period-filtered | delta vs prior period, → `/admin/subscriptions` |
| W2 | **Revenue (period)** | sparkline | `payments WHERE status='paid' AND paid_at IN range` | delta + % , → `/admin/payments` |
| W3 | **Active licenses** | sparkline | `licenses` valid-at-now (see §7.3) | split BD/INT inline, → `/admin/licenses?status=active` |
| W4 | **Customers** | sparkline | `customers.created_at IN range` | new vs total, → `/admin/customers` |
| W5 | **Renewal rate** | sparkline | renewals ÷ licenses expiring in prior window | 30/90-day rolling, → `/admin/subscriptions` |
| W6 | **Revenue trend** | Chart.js area + cumulative line | `payments` grouped by day or month (auto by range) | range brush, hover crosshair, click month → filtered payments |
| W7 | **Revenue by variant** | ApexCharts donut | `payments.variant` | click slice → `/admin/payments?variant=bd` |
| W8 | **Product × Variant matrix** | component (§4.1) | `licenses` + `payments` per cell | cell click → filtered license list; "request" CTA → `/custom-order` |
| W9 | **New vs renewal revenue** | Chart.js stacked bar | `payments` join `subscriptions` to classify | toggle stack mode |
| W10 | **Licenses by status** | ApexCharts donut | `licenses` with **derived** expired (see §7.3) | click → filtered list |
| W11 | **Product share** | ApexCharts radialBar | `licenses` per product, BD/INT stacked | click → filter |
| W12 | **Product fleet status** | list | `products.dashboards.*` settings | replace localhost strip; show configured/unconfigured; optional HTTP health check (off by default, cached 5 min) |
| W13 | **Activation flow** | Chart.js dual line | `license_activations.activated_at` / `.deactivated_at` | activation rate = active ÷ max_activations |
| W14 | **Traffic → sale funnel** | ApexCharts funnel | `visitors.path` (`/products/*`, `/pricing`, `/checkout`) → `payments` | conversion % per stage |
| W15 | **MRR movement waterfall** | Chart.js floating bars | new + expansion − contraction − churn = ending MRR | reconciles against W1 (§7.3) |
| W16 | **Gateway mix** | ApexCharts horizontal bar | `payments.gateway` | click → `/admin/payments?gateway=x` |
| W17 | **Expiring soon** | urgency list | `licenses` expiring ≤30d, `status='active'` | 7d→red, 8–30d→amber; extend action inline |
| W18 | **Renewal-risk heatmap** | ApexCharts heatmap | product × next-6-months expiry counts | click cell → filtered list |
| W19 | **Country split** | ApexCharts donut | `visitors.country` + `customers.country` | BD share sanity-check vs `variant` |
| W20 | **Top customers by LTV** | table | `payments` grouped by customer | sortable, → `/admin/customers/{id}` |
| W21 | **Activity feed** | timeline | `activity_logs` | icon per action type, relative time |

### 6.4 Alerts strip (new)

Rendered only when actionable, one line each, severity-coloured, with a direct fix link:
licenses expiring ≤7d · pending payments awaiting approval (>24h) · failed cloud backups ·
new unread custom orders · gateway errors in `activity_logs` last 24h. Dismissed per-session.

### 6.5 i18n for the admin

Move all 30 admin views' hardcoded English to `lang/{en,bn}.php` under an `admin.*`
namespace (~180 keys). `layouts/admin.php` sets `<html lang="<?= I18n::current() ?>">` and
the sidebar nav groups become lang keys too. **Optional but recommended** — the BD admin
team reads Bengali, and the `bd`/`int` split is *more* meaningful when the BD-side
operator can read their own dashboard in `bn`.

---

## 7. Data layer

### 7.1 Schema migration

`database/schema.sql` is the schema of record (full import, no migration runner), so the
change is an additive `ALTER` block plus a new `database/migrations/` script for
in-place upgrades.

```sql
-- customers.variant — market profile, resolved at registration / first checkout
ALTER TABLE `customers` ADD COLUMN `variant` VARCHAR(8) NOT NULL DEFAULT 'int' AFTER `locale`;
ALTER TABLE `customers` ADD KEY `idx_variant` (`variant`);

-- licenses.variant — immutable snapshot taken at issue time
ALTER TABLE `licenses` ADD COLUMN `variant` VARCHAR(8) NOT NULL DEFAULT 'int' AFTER `product`;
ALTER TABLE `licenses` ADD KEY `idx_variant` (`variant`);

-- plans: NO variant column. Prices stay USD-canonical by design (schema.sql:373);
-- BD figures are derived at display time via VariantResolver::toBdt().
```

Plus `database/migrations/2026_10_04_add_variants.sql` for existing installs, and
`database/backfill_variants.php` (idempotent, CLI-only, batched in 1 000-row chunks):

```
1. customers.variant ← latest payments.variant for that customer, else
                        GatewayFactory::isBdCountry(country) ? 'bd' : 'int'
2. licenses.variant  ← parent customer.variant, else 'int'
3. Report counts per bucket + a list of ambiguous rows for manual review.
```

**Write paths that must now set `variant`:** `RegisterController`, `CheckoutController:91`
(already), `RenewalController:66` (already), `Admin\LicenseController::store` (new
customer creation path — currently sets `locale='en'` blindly, `:store()`).

### 7.2 New query layer

New `app/Services/Analytics/` — pure, testable, no view code:

| Class | Responsibility |
|---|---|
| `DateRange` | value object: `from`, `to`, `bucket` (auto `hour`/`day`/`week`/`month` by span), prior-period range, `label()` |
| `KpiService` | W1–W5 |
| `RevenueService` | W6, W7, W9, W15 — incl. the MRR reconciliation |
| `LicenseService` | W3, W10, W11, W13, W17, W18 — **derived-status** logic lives here |
| `TrafficService` | W14, W19 |
| `MatrixService` | W8 — the 8 cells |
| `GatewayService` | W16 |
| `ActivityFeed` | W21 |
| `ChartPayload` | builds the JSON config handed to `chart.php` |

Every method takes an optional `DateRange` + `['product' => ?string, 'variant' => ?string]`
filter. All filters push down to SQL — no post-filtering in PHP.

### 7.3 Bug fixes bundled in (G6)

| # | Bug | Fix |
|---|---|---|
| B1 | `expiring_soon` KPI lacks `status='active'` (`DashboardController:32`) | add the filter; KPI and list now agree |
| B2 | donut's `expired` swatch is dead; expired-but-`active` counted as active | derive status in SQL: `CASE WHEN status<>'active' THEN status WHEN expires_at IS NOT NULL AND expires_at<=NOW() THEN 'expired' ELSE 'active' END`. One canonical expression, reused by KPI, donut, matrix, license list. |
| B3 | `$productColors` missing `node` (`dashboard.php:75`) | move all colour/icon maps to `App\Services\Catalog::product()` — single source for the 4 products |
| B4 | MRR (subscriptions) vs revenue (payments) unreconciled | add W15 waterfall + an explicit "MRR vs collected" reconciliation line; the gap becomes visible instead of contradictory |
| B5 | revenue window drops a partial month (`:47`) | `DateRange` computes from `DATE_SUB(NOW(), INTERVAL n MONTH)` (rolling), then buckets in PHP — dense, no gaps, no dropped edge |
| B6 | dashboard sends customer emails on every page view (`:23`) | move `notifyExpiring()` to a cron entry (`routes/` cron block + documented `*/15 * * * *` crontab); dashboard reads a cached `expiring_notified_at` |
| B7 | no caching; 8 queries, one a 15-subquery aggregate | `App\Services\Cache` (file-based in `storage/cache/`, TTL per widget: KPIs 5 min, matrix 10 min, fleet status 5 min) + in-request memoisation; a `?refresh=1` bypass for admins. Collapses the dashboard to ~2 queries on a warm cache. |
| B8 | hardcoded `localhost` product URLs in production UI | W12 renders configured/unconfigured honestly; no localhost fallback |
| B9 | `<html lang="en">` hardcoded | `I18n::current()` |
| B10 | Node has no `plans` rows, is missing from home/pricing/nav | seed `node` monthly+yearly plans; add the 4th home card; add `pricing.node` + `nav.node` keys |
| B11 | `/products/node` recommender CTA is `/custom-order?product=node` | once plans exist, link to `/products/node` + `/checkout?plan=node-monthly`; keep the custom-order route as a secondary CTA |
| B12 | MRR ignores plan `active=0` | filter `plans.active = 1` in MRR/ARR |
| B13 | `securityheaders` CSP would need no change (good) | add a test asserting the vendored chart bundles load under the current CSP |

#### 7.3.1 Found during Docker verification (2026-10-04)

The unit suite passed while all four of these were live, because the stub database
answers on query *shape*, not on real result sets. They were only caught by seeding
mixed-currency/mixed-status rows into the MariaDB container and rendering
`/admin/dashboard`.

| # | Bug | Impact | Fix |
|---|---|---|---|
| B14 | `2026_10_04_add_variants.sql` added `variant NOT NULL DEFAULT 'int'` | every pre-existing Bangladeshi customer/license is stamped `int` on upgrade, and since `int` is itself valid the backfill **skips** them — permanent misreporting | add the columns as `NULL DEFAULT NULL`; `NULL` means "unresolved". Verified by simulating an old install in a scratch DB |
| B15 | `RevenueService` summed the raw `amount` column | `payments.currency` mixes BDT and USD, so BDT 144 + USD 45 was rendered as "$189", and compared against USD-canonical MRR | normalise to USD in SQL (`usdExpression()`), qualifying `currency` because `plans` also has one |
| B16 | `countsByStatus()` used `GROUP BY status` | MySQL/MariaDB binds that to the **physical** `licenses.status` column and discards the derived expression — "expired" was never emitted and the Active KPI read 8 instead of 5 | alias to `derived_status` so it cannot collide |
| B17 | `countLicensesByPeriod()` put the SQL literal `'l.deleted_at IS NULL'` in `$params`, and never added predicates for the product/variant filters it bound | unfiltered dashboard threw a fatal `Invalid parameter number`; any filter combination also threw | bind values only, in placeholder order, and emit a predicate per bound value |
| B18 | `RevenueService::paymentsQuery()` called `DateRange::where()` twice when a product filter joined `plans` | a second set of date params with no matching placeholders → fatal on every filtered view | decide join-first, then build WHERE and params exactly once |
| B19 | `reconcile()` scoped collected revenue but not MRR | every filtered view reported the same gap as the unfiltered one, comparing a slice against the whole | `mrr()` takes the same filters (product via `plans.product`, variant via the license snapshot) |
| B20 | nothing wrote `variant` at runtime — `RegisterController`, `LicenseController` and `LicenseManager` all omitted it | with nullable columns (B14) every newly registered customer and issued license stayed `NULL` and was reported as international forever; the backfill only helps historical rows | resolve at write time: `VariantResolver::forCustomer()` on customer insert, `forNewLicense()` on license insert (preferring the source payment's market) |

### 7.4 New public routes

| Route | Purpose |
|---|---|
| `GET /admin/analytics` | dedicated deep-dive analytics page (all widgets, full range); dashboard becomes the summary view |
| `GET /products` | **new** public Product × Variant explainer (matrix + full comparison) |
| `GET /products/{product}` | existing; add the variant badge strip + "other products" rail |
| `GET /pricing?variant=bd|int` | variant-aware pricing; `variant` filter persists through checkout |
| `GET /choose?market=bd|int` | quiz gains a **market** question; recommends a product **and** variant |

---

## 8. Public-site showcase changes

### 8.1 Home page (`views/site/home.php`)

- **3-column grid → 4** (adds Node). Equal card heights, consistent CTA placement.
- Each card gains a **variant badge strip**: `🇧🇩 BD` + `🌐 INT` chips, with
  "not offered" struck through where true.
- The current `home.products.recommended` ribbon sits on `php` ("Best for shared
  hosting") — keep, but make it a data-driven `settings` flag
  (`appearance.recommended_product`) rather than a hardcoded position.
- Add a compact matrix summary + link to `/products`.

### 8.2 Pricing (`views/site/pricing.php`)

- Replace the three duplicated product sections with **one table**: rows = products,
  columns = variant, cells = price + period toggle. Generated from `plans`, so a plan
  added in the admin appears automatically (fixes B10 permanently).
- Currency is driven by the selected variant, always footnoted as derived.
- Keep the FAQ block; add a "which product should I buy?" link → `/choose`.

### 8.3 Product chooser (`views/site/choose.php`, `choose_result.php`)

- Relabel every "variant" → "product" (§3.4).
- **Add a market question** (`Bangladesh` / `International` / `Not sure`) as the *first*
  question, and use it to (a) pre-select the currency, (b) annotate the result with the
  matching variant chips, (c) flag the one combination that is not offered.
- `ProductRecommender::recommend()` keeps its scoring; extend the return shape to
  `['product' => ..., 'variant' => ..., 'runnerUp' => ..., 'score' => ..., 'reason_key' => ...]`.
- Show the compact matrix with the winning cell ringed.
- Existing test `tests/Unit/Services/ProductRecommenderTest.php` must be extended, not
  replaced — the deterministic ordering assertions stay.

### 8.4 `/compare` and `/products/{product}`

- `/compare`: prepend the Eskoofy Product × Variant matrix above the competitor table,
  and fix the table's own wording so it never says "variant" for a product.
- `/products/{product}`: add the variant strip, the "other three products" rail, and a
  direct compare link to the same product in the other variant.

### 8.5 Copy consistency sweep

`rg` the whole product for products-called-variants and fix every hit in
`lang/en.php`, `lang/bn.php`, `views/`, `app/`, and the CMS seed rows in
`database/schema.sql` (post seed says *"three deployments"* — now four). Add a
`tests/Unit/I18n/NoVariantConfusionTest.php` that greps the rendered marketing pages for
the banned phrasings (`four variants`, `Node.js variant`, `recommended variant`) so the
confusion cannot come back.

---

## 9. Command palette (Ctrl+K)

`public/js/admin.js` + `views/admin/partials/command_palette.php`.

- Opens on `Ctrl/Cmd+K` or `/`; closes on `Esc` or outside click; ↑/↓ navigate;
  `Enter` runs; grouped results (Navigate · Create · Jump to record · Actions).
- **Navigate** — every admin route from a single `App\Services\Nav::all()` array that
  also powers the sidebar (removes the duplicated nav definition in `layouts/admin.php`).
- **Create** — Issue license · New plan · New post · New category · New page · New customer.
- **Jump to record** — debounced search (200 ms, min 2 chars) against
  `customers` / `licenses` / `payments`, results capped at 8 per group.
- **Actions** — Clear cache · Create backup · Run cloud backup · Toggle maintenance.
  Every action is `POST` + CSRF + a confirm step.
- Full keyboard/ARIA combobox semantics (`role="combobox"`, `aria-expanded`,
  `aria-activedescendant`, live region for result counts).

---

## 10. Command palette — records & filters

Every chart click and matrix cell routes with query params that the target list view
already understands (or that we add): `?product=`, `?variant=`, `?status=`, `?gateway=`,
`?from=`, `to=`, `range=`. `/admin/licenses` and `/admin/payments` gain these filters +
a "clear filters" affordance, so no dashboard link can dead-end. **A dashboard widget
that links nowhere is a dead widget** — the drill-through matrix is part of the spec, not
a nice-to-have.

---

## 11. File-by-file change list

### New

```
app/Services/VariantResolver.php
app/Services/Catalog.php                     # 4 products: code, label keys, colour, icon, ordering
app/Services/Nav.php                         # single nav source (sidebar + palette)
app/Services/Cache.php                       # file cache, per-widget TTL
app/Services/Analytics/{DateRange,KpiService,RevenueService,LicenseService,
                        TrafficService,MatrixService,GatewayService,ActivityFeed,
                        ChartPayload}.php
app/Services/Cron/ExpiringNotifier.php
database/migrations/2026_10_04_add_variants.sql
database/backfill_variants.php
public/js/admin.js                           # shell + palette + theme + range + filters
public/js/charts.js                          # chart registration/theming (thin wrapper)
public/vendor/charts/chart.umd.min.js
public/vendor/charts/apexcharts.min.js
views/partials/product_variant_matrix.php    # shared by admin + public (G3)
views/admin/partials/{stat_card,panel,chart,data_table,status_pill,empty_state,
                      skeleton,variant_chip,product_pill,range_picker,
                      legend_matrix,command_palette}.php
views/site/products_index.php                # new /products page
views/admin/analytics.php
public/css/admin.css                         # tokens + primitives (no build)
```

### Modified

```
database/schema.sql                  # +2 columns, node plans, copy fixes, CMS seed wording
views/layouts/admin.php              # shell rewrite: tokens, topbar, filters, palette, dark mode
views/admin/dashboard.php            # full rewrite (§6.2)
views/admin/{licenses,payments,customers,subscriptions,visitors,activities,
             settings,gateways,backups,cloud-backup}.php   # token sweep + new filters
views/admin/settings.php             # + "Products & variants" group (matrix settings, recommended
                                     #   product, BDT rate display, dashboard URLs per product×variant)
views/site/{home,pricing,compare,choose,choose_result,features}.php
views/site/products/{app,php,theme,node}.php   # variant strip + other-product rail
views/layouts/main.php               # nav gains /products + node; badge strip component
app/Controllers/Admin/DashboardController.php  # thin controller → Analytics services
app/Controllers/Admin/AnalyticsController.php   # new
app/Controllers/Site/{Home,Pricing,Choose,ChooseResult}Controller.php
app/Controllers/{Auth/Register,Site/Checkout,Account/Renewal,Admin/License}Controller.php  # set variant
app/Services/{ProductRecommender,LicenseReminderService}.php
app/Models/{License,Customer,Payment,Visitor}.php
routes/web.php                       # +/products, /admin/analytics, cron route, palette data route
routes/cron.php                      # new
lang/{en,bn}.php                     # +~220 keys, renames per §3.4, parity preserved
config/{app,licensing}.php           # variant defaults, analytics settings
public/manifest.json                 # description says four deployments
eskoofy-branding-website/AGENTS.md    # variant rules + dashboard architecture
docs/design/BRANDING-SITE-IMPROVEMENTS.md  # cross-ref
```

---

## 12. Phases & gates

Each phase is independently shippable and leaves the site working.

### Phase 0 — Foundations (no UI change) — **DONE**
Design tokens CSS, `public/css/admin.css`, `Catalog`, `Nav`, `Cache`, `VariantResolver`,
schema migration + backfill script, vendored chart bundles, `admin.js` skeleton.
**Gate:** `composer test` green (123 existing + new); backfill is idempotent (run twice,
second run is a no-op) and its report reconciles against `payments.variant`; CSP test
passes with the vendored bundles.
*Met:* 328 tests green. Backfill verified idempotent against the container. The report now
prints a **Reconciliation against payments.variant** block (BD payments on record, BD
customers after backfill, and any customer disagreeing with their latest payment — verified
by forcing a disagreement and confirming the warning). CSP gate is
`tests/Unit/Core/SecurityHeadersCspTest.php`, which asserts the emitted policy allows
`script-src 'self'` so the same-origin bundles need no allow-list entry.

> Gate-test naming drifted: the doc names `tests/Unit/Analytics/LicenseStatusTest.php`,
> `DashboardConsistencyTest.php`, `NoVariantConfusionTest.php` and `CommandPaletteTest.php`.
> Phase 0/1 coverage actually landed in `tests/Unit/Services/{Catalog,Cache}Test.php` and
> `tests/Unit/Services/Analytics/{DateRange,License,Revenue}ServiceTest.php`. The Phase 2–4
> gate tests named above are still unwritten because those phases are not built.

### Phase 1 — Fix the data (visible correctness before beauty) — **DONE**
B1–B5, B12 in the existing dashboard/controller. Reconcile `expiring_soon`, derived
status, `node` colour, dense revenue window, `plans.active` filter.
**Gate:** new `tests/Unit/Analytics/LicenseStatusTest.php` asserts the derived-status SQL
agrees with the stored status for a seeded matrix of fixtures; B1 regression test asserts
the KPI count equals the list count.
*Met:* B1–B5 and B12 verified live in the container, plus B14–B20 found during that
verification. `testExpiringCountAndListShareTheSamePredicate` covers the B1 gate.
The derived-status agreement check exists as
`testCountsByStatusUsesTheDerivedExpressionNotTheStoredColumn`, but it asserts the emitted
SQL rather than diffing it against a seeded matrix — that stronger form is still worth
writing, and B16 is exactly the bug it would have caught.

### Phase 2 — Variant showcase, public side — **NOT STARTED**
§4.2 matrix component, new `/products`, `home` 4-card grid + badge strips, pricing
table, `/choose` market question, `/compare` section, copy sweep.
**Gate:** `NoVariantConfusionTest` green; `/choose` still returns the same product for the
existing fixture set; `en`/`bn` key parity holds; visual review at 375/768/1440px in both
languages and both themes.

### Phase 3 — Shell + dark mode + command palette — **NOT STARTED**
`layouts/admin.php` rewrite, token sweep across all 30 admin views, topbar (search,
range, filters, theme toggle, notifications), collapsible sidebar, Ctrl+K palette.
**Gate:** every admin page renders correctly in light **and** dark with no
hardcoded-colour regressions; full click-through of all 24 sidebar items; keyboard-only
navigation of the shell works.

### Phase 4 — Dashboard widgets — **PARTIAL (read models only; W1–W21 not built)**
`Analytics/*` services, then W1–W21 in two drops (W1–W11, then W12–W21), plus the alert
strip and skeletons/empty states.
**Gate:** every widget drill-through lands on a filtered, correct list; no widget issues
a query outside its TTL window (assert via query counter in tests); `?refresh=1` bypasses
cache; screen-reader pass on all 21 widgets; `prefers-reduced-motion` respected.

### Phase 5 — Move email off the request path — **NOT STARTED**
`Cron\ExpiringNotifier`, `routes/cron.php`, crontab documentation, `B6`/`B7` closed.
**Gate:** 200 consecutive dashboard loads send zero emails; notifications still arrive on
the cron schedule (verified against a seeded fixture).

### Phase 6 — Docs & polish — **NOT STARTED**
`AGENTS.md` updates, design doc cross-links, README screenshots, `/admin/analytics` page,
performance budget (dashboard TTFB < 400 ms warm), a11y sweep.
**Gate:** full `composer test` green; Lighthouse a11y ≥ 95 on `/admin`; warm-cache query
count ≤ 3.

---

## 13. Testing strategy

| Layer | What | Where |
|---|---|---|
| Unit | `VariantResolver` resolution order + `toBdt`; `Catalog` completeness (4 products); `DateRange` bucketing + prior period; `ProductRecommender` extended with `variant` | `tests/Unit/Services/` |
| Unit | derived-status SQL vs stored status across a fixture matrix | `tests/Unit/Analytics/LicenseStatusTest.php` |
| Integration | `Analytics/*` against `FakeDatabase` — each service returns dense, correctly-bucketed series with no date gaps | `tests/Unit/Analytics/` |
| Regression | B1 (KPI == list), B2 (donut total == KPI total), B3 (all 4 products coloured), B12 (`plans.active=0` excluded) | `tests/Unit/Analytics/DashboardConsistencyTest.php` |
| Regression | B14 (upgraded columns nullable), B15 (BDT normalised), B16 (`derived_status` alias), B17/B18 (placeholder/param parity), B19 (MRR scoped to filters) | `tests/Unit/Services/Analytics/{License,Revenue}ServiceTest.php` |
| **Integration** | **seed a real MariaDB with mixed currencies, expired/suspended/cancelled licenses, a soft-deleted row, a retired plan and gaps in the payment series; render `/admin/dashboard` over every range × filter and diff against hand-computed SQL** | **`./docker/dev.sh up website` — the only layer that caught B14–B19** |
| Content | `en`/`bn` key parity; banned-phrasing sweep | `tests/Unit/I18n/` |
| Integration | palette route returns grouped, permission-filtered results; every action is CSRF-protected | `tests/Unit/Admin/CommandPaletteTest.php` |
| PWA | manifest/sw unchanged; new routes excluded from caching | existing `PwaTest.php` |
| Manual | visual review matrix: {2 themes} × {2 languages} × {3 breakpoints} × {5 filters} | checklist in §12 |

`tests/FakeDatabase.php` makes all of this DB-free, matching the existing suite. **No new
runtime dependency** — the vendored JS is not covered by PHPUnit; it is covered by the CSP
test plus the manual visual matrix.

---

## 14. Risks

| Risk | Severity | Mitigation |
|---|---|---|
| **Tailwind CDN purges/drops arbitrary tokens in production** — the CDN JIT scans the DOM, and dynamically-injected chart colours may not be generated | High | all colours resolve from CSS custom properties (not Tailwind classes), so JIT is irrelevant; verified in Phase 0 before any UI work |
| **Dark mode breaks the other 29 admin views** (hardcoded `bg-white`/`text-slate-*`) | High | Phase 0's token sweep lands *before* Phase 3; Phase 3 gate requires a full light+dark pass on every page |
| **Vendored UMD bundles go stale / no security updates** | Medium | pin exact versions, record them in `public/vendor/charts/VERSIONS.md`, add a scheduled review note; the wrappers in `charts.js` keep the API surface we depend on to ~10 functions so a swap is cheap |
| **Migration on a live production DB** (shared hosting, no migration runner) | High | additive `ALTER` only, idempotent backfill, pre-flight `--dry-run` with a full report, documented rollback (`DROP COLUMN`), run during a maintenance window |
| **Backfill mis-buckets historic revenue** | Medium | licenses/payments keep their own snapshots; report ambiguous rows for manual review rather than guessing; never overwrite a non-null existing value |
| **Scope creep into the 4 school products** | Medium | the plan is explicitly scoped to `eskoofy-branding-website/`; any product-side change is a separate plan |
| **21 widgets is too many / slow** | Medium | per-widget cache TTLs, lazy canvas rendering via `IntersectionObserver`, an "analytics" deep-dive page so the summary dashboard stays scannable |
| **New `variant` column leaks into the license API contract** | Low | `/api/v1` responses unchanged; variant is admin/marketing-only. Asserted by the existing API contract tests |

---

## 15. Open questions

- [ ] **Does Node.js get real BD plans, or stay INT-only + custom-order?** The matrix is
      designed to make either answer legible, but Phase 2 B10 needs a decision.
- [ ] **Variant filter on license issuance:** auto-derive from the customer (proposed) or
      let the admin override per license? Override means `VariantResolver` needs an
      explicit-override branch in the resolution order.
- [ ] **Is `appearance.dark_default` the right seed for the theme toggle, or should the
      toggle be user-local only (localStorage) with the setting as the server default?**
- [ ] **Should the command palette's "Jump to record" be admin-only, or also on the
      customer `/account` shell?** Customer-side search has different privacy constraints.
- [ ] **Owner + target ship date per phase.** Phase 0/1 are small and unblock everything
      else; Phase 3 is the largest single chunk of work.
- [ ] **Cross-ref into `docs/design/BRANDING-SITE-IMPROVEMENTS.md`** — that doc may
      already propose overlapping marketing-site work; reconcile rather than duplicate.