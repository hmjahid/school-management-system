# Eskoofy Branding Website — Enterprise-Grade Admin Dashboard & Sidebar Prompt

Hello! Thank you for helping us take the Eskoofy branding website's admin
backend (`eskoofy-branding-website/`) from "good" to a truly **enterprise-grade
operations console** — the kind of dashboard an ops leader at Stripe, Vercel or
Linear would feel at home in. Please work through the tasks below one at a
time, from top to bottom, and **continue until every task is finished**. Do not
stop early, and do not skip verification.

---

## Ground rules (read first)

- **Scope.** Only `eskoofy-branding-website/` is in scope. This is the
  marketing/license-server product, **not** a school-management product — do
  not port school modules here. No changes to the other products, and no
  marketing-copy changes.
- **Existing design system lives.** Build on the current token system in
  `public/css/admin.css` (`--brand`, `--surface-*`, `--text-*`, `--border`,
  `--success/--warning/--danger/--info`, product colours `--p-app/--p-php/...`,
  variant colours `--v-bd/--v-int`). Do **not** introduce a second styling
  system. Hardcoded Tailwind palette classes (`bg-white`, `text-slate-*`
  etc.) inside admin views should be migrated to tokens where you touch them.
- **No build step, no new runtime dependencies.** The site runs on shared
  hosting: no Composer packages at runtime, no npm, no bundler. Charts already
  ship vendored in `public/vendor/charts/` (chart.umd.min.js, apexcharts.min.js)
  and are driven by `public/js/charts.js` through the `esk-chart` marker +
  `admin.partials.chart` payload pipeline — keep using that pipeline. Plain
  ES5-compatible JS/CSS only.
- **Dark mode must be first-class.** Every new surface, chart and sidebar state
  must look right in both themes, reading from tokens only.
- **Single source of truth for nav.** The sidebar and the Ctrl+K command
  palette both render from `App\Services\Nav` (`app/Services/Nav.php`) — any
  nav change happens there, and the palette must stay in sync automatically.
- **Accessibility is not optional.** Skip links, `aria-current`, focus-visible
  rings, keyboard operability, min 4.5:1 contrast and colour-blind-safe delta
  cues (glyph + colour, never colour alone) already exist — preserve and extend
  them.
- **Keep tests green.** Run `cd eskoofy-branding-website && composer test`
  after every meaningful step (the suite is DB-free, 400+ tests, and includes
  `tests/Unit/Views/AdminDashboardViewTest.php` and
  `tests/Unit/Views/AdminShellViewTest.php`, which render views directly and
  promote notices to failures). Run
  `./vendor/bin/pint --test` if you touch PHP. Verify visually with
  `php -S 127.0.0.1:8011 -t public` and log in as the seed admin
  (`admin@eskoofy.com` / `admin123`) — look at the dashboard in light **and**
  dark theme, on mobile **and** desktop, with an empty database **and** seeded
  data.
- **Design references.** `docs/design/BRANDING-ADMIN-DASHBOARD-UX.md` and
  `docs/design/BRANDING-ADMIN-DASHBOARD-STATUS.md` at the repo root document
  the current UX intent and shipped status — read them before changing shell
  behaviour, and append what you shipped to the STATUS doc at the end.

---

## 1. Information-architecture pass: arrange the sidebar beautifully

**Goal:** The 24 nav items are flat within six groups and read as a wall of
links. Give them a clear, scannable enterprise hierarchy — no single-item
groups, no duplicated destinations.

- Rework `App\Services\Nav::groups()` so order and grouping follow what an
  admin actually does, e.g.:
  1. **Overview** — Dashboard
  2. **Sales & licensing** — Licenses, Plans, Payments, Subscriptions,
     Packages, Gateways
  3. **Customers & inbox** — All customers, Messages (badge), Custom orders
     (badge)
  4. **Analytics** — Analytics, Visitor log
  5. **Content** — Pages, Blog posts, Post categories
  6. **System** — Deployment & maintenance, Email templates, Client
     documents, Push notifications, Activity log, Clear cache, Backups,
     Settings, My account
  (That is exactly the existing 24 items — regrouped and reordered, none
  dropped.) Keep every existing route reachable; you may reorder/regroup but
  must not remove or shorten the contract (`Nav::destinations()` keeps
  feeding the command palette — verify the palette still lists every
  destination after the change).
