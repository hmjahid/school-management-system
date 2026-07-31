# Features Implementation Prompt 14 — Sidebar, Media Picker, Events, Hero Designs, Filters

## Context

Laravel 12 school management system with Blade + Tailwind CSS v4 + Vite. Dashboard views extend `layouts.dashboard`. SQLite in-memory for tests, `database/database.sqlite` for dev. Spatie `laravel-permission` for auth.

## Task List

### 1. Restructure "Users & Roles" sidebar into a dropdown group

**Problem**: Users & Roles, Roles, and Permissions are three separate `x-admin-nav-link` items under the "Administration" section. They should be combined into a single `<details>` dropdown group labeled "Users & Roles" (matching the pattern of People, Daily, HR, Finance, etc.).

**Fix**:
- `resources/views/partials/dashboard/sidebar.blade.php`: Replace the three flat `x-admin-nav-link` items (lines 326-329) with a `<details class="group">` dropdown:
  - Summary: "Users & Roles" with icon
  - Auto-open: `@if (request()->routeIs('dashboard.users.*', 'dashboard.roles.*', 'dashboard.permissions.*')) open @endif`
  - Children: Users, Roles, Permissions — each as `<a>` links with active state styling matching other dropdown groups.

### 2. CMS image fields: select from media library

**Problem**: CMS image fields (`resources/views/dashboard/cms/fields/image.blade.php`) accept a file upload or URL string. Admins should be able to select from already-uploaded files in the media library.

**Fix**:
- Add a "Browse Media" button next to the URL input that opens a modal/iframe showing the media library (`/dashboard/media`).
- The modal displays a grid of uploaded images with thumbnails. Clicking one populates the URL input with the file's URL (`/storage/{file_path}`).
- Implement via a lightweight JS function: open `/dashboard/media?select=1` in a modal. The media index view gets a `?select=1` query param that, when set, wraps each image in a click handler that posts the URL back to the parent window via `postMessage` or `window.opener`.
- Update `resources/views/dashboard/cms/fields/image.blade.php` to include the "Browse" button and JS listener.
- Update `resources/views/dashboard/media/index.blade.php` to support `?select=1` mode (simpler UI, click-to-select behavior).

### 3. Move homepage recent events above upcoming events

**Problem**: Homepage currently only shows "Upcoming Events". The user wants a "Recent Events" section displayed above the upcoming events.

**Fix**:
- `app/Http/Controllers/Web/HomeController.php`: Add a `$recentEvents` query — events with `start_date < now()` ordered by `start_date DESC`, limited to 5.
- `resources/views/home.blade.php`: Add a "Recent Events" section before the "Upcoming Events" section, using the same grid/card layout. The recent events section shows past events that were notable/interesting.
- CMS content `events` group can have a `recent_title` field or reuse the same title.

### 4. Add hero design 5: school name + full-width image

**Problem**: Need a hero design that showcases the school name over a full-width background image.

**Fix**:
- Create `resources/views/partials/site/hero/design-5.blade.php`: Full-width hero with background image (from `$heroImg`), school name as large centered white text with text shadow, optional headline/subtitle below. Dark overlay gradient for readability. Minimal CTA.
- `config/cms_pages.php`: Add `'design-5' => 'Design 5 — Full-width image with school name'` to the hero_design select options.

### 5. Add hero design 6: school name + slider in hero

**Problem**: Need a hero design that includes a photo slider within the hero section.

**Fix**:
- Create `resources/views/partials/site/hero/design-6.blade.php`: Split layout — left side has school name, headline, subtitle, CTAs. Right side has an auto-rotating image slider (using the CMS slider content or fallback events). CSS-only fade transition (no new npm deps), 4-second interval.
- `config/cms_pages.php`: Add `'design-6' => 'Design 6 — School name with hero slider'` to the hero_design select options.
- Pass `$sliderSlides` (from CMS slider content or `$sliderFallback`) to this hero design.

### 6. Add payment type filter in fee payments page

**Problem**: Fee payments page only has search and status filters. Need a payment method filter.

**Fix**:
- `app/Http/Controllers/Web/DashboardFeePaymentController.php`: Add `payment_method` filter — when `$request->filled('payment_method')`, add `->where('payment_method', $request->payment_method)` to the query.
- `resources/views/dashboard/fee-payments/index.blade.php`: Add a `<select name="payment_method">` dropdown with options: All methods, Cash, Bank Transfer, Check, Online Payment, Mobile Banking, Other. Preserve selection on reload.

### 7. Add fee type filter + columns-directed layout in fees page

**Problem**: Fees page has no fee_type filter and uses a row-directed table layout. User wants a column-directed (card/grid) layout with fee_type filter.

