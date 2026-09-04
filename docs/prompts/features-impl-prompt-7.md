# Features Implementation Prompt 7 — Bug Fixes & UX Improvements

## Context
School Management System — Laravel 12, Blade, Tailwind CSS 4, vanilla JS.
Previous commits introduced bilingual announcements, audience checkboxes, display_target, and section visibility. Several bugs and missing features surfaced after those changes.

---

## Task 1: Fix Announcements Page Error

**Problem:** `resources/views/dashboard/announcements/index.blade.php` line 39 does `{{ $row->audience }}` but `audience` is now a JSON array (cast as `array` in the model). PHP 8.3's `htmlspecialchars()` throws `TypeError` when receiving an array.

**Fix:** Replace the raw `{{ $row->audience }}` with a loop that renders each audience value as a badge, matching the pattern already used in `notices/index.blade.php` lines 42-49.

**File:** `resources/views/dashboard/announcements/index.blade.php`

---

## Task 2: Fix Help & Documentation Page Error

**Problem:** `resources/views/dashboard/help/index.blade.php` uses `__('help.xxx')` translation keys, but the translations live inside `lang/en/dashboard.php` under the `help` key. Laravel resolves `__('help.sections')` by looking for `lang/en/help.php` which doesn't exist, so it returns the raw string `"help.sections"`. The `@foreach` on that string crashes.

**Fix:** Change all `__('help.xxx')` to `__('dashboard.help.xxx')` in the blade file. There are ~8 occurrences.

**File:** `resources/views/dashboard/help/index.blade.php`

---

## Task 3: Fix Visitor Log Not Logging

**Problem:** `VisitorLog` model doesn't set `$timestamps = false`. The `visitor_logs` table only has `created_at` (no `updated_at`). Eloquent auto-adds `updated_at` to INSERT, DB rejects it, error is silently caught in middleware.

**Fix:** Add `public bool $timestamps = false;` to `VisitorLog` model and add a `booted()` static method that sets `created_at = now()` on creation.

**File:** `app/Models/VisitorLog.php`

---

## Task 4: Fix Ledger Page Issues

**Problem:** `ChartOfAccountSeeder` is never called from `DatabaseSeeder`. `DemoLedgerSeeder` creates accounts with non-standard codes (AST-001, INC-001, etc.) that don't match what the app expects (1000, 1010, 4000, 5000, 5500). Cashbook/Bankbook pages 404. Also, DemoLedgerSeeder has reversed debit/credit for income/expense entries.

**Fix:**
1. Add `ChartOfAccountSeeder::class` to `DatabaseSeeder` before `DemoLedgerSeeder`.
2. Rewrite `DemoLedgerSeeder` to reference standard chart of accounts by code, and fix debit/credit orientation (income=credit, expense=debit).

**Files:** `database/seeders/DatabaseSeeder.php`, `database/seeders/DemoLedgerSeeder.php`

---

## Task 5: Move Bulk SMS Below Messages

**Problem:** Bulk SMS is item #10 in sidebar (line 229), far from Messages (item #2, line 77).

**Fix:** Cut the `@can('send_bulk_sms')...@endcan` block from line 229-231 and paste it immediately after the Messages `</x-admin-nav-link>` (line 79).

**File:** `resources/views/partials/dashboard/sidebar.blade.php`

---

## Task 6: Fix Website Search

**Problem:** Site header search submits `GET /?q=...` to `HomeController@index`, which completely ignores the `q` parameter.

**Fix:** Create a dedicated `SiteSearchController` with an `index` method that:
1. Reads `$request->input('q')`.
2. Searches across `News`, `Notice`, `Event` models using `LIKE` queries.
3. Returns a new `site/search.blade.php` view with results.
4. Add route `GET /search` → `SiteSearchController@index`.
5. Update the search form action in `nav.blade.php` to point to the new route.

**Files:** New `app/Http/Controllers/Web/SiteSearchController.php`, new `resources/views/site/search.blade.php`, `routes/web.php`, `resources/views/partials/site/nav.blade.php`

---

## Task 7: Fix Dashboard Sidebar Search

**Problem:** Sidebar search input is cosmetic only — no event listeners, no filtering logic.

**Fix:** Add JavaScript in `sidebar.blade.php` that:
1. Listens for `input` events on `[data-sidebar-search-input]`.
2. Filters sidebar nav links by text content (case-insensitive `includes()`).
3. Expands `<details>` parents of matching children.
4. Shows/hides section headers based on whether any child matches.
5. Shows "No results" message when nothing matches.

**File:** `resources/views/partials/dashboard/sidebar.blade.php`

---

## Task 8: Fix Dashboard Cmd+K Conflict

**Problem:** `app.js` registers a Cmd+K handler for a static command palette. `dashboard.blade.php` also registers Cmd+K for the API-powered search modal. Both fire on dashboard pages, creating two overlapping UIs.

**Fix:** In `app.js`, guard the Cmd+K registration: skip if `[data-search-backdrop]` element exists on the page (indicating dashboard layout is active). Also remove the duplicate `openDashboardSearch` definition from sidebar.blade.php since it's always overwritten by the layout version.

**Files:** `resources/js/app.js`, `resources/views/partials/dashboard/sidebar.blade.php`

---

## Task 9: Add Missing Section Visibility Checkboxes

**Problem:** Homepage has `teachers` and `remarkable_students` sections but the settings page has no checkboxes for them — they always show.

**Fix:** Add entries to the `$sectionLabels` array in `settings.blade.php` and the `$defaults` array in `DashboardModulesController@updateSettings`.

**Files:** `resources/views/dashboard/modules/settings.blade.php`, `app/Http/Controllers/Web/DashboardModulesController.php`

---

## Verification
- `php artisan migrate:fresh --seed` should complete without errors
- `composer test` should pass
- Visit `/dashboard/announcements` — should render without TypeError
- Visit `/dashboard/help` — should render help content
- Visit visitor logs page after browsing — should show entries
- Visit `/dashboard/ledger/cashbook` and `/dashboard/ledger/bankbook` — should render
- Use site search — should return results
- Use sidebar search — should filter nav items
- Press Cmd+K on dashboard — should open only one modal
- Toggle teachers/students checkboxes in settings — should hide/show sections on homepage
