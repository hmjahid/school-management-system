# Features Implementation Prompt 6

## Overview
Four tasks: fix dashboard user header link, fix homepage teachers slider responsiveness, add see more/see less to teachers page, and make help page bilingual + interactive.

---

## Task 1: Fix Dashboard User Header Link Sometimes Not Working

### Root Cause Analysis
The dashboard topbar (`resources/views/partials/dashboard/topbar.blade.php` lines 88-147) contains an inline `<script>` that duplicates user menu handling already done by `resources/js/app.js` (lines 94-116). Two separate JS handlers fight over the same `[data-user-menu-root]` element:

- `app.js` line 94-116: Sets up user menu toggle/panel with `e.stopPropagation()` on toggle click + document click listener to close
- `topbar.blade.php` inline script lines 120-146: Re-queries the same `[data-user-menu-root]`, adds its own toggle click handler, and its own document click listener

When clicking the user header button, both handlers fire, each calling `stopPropagation()` and toggling the panel state independently. This causes race conditions where the panel opens then immediately closes.

Additionally, the dark mode toggle in `app.js` (line 463) uses a **capture-phase** (`true` flag) click listener. While it only acts on `[data-dark-toggle]`, its `stopPropagation()` call on dark-toggle clicks prevents event propagation — this isn't the direct cause but adds complexity.

### Fix
Remove the user menu handling from the inline `<script>` in `topbar.blade.php`. Keep only the language menu handling and sidebar toggle logic. The `app.js` user menu handler is sufficient and properly isolated.

**Files to modify:**
- `resources/views/partials/dashboard/topbar.blade.php`: Remove lines 120-146 (duplicate user menu JS) and the `closeUserMenu()` function

**Verification:** Click user avatar in dashboard header — dropdown should toggle reliably. Click outside — should close. Press Escape — should close. Language menu should still work independently.

---

## Task 2: Homepage Teachers Slider — 3 Desktop, 2 Tablet, 1 Mobile

### Current State
`resources/views/home.blade.php` line 283:
```html
<div class="min-w-[260px] max-w-[260px] snap-start shrink-0 group rounded-2xl ...">
```
Each card has a fixed 260px width. The track is a flex container with `overflow-x-auto`. This means cards don't respond to viewport — they're always 260px.

### Fix
Replace the fixed-width card sizing with responsive grid-based sizing using CSS `flex` with responsive basis:

**Option A (preferred — CSS grid approach):**
Replace the flex track with a CSS grid that shows 3/2/1 columns and scroll horizontally only on mobile.

**Option B (keep flex scroll but responsive card widths):**
Use responsive `min-w`/`max-w` values and let the JS `scrollAmount` be dynamic based on card width.

**Recommended: Option A** — Convert to a scroll container with responsive snap points:
- `lg:` = 3 cards visible (scroll snaps every 3)
- `md:` = 2 cards visible
- Default (mobile) = 1 card visible

**Files to modify:**
- `resources/views/home.blade.php`: Update card classes (line 283) and track container (line 277). Update JS scrollAmount (line 517) to be dynamic.

**Verification:** Resize browser — should show 3 cards on desktop, 2 on tablet (~768px), 1 on mobile (~480px). Prev/Next arrows should scroll by the visible count. Auto-scroll should work.

---

## Task 3: Teachers Page — Show 6 Initially, See More/See Less Toggle

### Current State
`resources/views/site/faculty.blade.php` shows ALL teachers in a grid (line 43-98). No pagination or lazy loading.

### Fix
1. Add lang keys for "See more" / "See less" in both `lang/en/site_frontend.php` and `lang/bn/site_frontend.php` under `faculty_page` section
2. In the Blade template, wrap teacher cards with a data attribute for initial visibility:
   - First 6 cards: visible by default
   - Remaining cards: hidden with `hidden` class and `data-faculty-extra` attribute
3. Add a "See more" / "See less" button below the grid
4. Add vanilla JS toggle logic in a `@push('scripts')` block

**Files to modify:**
- `lang/en/site_frontend.php`: Add `see_more`, `see_less`, `showing_of` keys under `faculty_page`
- `lang/bn/site_frontend.php`: Same keys in Bengali
- `resources/views/site/faculty.blade.php`: Add visibility logic + button + JS

**Verification:** Page loads showing 6 teachers. Click "See more" reveals all. Click "See less" hides back to 6. Works with search/filter (filtered results should override the limit).

---

## Task 4: Help & Documentation Page — Bilingual + Interactive

### Current State
`resources/views/dashboard/help/index.blade.php` (144 lines) is a static 2-column grid of 8 cards with bullet-point lists. All strings use `__()` translation helper but content is English-only (no Bengali translations exist for the bullet text). No interactivity — just static cards.

### Fix
Make the page bilingual and interactive:

1. **Add Bengali translations** for all help content to `lang/bn/dashboard.php`
2. **Redesign the page** with:
   - Search/filter bar to find topics
   - Collapsible accordion sections (one per topic) instead of static cards
   - Step-by-step numbered lists instead of bullets
   - Quick-links sidebar or top navigation to jump between sections
   - "Was this helpful?" feedback buttons (visual only, no backend needed)
   - Responsive: single column on mobile, sidebar+content on desktop

**Files to modify:**
- `lang/en/dashboard.php`: Add help page section keys (getting_started, managing_students, etc.)
- `lang/bn/dashboard.php`: Bengali translations for all help content
- `resources/views/dashboard/help/index.blade.php`: Full rewrite with accordion, search, bilingual content

**Verification:** Switch dashboard language to Bengali — all help text should be in Bengali. Search for "attendance" — should filter to relevant section. Click section headers — should expand/collapse. Responsive on mobile.
