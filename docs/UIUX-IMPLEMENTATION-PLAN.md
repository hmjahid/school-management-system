# UI/UX Implementation Plan

Tracks the implementation of `UIUX-IMPROVEMENTS.md` for the public website and dashboard.
Scope: consolidate the two dashboard "eras" onto the component system, fix concrete defects, polish
a11y/visual consistency, and enable Bengali typography. Every task lists files, changes, and acceptance.

**Baseline (run before/after):** `composer test`, `./vendor/bin/pint --test`, `npm run build`.
**Working rules for every edit:**
- Use existing components: `x-page-header`, `x-card`, `x-button`, `x-badge`, `x-admin-data-table`,
  `x-empty-state`, `x-admin-breadcrumbs`, `confirmAction()` (via `data-confirm`), `showToast()`.
- Tokens over literals: `brand-*` / `accent-*` (CMS-driven), never hard-coded `blue-600`.
- Every new/edited block needs a `dark:`-mode twin.
- No new JS libraries. Reuse `app.js` utilities (`debounce`, `confirmAction`, `showToast`).
- No translation-text changes outside `lang/` keys; keep English keys, reuse existing `__()` strings.

---

## Phase 0 — Verify baseline

- [x] `composer test` green (876 tests / 2219 assertions expected).
- [x] `npm run build` completes; `public/build/manifest.json` exists.
- [x] Screenshot the "before" state: fees, attendance, exam results, reports, library, transport routes.

---

## Phase 1 — Upfront defect fixes (must-fix, ship-first) [~2 days]

| # | Task | Files | Change | Accept | Status |
|---|------|-------|--------|--------|--------|
| 1.1 | Fix broken Reports grid markup | `dashboard/reports/index.blade.php` | Close the unclosed `<a>` on line 37 (Analytics card); add missing `/2` card gap; keep 5 cards in `sm:grid-cols-2 lg:grid-cols-3` | DOM validates; 5 distinct cards, no nesting; grid renders 3+2 | ✅ Done (with x-page-header + dark twins) |
| 1.2 | Fix stray variable in reset-password | `auth/reset-password.blade.php:16` | Drop `{{ $li ?? '' }}`; render only `{{ $error }}` | Errors list shows clean text | ✅ Done |
| 1.3 | Fees: status color map + branded confirm | `dashboard/modules/fees.blade.php` | Replace `bg-{{ $color }}` dynamic classes with a static status→class map (like `library/issues`); swap `onsubmit="return confirm(...)"` for `data-confirm` + `data-confirm-title` | Pills always render; Tailwind ships the classes; confirm uses styled modal | ✅ Done (full x-* migration) |
| 1.4 | Attendance: semantic status pills + empty state | `dashboard/modules/attendance.blade.php` | Map present→green / absent→red / late→amber color pills; replace inline colored-class map; add `x-empty-state` on empty row (keep colspan) | Statuses distinguishable by color + text; empty state styled | ✅ Done (full x-* migration) |
| 1.5 | Remove redundant status boxes (layout already toasts) | `media/index`, `events/index`, `cms/edit`, `settings/about`, `bulk/import`, `profile/edit`, `students/promote`, `exams/results` | Delete `@if (session('status'))…` green boxes; they duplicate `data-flash-toast` in layout | No duplicate flashes; no double toast | ✅ Done |
| 1.6 | Blue-600/stale-color sweep (dashboard only) | all `dashboard/**/*.blade.php` | `bg-blue-*`, `text-blue-*`, `border-blue-*`, `bg-indigo-*`, `focus:border-blue-*` on interactive controls → `brand-*` / `x-button`; keep semantic blue for links if distinct | grep returns 0 dashboard hits for interactive blue controls | ✅ Done — full sweep across 125+ dashboard views + `partials/dashboard/*`; `rg 'bg-blue-[0-9]|bg-indigo-[0-9]|focus:(border|ring)-(blue|indigo)'` returns 0 under `resources/views/dashboard/` and `partials/dashboard/` |
| 1.7 | Dark-mode gap sweep (old-era views) | `modules/fees`, `modules/attendance`, `modules/exams`, `exam*/results`, `library/issues/index`, `transport/*`, `reports/index`, `bulk/import` | Add `dark:` variants to white cards/tables/inputs on these views | In `dark` mode all these pages are readable (no white boxes) | ✅ Done on migrated views (`modules/*`, exam results, library issues, transport routes/vehicles/assignments, reports) |