- Keep the "Quick create" sub-section but polish it: visually separated as a
  distinct mini-section with small "+ action" rows (already exists — restyle).
- Messages and Custom orders keep live unread badges; badges should become
  count-capped "99+" if huge — add that.
- Consider subtle pinning: highlight the currently-most-actionable item (e.g. a
  dot on Expiring licenses within Licenses row driven by the same
  `expiring_soon` stat). Keep this data-driven from settings/stats, no magic.
- Make sure group labels, spacing rhythm and divider placement are consistent
  and polished in **rail (collapsed), expanded and mobile drawer modes**.

## 2. Sidebar scrollbar: enterprise-grade custom indicator

**Goal:** The nav column scrolls, but the default OS scrollbar looks
out-of-place (and on macOS disappears entirely). Replace it with a custom,
design-system-native scrollbar indicator, fully keyboard and drag friendly,
with zero dependencies.

- Implement a custom scrollbar for `.esk-sidebar-nav` (and only there):
  a slim right-side track (4–6 px, token-coloured `--sidebar-border` on top of
  `--sidebar-bg`) with a rounded thumb in `--sidebar-text-muted`, brightening
  to `--sidebar-text` on hover/drag/scroll.
- **Auto-reveal behaviour:** hidden until the column is actually
  scrollable/hovered/being-dragged; appears while scrolling and fades after
  ~600 ms idle (like modern editors). Must never render when content fits.
- **Drag interaction:** pointerdown/pointermove/pointerup on the thumb drags
  the scroll position 1:1; cursor changes to grabbing.
- **Touch devices:** hide the custom indicator entirely on coarse pointers
  (`@media (pointer: coarse)`) — native overlay scrollbars already work
  there. The custom indicator must never intercept touch scrolling, and the
  existing `overscroll-behavior: contain` must survive.
- **Keyboard support:** the thumb is decorative — `aria-hidden`, never a
  focus trap; the nav list keeps native keyboard scrolling (arrows,
  PageUp/PageDown/Home/End). Do not break that, and do not add a
  `role="scrollbar"` widget.
- Update the thumb on every scroll event **and**:
  content resize (ResizeObserver with a rAF fallback), sidebar rail
  collapse/expand, and mobile drawer open (sizes change there too).
- Respect `prefers-reduced-motion` (no fade/slide transitions).
- No `iframe`/canvas hacks; pure CSS + small vanilla JS, added to
  `public/js/admin.js` with clear sectioning and to `admin.css` under the
  existing shell section. Rail mode (4.5rem) must keep it working.

## 3. Sidebar shell polish (enterprise feel)

- **Active indicator:** keep the brand-blue left bar but make it smoother —
  animated slide/tween between active items is optional; at minimum make the
  active pill include a subtle brand-tinted background wash
  (`color-mix(in srgb, var(--brand) 12%, transparent)`), not just grey.
- **Rail mode tooltips:** when collapsed, hovering/focusing a nav link should
  show a small token-styled tooltip (label + optional badge count). Pure CSS
  or minimal JS. The tooltip itself is decorative (`aria-hidden`) — the link
  keeps its accessible name; it must dismiss on blur/Escape and never obscure
  the focus ring.
- **Persistence:** the rail-collapsed state already persists in localStorage
  (`esk_admin_rail` in `public/js/admin.js`) — keep that, and make sure the
  scrollbar and tooltips settle without a flash on load.
- Add a subtle **notification summary card** at the very top of the nav
  ("3 things need attention → Dashboard") only when the alert-strip items
  exist, linking to `/admin` — hidden in rail mode. It complements (does not
  replace) the topbar bell dropdown.
- Consistent focus-visible outline colour using brand token inside the dark
  sidebar (white-on-dark ring), and keep skip-link ordering.

## 4. Dashboard page: level-up to enterprise

**Goal:** `views/admin/dashboard.php` already has KPI cards, area/donut/line/
heatmap/funnel charts. Push it to true enterprise polish rather than adding
more noise.

- **Hero metrics row:** keep 6 KPI cards but make the first card (Revenue) a
  *hero* card — larger, gradient/solid accent, delta big and readable,
  sparkline higher-resolution. The grid should read hierarchy at a glance.