**Fix**:
- `app/Http/Controllers/Web/DashboardModulesController.php` `fees()`: Add `fee_type` filter — when `$request->filled('fee_type')`, add `->where('fee_type', $request->fee_type)` to the query.
- `resources/views/dashboard/modules/fees.blade.php`: 
  - Add `<select name="fee_type">` dropdown with options from Fee model constants (tuition, admission, exam, transport, library, uniform, other).
  - Replace the table layout with a card grid layout (`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4`). Each fee card shows: name, code, amount, class, status badge, fee type badge, edit button. This is "columns-directed" (cards in columns) vs "row-directed" (table rows).

### 8. Add proper filter system in all pages where necessary

**Problem**: Some pages lack filters that would help users find data.

**Fix** — audit and add filters to these pages:
- `dashboard.modules.teachers`: Add status filter (active/inactive)
- `dashboard.modules.parents`: Add search is already there, verify it works
- `dashboard.modules.classes`: Add search is already there
- `dashboard.modules.attendance`: Add class_id and section_id filters (date and status already exist)
- `dashboard.modules.exams`: Add class_id filter (status already exists)
- For each, update the controller to accept the new filter parameter and the view to show the filter dropdown.

### 9. Fix school info page sidebar selection

**Problem**: When opening the School Info page (`dashboard.settings.index`), both "School Info" and "Settings" sidebar items appear selected because the Settings link uses `route-is="dashboard.settings.*"` which matches all settings sub-routes.

**Fix**:
- `resources/views/partials/dashboard/sidebar.blade.php`: Change the Settings link's `route-is` from `dashboard.settings.*` to `dashboard.settings.general` (the specific route for the tabbed settings page). This prevents it from matching `dashboard.settings.index` (school info).
- Alternatively, exclude `dashboard.settings.index` from the Settings wildcard: use `routeIs('dashboard.settings.*') && !routeIs('dashboard.settings.index')` — but this is harder to express in the component. The cleaner fix is to narrow the Settings route-is to `dashboard.settings.general` (or add all specific sub-routes).

### 10. Improve news page past events section

**Problem**: Past events on the news page are shown in a muted opacity-75 grid with minimal info. Should be more attractive and match the homepage recent events style.

**Fix**:
- `resources/views/site/news.blade.php`: Redesign the past events section:
  - Use a visually appealing card grid (similar to homepage events) instead of muted opacity-75 cards.
  - Each card shows: event title, date (formatted nicely), location, and a subtle category/status badge.
  - Add a decorative section header with accent bar (orange/teal, matching other section headers).
  - Keep the section clearly labeled as "Past Events" so users understand these are completed.

## Files to Modify

| # | File | Change |
|---|---|---|
| 1 | `resources/views/partials/dashboard/sidebar.blade.php` | Users & Roles dropdown, Settings route-is fix |
| 2 | `resources/views/dashboard/cms/fields/image.blade.php` | Media library browser button |
| 3 | `resources/views/dashboard/media/index.blade.php` | Select mode support |
| 4 | `app/Http/Controllers/Web/HomeController.php` | Add recentEvents query |
| 5 | `resources/views/home.blade.php` | Recent events section |
| 6 | `resources/views/partials/site/hero/design-5.blade.php` (new) | Full-width image hero |
| 7 | `resources/views/partials/site/hero/design-6.blade.php` (new) | Hero with slider |
| 8 | `config/cms_pages.php` | Add design-5, design-6 options |
| 9 | `app/Http/Controllers/Web/DashboardFeePaymentController.php` | payment_method filter |
| 10 | `resources/views/dashboard/fee-payments/index.blade.php` | Payment method filter dropdown |
| 11 | `app/Http/Controllers/Web/DashboardModulesController.php` | fee_type filter, attendance/exam/teacher filters |
| 12 | `resources/views/dashboard/modules/fees.blade.php` | fee_type filter + card grid layout |
| 13 | `resources/views/dashboard/modules/teachers.blade.php` | Status filter |
| 14 | `resources/views/dashboard/modules/attendance.blade.php` | class/section filters |
| 15 | `resources/views/dashboard/modules/exams.blade.php` | class filter |
| 16 | `resources/views/site/news.blade.php` | Past events redesign |
| 17 | `lang/en/dashboard.php` | New labels if needed |
| 18 | `lang/bn/dashboard.php` | New labels if needed |
| 19 | `tests/Feature/HomeHeroAndSliderTest.php` | Tests for new hero designs + recent events |

## Verification

```bash
php artisan optimize:clear
# Sidebar: "Users & Roles" is a dropdown with Users, Roles, Permissions inside
# Sidebar: Settings link only highlights on settings/general, not on school info
# CMS image fields show "Browse Media" button → opens modal → select image → URL populated
# Homepage: Recent Events section appears above Upcoming Events
# Hero: 6 designs available in CMS editor, designs 5 & 6 render correctly
# Fee payments: Payment method filter works
# Fees: Fee type filter works, cards display in grid layout
# Teachers: Status filter works
# Attendance: Class/section filters work
# Exams: Class filter works
# News page: Past events section is visually appealing with card grid
php artisan test --testsuite=Feature --filter=HomeHeroAndSliderTest
```