---

## Phase 2 — Migrate legacy views to the component system [~3 days]

Rule: keep identical data/behavior; only presentation changes.

| # | View | Heads | Status |
|---|------|-------|--------|
| 2.1 | `dashboard/modules/fees.blade.php` | `x-page-header` (+ `x-admin-breadcrumbs`), filter bar with `admin-input`/`admin-select` + labels, `x-admin-data-table` + `x-badge` status map, `x-button` actions, `x-empty-state` via table, native confirm → `data-confirm` | ✅ Done |
| 2.2 | `dashboard/modules/attendance.blade.php` | Same pattern; labelled filters; `x-button`; `x-badge` status colors; `x-empty-state` | ✅ Done |
| 2.3 | `dashboard/exams/results.blade.php` | Stat cards → `x-card`; replace the hand-rolled publish modal (lines 143-199) with a `x-confirm-modal`-style dialog + explicit in-page summary card before publish; toast instead of `session('status')`; `x-button` for Save/Export/Publish; keep per-student marks inputs | ✅ Done (publish modal keeps its own summary panel but now uses `modal-backdrop`/`modal-panel` + JS open/close + focus; `data-confirm` for unpublish; toast from layout) |
| 2.4 | `dashboard/library/issues/index.blade.php` | `x-page-header`, `x-admin-data-table`, `x-badge` map (already mapped), ghost `x-button`, `data-confirm` | ✅ Done |
| 2.5 | `dashboard/transport/routes/index.blade.php` + `vehicles/index` + `assignments/index` | `x-page-header`, `x-card`, `x-button`, `data-confirm`; add `dark:` twins | ✅ Done |
| 2.6 | `dashboard/modules/exams.blade.php` | `x-page-header`, `x-admin-data-table`, `x-badge`, `x-button` | ✅ Done (plus classes, teachers, parents) |
| 2.7 | `dashboard/reports/index.blade.php` | `x-page-header` + breadcrumbs; `x-card` grid with `x-button`/text link; `dark:` twins; fix 1.1 styling for good | ✅ Done |

Also: **shared component** hardening — `admin-data-table` header alignment is now class-driven
(`text-right` no longer conflicts); `.dark .admin-table-row` hover twin added to `app.css`.

Acceptance per view: page renders, filters work, dark mode legible, no raw `bg-blue-600` buttons,
no native `confirm()`, no redundant status box.

---

## Phase 3 — Interaction & a11y hardening (app.js + components) [~2 days]

| # | Task | Files | Change |
|---|------|-------|--------|
| 3.1 | Confirm modal: focus trap + focus restore | `app.js` `confirmAction()` + layout block (lines 140-150) | Trap Tab within modal, restore focus to trigger on close, `role="alertdialog"` already set; add `data-confirm-danger` variant → red OK button |
| 3.2 | Lightbox a11y | `app.js` gallery lightbox (363-417) | Add focus trap + `role="dialog"` + `aria-modal`; add/remove keydown listener on open/close; close button focus anchor |
| 3.3 | Gallery filter tabs use brand color | `app.js` (346-350) | Replace hard-coded `bg-blue-600` with `bg-brand-600` (Tailwind will compile it) |
| 3.4 | Skip-to-content | `layouts/app.blade.php` + `layouts/dashboard.blade.php` | Add `a.skip-link` + `#main-content` anchor at top of `<body>` |
| 3.5 | Empty-state sweep on old era | all list views not using `x-empty-state` | Use `x-empty-state` (icon+title+message) instead of dashed text rows where feasible |
| 3.6 | Table density + focus rows | `admin-data-table.blade.php` | Preserve current density; ensure `scope="col"` present (already), add `aria-sort` on sortable headers later; don't block keyboard |