- **Compact number formatting ("scale"):** enterprise dashboards abbreviate
  large values — add a small shared helper (e.g. `money_short` / `num_short`)
  that renders `$12.4k`, `$1.2M`, `3.4k` on KPI feet, chart y-axes and dense
  tables, while tooltips and detail rows keep full tabular values
  (`.esk-tabular`). Wire it through `ChartPayload` axis `valueFormat`, not
  ad-hoc strings in views.
- **Trending block (new):** a compact "What's trending" panel:
  - top 3 plans by sales in the window (delta vs prior window),
  - top 3 countries by visitors (delta),
  - fastest growing gateway,
  - each row with a mini bar/trend arrow and a link to its page.
  It must respect the active dashboard range and product filter
  (`$range` / `$filters` the controller already passes — no independent
  defaults). All data must come from the existing analytics services
  (`App\Services\Analytics\*`: `RevenueService`, `LicenseService`,
  `TrafficService`, `KpiService`) — **extend the services** rather than
  embedding SQL in the view, reuse the controller's already-cached
  aggregates where possible, and cache new aggregates with
  `App\Services\Cache` matching its current TTL conventions.
- **Radial "scale" gauge card (new):** a single donut-gauge showing MRR as %
  of the ARR target, target coming from a new settings key
  (`analytics.arr_target`, default blank → the card degrades to plain MRR/ARR
  numbers). Use the existing chart pipeline (`ChartPayload::donut`), not
  custom canvas.
- **Empty/onboarding state:** when the whole dashboard is empty (no
  products/licenses/payments), show one focused onboarding checklist panel
  (Issue first license → Create plan → Configure gateway) instead of a sea of
  empty-state panels — reuse `admin/partials/empty_state.php` styling.
- **Section ordering & density:** reorder sections into an ops-narrative:
  Alert strip (keep it at the very top, above quick actions, tone-tinted as
  today) → Hero KPIs → Trending/gauge → Revenue chart → Splits & flow →
  Lists (expiring, top customers, activity, recent) → Product fleets. Add
  consistent `esk-panel` head actions, and make tables keep tabular-nums
  alignment (already `.esk-tabular` — apply where missing).
- **Responsive intelligence:** at `lg` and below, charts stack so primary
  (revenue) is never below secondary panels. Verify at 375px, 768px, 1280px
  and 1920px widths.

## 5. Design-system refinements

- Add/normalize tokens: a `--scrollbar-*` family (bg, thumb, thumb-hover,
  thumb-active), refine `--shadow-2` to something softer / less heavy, and
  ensure the dark sidebar tokens read elegantly (very slight elevation
  difference between sidebar and content, premium feel).
- Micro-interactions: 80–150 ms token-based transitions on nav links, panels
  hover lift (1 px translate only), button presses. No bouncy/short-lived
  animation gymnastics; everything under `prefers-reduced-motion` guard.
- Update `views/layouts/admin.php` to bump the CSS/JS cache-busting query
  (`?v=`) after the change ships. The service worker serves assets
  stale-while-revalidate, so the query bump is the invalidation mechanism —
  do not add a second cache-busting scheme.

## 6. Tests & docs

- Extend/adjust `tests/Unit/Views/AdminDashboardViewTest.php` so new panels
  render without warnings when all inputs are absent (the view must degrade,
  never fatal).
- Add a small unit test for any new analytics service methods (following the
  existing `tests/Unit/Services/Analytics/*` patterns and `FakeDatabase`).
- Append a "shipped" summary to `docs/design/BRANDING-ADMIN-DASHBOARD-STATUS.md`.
- Do **not** create new top-level docs unless unavoidable; prefer updating the
  existing UX/STATUS docs.

---

## Definition of done

1. Sidebar: regrouped (all 24 items reachable), custom scrollbar with drag
   + auto-reveal indicator working in expanded/rail/mobile modes, hidden on
   touch devices, tooltips in rail mode, both themes, both
   empty/populated states.
2. Command palette still lists every destination after the nav reorder.
3. Dashboard: hero KPI card, compact number formatting, trending block
   (range-aware), ARR-target gauge, onboarding empty state, responsive
   layouts verified at 375/768/1280/1920 px.
4. `composer test` fully green; `pint --test` clean.
5. Visual verification pass described above actually performed and issues
   fixed — including dark mode and the mobile drawer.
6. STATUS doc updated. Nothing outside `eskoofy-branding-website/` changed.
