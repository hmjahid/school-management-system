# UI/UX Guidelines

Source of truth for how the dashboard and public site should look and be built.
Read this before touching any `.blade.php` view or adding UI styles. It encodes
the "new era" component system — everything else (hand-rolled legacy markup) is
being migrated toward this and should **not** be copied.

## Design tokens

- **Source of truth:** `resources/css/app.css` `@theme` block.
- **Brand:** `brand-50/100/400/500/600/700/800/900` — CMS-driven. Never hard-code
  `blue-600`, `indigo-600`, etc. in dashboard views.
- **Accent:** `accent-500/accent-600` — secondary CMS color.
- **Semantics:** `success-*`, `warning-*`, `danger-*`, `info-*` and their `-50/-500/-700`
  shades. Prefer these for status (see `x-badge` below).
- **Surfaces:** `surface`, `surface-secondary`, `on-surface`, `on-surface-muted`.
- **Radii:** `radius-card` (1rem), `radius-button` (0.625rem).
- **Motion:** `transition-default`. `prefers-reduced-motion` disables marquee/reveal/scroll.
- **Typography:** `font-sans` (Inter). `font-bengali` (Noto Sans Bengali) is applied
  automatically when locale is `bn`. The layout stays `dir="ltr"` — Bengali is a
  left-to-right script.

### Do-not-use list

- `bg-blue-*` / `text-blue-*` / `border-blue-*` / `bg-indigo-*` on the **dashboard** —
  use `brand-*` (grep this before merging a view).
- Raw `bg-blue-600` buttons anywhere — use `x-button`.
- Native `onsubmit="return confirm(...)"` — use `data-confirm` / `confirmAction()`.
- Dynamic Tailwind class strings built at runtime (`bg-{{ $color }}-100`) — Tailwind
  can't see them. Use a static class/variant map instead.
- Green `session('status')` boxes — the layout already toasts via
  `data-flash-toast` (defined in the layout); don't add a second flash.

## Components

| Component | Purpose |
|---|---|
| `<x-page-header>` | Page `<h1>` + description + optional `actions` slot, breadcrumbs slot |
| `<x-admin-breadcrumbs>` | Breadcrumb trail under the header |
| `<x-card>` | Section card (header/body slots) |
| `<x-button variant size>` | `primary` / `secondary` / `danger` / `ghost` / `accent` |
| `<x-badge variant dot>` | `default` / `success` / `warning` / `danger` / `info` / `brand` |
| `<x-admin-data-table>` | Th table + paginator + built-in empty state (`scope="col"`, optional per-col `aria-sort`) |
| `<x-empty-state>` | Icons: `inbox` `users` `document` `chart` `shield` `clock` `tag` `sparkles` |
| `confirmAction({danger})` | Promise-based confirm; traps focus, restores it on close |
| `showToast(msg, type)` | Success/error/info toasts |
| `admin-input` / `admin-select` / `.admin-card` / `.admin-table-row` | Utility classes in `app.css` |

## New-era checklist (every new/edited view)

1. `x-page-header` (+ breadcrumbs) instead of a bare `<h1>`.
2. `x-admin-data-table` for lists; filters use `admin-input`/`admin-select` with `<label>`s.
3. Buttons via `x-button`; destructive confirmations via `data-confirm` (+ `data-confirm-danger`).
4. Status indicators via `x-badge` with a static variant map, never dynamic color strings.
5. Empty lists via `x-empty-state` (the table component does this automatically).
6. Every block has a `dark:` twin — inline a dashboard view and check both themes.
7. No raw blue/indigo, no native `confirm()`, no second flash box, no unlabeled inputs.

## Conventions

- Translation text is **never** added outside `lang/`; reuse existing `__()` keys or add
  keys to `lang/en` + `lang/bn`.
- Icons: inline SVG, `aria-hidden="true"` + an accessible label on interactive elements.
- Focus: `focus:outline-none focus:ring-2 focus:ring-brand-500/20` on inputs,
  `:focus-visible` global outline in `app.css` for keyboard users.
- Sortable table headers: pass `['label' => ..., 'class' => 'text-right', 'sort' => 'asc'|'desc']`
  to `x-admin-data-table` (renders `aria-sort`).

## Status pill map (canonical)

Prefer a PHP `$statusVariants` map feeding `x-badge`:

```blade
@php $statusVariants = ['paid' => 'success', 'pending' => 'warning', 'failed' => 'danger']; @endphp
<x-badge :variant="$statusVariants[$row->status] ?? 'default'">{{ __($row->status) }}</x-badge>
```

The reference implementations live in `resources/views/dashboard/library/issues/index.blade.php`,
`dashboard/modules/fees.blade.php`, and `dashboard/modules/attendance.blade.php`.