**Status:** ✅ 3.1–3.4 pre-existing in `app.js`/layouts and kept; 3.5 completed (38 list/detail views
migrated to `x-empty-state`, incl. activity, ledger, payroll, library, media, SMS, transport);
3.6 done (`scope="col"` + optional per-header `aria-sort`). `focus-visible` global outline added in `app.css`.

---

## Phase 4 — Public website polish [~2 days]

| # | Task | Files | Change |
|---|------|-------|--------|
| 4.1 | Bengali font + locale switching | `app.css` `@theme`, `layouts/app` + `dashboard` font link | Add `--font-bengali: 'Noto Sans Bengali', 'Inter', sans-serif`; apply `font-bengali` when `app()->getLocale() === 'bn'`; add `Noto Sans Bengali` to the Google Fonts link. Keep `dir="ltr"` (Bengali is a left-to-right script — no RTL flip) |
| 4.2 | Footer social links | `partials/site/footer.blade.php` | Replace `opacity-50` placeholder spans with real configured links (social URLs from site settings) + hover states |
| 4.3 | Results lookup result card + PDF | `site/results.blade.php` | After lookup, show a typed result summary (grade/GPA/percentage) card + "Download PDF" button before the print view; keep print layout |
| 4.4 | Form success inline status | `contact`, newsletter, scholarship forms | Replace success-modal-only with inline success alert (`role="status"`) + toast; keep honeypots |

**Status:** ✅ 4.1 done (token + `font-bengali` swap on both layouts incl. font links; layout stays `dir="ltr"` since Bengali is LTR); ✅ 4.2 done (`partials/site/social-links` wires configured URLs + hover states); ✅ 4.3 done (typed result card with grade/GPA/percentage donut + Download PDF); ✅ 4.4 done (contact page shows inline `role="status"` success alert + toast; newsletters/toasts already in place; honeypots kept).

---

## Phase 5 — Design-system housekeeping [~1 day]

- [x] **5.1 Semantics tokens:** add `--color-success-*`, `--color-warning-*`, `--color-danger-*` to `@theme`;
      optionally map `x-badge` variants onto them.
- [x] **5.2 Badge/status map cleanup:** audit remaining inline `bg-emerald-100 text-emerald-800`-style pills in
      `dashboard/**` and route through `x-badge` or a status map partial (40+ pills converted; remaining hits are
      intentional non-pills: attendance cells, step circles, icon chips).
- [x] **5.3 Document the system:** add `docs/UIUX-GUIDELINES.md` (tokens, component cheatsheet, "new era"
      checklist, dark-mode rule, do-not-use list: `blue-600`, native `confirm()`, dynamic class strings).
- [x] **5.4 Update `docs/UIUX-IMPLEMENTATION-PLAN.md`** checkboxes + `docs/README.md` index.

---

## Verification / Done definition

1. `composer test` fully green.
2. `./vendor/bin/pint --test` clean (run `./vendor/bin/pint` if not).
3. `npm run build` succeeds and Tailwind generates the new token/badge classes.
4. Manual pass (light + dark): fees, attendance, exams/results, library, transport, reports, media,
   events, profile, bulk import — no raw blue buttons, no native confirms, no dual status boxes,
   empty states styled.
5. Bengali spot check: public site renders with Noto Sans Bengali and RTL layout when locale = bn.
6. Keyboard: Tab through a list page reveals focus rings; Ctrl+K search; confirm modal traps focus.

## Out of scope (parked for later)

- Full AJAX tables / live search on every list (roadmap).
- SVG chart library + chart page upgrades (feature roadmap F7).
- Sidebar icon-only collapse toggle (nav experiment).
- Table density/column-visibility persisted prefs (needs localStorage spec).
- Bulk actions bar rollout beyond what's already wired.