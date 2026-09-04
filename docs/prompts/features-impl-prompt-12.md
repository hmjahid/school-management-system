# Features Implementation Prompt 12 — Navigation Groups, Settings Cleanup, Media, Slider & Hero Designs

## Context

We're working on a Laravel 12 school management system with Blade + Tailwind CSS v4 + Vite. Dashboard views extend `layouts.dashboard`. Spatie `laravel-permission` for auth, Spatie `laravel-activitylog` for logs. SQLite.

## Task List

### 1. Add "Help" and "Administration" sidebar groups

**Problem**: The sidebar has "User Management" and "Help/Documentation" groups scattered at the bottom. We want two clearly-named groups: "Administration" (contains User Management links) and "Help" (contains Help/Documentation links).

**Fix**:
- In `resources/views/partials/dashboard/sidebar.blade.php`:
  - Rename/replace the "User Management" group header with "Administration". Keep the same links (Users, Roles, Permissions).
  - Rename/replace the "Help/Documentation" group header with "Help". Keep the same links.
  - Use the existing `$dropdownGroup()` helper pattern for each group; keep translations for the new group labels.

### 2. Remove admission tab from general settings page

**Problem**: The general settings page (`route('dashboard.settings.general')` → `resources/views/dashboard/settings/index.blade.php`) has tabs: Theme, Localization, Payment, Library, Admission. The Admission tab should be removed — the settings that were under it are no longer needed there (theme, localization, payment, library stay).

**Fix**:
- In `resources/views/dashboard/settings/index.blade.php`: remove the Admission tab button and its `@if($tab === 'admission') … @endif` panel.
- In `app/Http/Controllers/Web/DashboardSettingController.php` (or whichever controller renders that view): remove the admission-related logic/redirect target if present, and ensure saving other tabs doesn't redirect to the removed `?tab=admission`.

### 3. Add "Payment Configuration" button to payment page

**Problem**: Fee payments page (`resources/views/dashboard/fee-payments/index.blade.php`) has no link to payment settings, which live at `route('dashboard.settings.general', ['tab' => 'payment'])`.

**Fix**:
- In `resources/views/dashboard/fee-payments/index.blade.php`: in the page header (first `<div>` block), add a link/button "Payment Configuration" → `route('dashboard.settings.general', ['tab' => 'payment'])` using the same button style as other header actions on the page.

### 4. Fix activity logs not being logged

**Problem**: The dashboard Activity Log page shows nothing. Root cause: the app uses TWO activity tables — Spatie's `activity_log` (created by `database/migrations/2026_07_10_191620_create_activity_log_table.php` etc.) and a custom `activities` table (created by `database/migrations/2025_10_13_171213_create_activities_table.php`, mapped by `App\Models\Activity`). The dashboard controller queries the custom `App\Models\Activity`, which is empty because models actually log to Spatie's `activity_log`.

**Fix**:
- `app/Http/Controllers/Web/DashboardActivityController.php`: query `Spatie\Activitylog\Models\Activity` (`\Spatie\Activitylog\Models\Activity::with('causer')->latest()->paginate(...)`) instead of the custom `App\Models\Activity`.
- `resources/views/dashboard/activity/index.blade.php`: update column references — it uses `user_id` which doesn't exist on Spatie's table; use `causer` (name/email) and `created_at`. Show `description`, `event` (e.g. `updated`), and `properties` (JSON) columns.
- Ensure real logging actually happens: the relevant models use `LogsActivity` / `LogsModelActivity` (Spatie), and the `activity_log` table exists. If any custom `Activity::create([...])` calls exist in the codebase (e.g. in `CmsWebController` or elsewhere), keep them working against Spatie's model or the dashboard log as a single source of truth.

### 5. Fix ledger page error

**Problem**: Ledger pages (`/dashboard/ledger/{type}`) throw an error.

**Fix (already in working tree, verify & keep)**:
- `resources/views/dashboard/ledger/_ledger-table.blade.php` (new partial) renders the shared ledger table.
- `resources/views/dashboard/ledger/cashbook.blade.php` and `bankbook.blade.php` include the partial.
- Verify `app/Http/Controllers/Web/DashboardLedgerController.php` passes the right data and that both `?type=cash` and `?type=bank` pages render without error. Keep `tests/Feature/TempLedgerCheckTest.php` (or rename to a proper regression test) asserting the ledger pages return 200.

### 6. Fix backups not showing on backup page

**Problem**: Backup page (`/dashboard/backups`) shows no backups even after running `php artisan backup:run`.

**Fix (already in working tree, verify & keep)**:
- `app/Console/Commands/BackupRunCommand.php`: writes backup zip to `Storage::disk('local')->path('backups')` → `storage/app/private/backups/`.
- `app/Http/Controllers/Web/DashboardBackupController.php`: lists `Storage::disk('local')->files('backups')`.
- `app/Console/Commands/BackupRestoreCommand.php`: reads from the same `local` disk `backups` directory.
- Verify end-to-end: `php artisan backup:run` then visit `/dashboard/backups` — the zip must appear in the list with size/date, and download must work.

### 7. Fix gallery page tab filtering

**Problem**: Public gallery page (`resources/views/site/gallery.blade.php`) has category tabs but clicking them does not filter the photos.

**Fix (partially in working tree, verify & complete)**:
- `resources/js/app.js` already has a `[data-filter-tabs]` handler (uncommitted) that filters grid items by `data-filter` matching the tab's `data-filter`.
- Verify the gallery view sets `data-filter-tabs` on the tab container and `data-filter` on each grid item (matching its category), and a wrapping element with a class/id the JS targets. Wire up attributes if missing.
- Also ensure the CMS "Gallery" page (`dashboard.gallery` under Website) still saves categories the same way the public page reads them.

### 8. Create a Media page

**Problem**: There is no centralized media library. CMS image fields only accept pasted URLs.

**Fix**:
- New `App\Models\WebsiteMedia` model + migration (`media` table: `category`, `title`, `file_path`, `file_type`, `file_size`, `created_at`).
- New controller `App\Http\Controllers\Web\DashboardMediaController` (index with category filter + search, store, destroy, download). Gate with a permission or `hasRole('admin')` like other document/media controllers.
- New routes in `routes/web.php` inside the authenticated dashboard group: `dashboard.media.index`, `dashboard.media.store`, `dashboard.media.destroy`, `dashboard.media.download`.
- Views: `resources/views/dashboard/media/index.blade.php` (upload form with file input + category + title; grid/table of media with thumbnail for images, delete + copy-URL buttons). Add "Media" to the sidebar Website section.
- Publicly serve via `Storage::disk('public')` (`storage/app/public/media/...`, symlink). Add `manage_media` permission to `database/seeders/RolePermissionSeeder.php` and assign to admin.

### 9. Make CMS uploads save directly (instead of URL)

**Problem**: CMS image fields (`resources/views/dashboard/cms/fields/image.blade.php`) only accept a URL string. Admins must host images elsewhere.

**Fix**:
- `resources/views/dashboard/cms/fields/image.blade.php`: change the input to accept BOTH a file upload and a URL. Add `type="file"` input plus a `type="url"` fallback input; keep the preview `<img>`.
- `resources/views/dashboard/cms/edit.blade.php`: add `enctype="multipart/form-data"` to the `<form>`.
- `app/Http/Controllers/Web/CmsWebController.php` `update()`: when a section is an image field and the request has a file, store it (`Storage::disk('public')->putFile('media', $file)`), create a `WebsiteMedia` row, and save `Storage::url($path)` as the field value so existing rendering (URL string) keeps working. Keep the hero/principal `photo` handling consistent.

### 10. Add CMS-manageable slider section on homepage

**Problem**: Homepage has a hero but no slider showcasing school photos/news.

**Fix**:
- Add a `slider` config section to `config/cms_pages.php` for the `home` page (repeater of slide items: image, heading, subheading/description, link).
- `resources/views/home.blade.php`: after the hero, render a slider section when content exists (`$homeContent->content['slider']`), showing recent activity/event photos by default (fall back to the `homeContent` slides). Simple CSS/JS-only carousel (no new npm deps) or a fade/track slider.
- Make slides CMS-editable through the existing CMS page editor (`/dashboard/cms/pages/home/edit`).

### 11. Add 4 hero section designs with admin selection

**Problem**: Homepage hero is fixed. Admins should pick between 4 hero designs.

**Fix**:
- Add a `hero_design` select in the homepage CMS editor (or settings) with options `classic`, `split`, `overlay`, `gradient` (each = a different Blade layout snippet rendered from `home.blade.php`).
- Create 4 hero partials, e.g. `resources/views/site/hero/classic.blade.php`, `split.blade.php`, `overlay.blade.php`, `gradient.blade.php`, all receiving `$hero` (image + heading + subheading).
- `resources/views/home.blade.php`: `@include('site.hero.' . ($homeContent->content['hero_design'] ?? 'classic'), ['hero' => ...])`.
- Store selection in the homepage CMS content (`hero_design` key) so it's editable in the CMS editor and survives saves.

## Files to Modify

| # | File | Change |
|---|---|---|
| 1 | `resources/views/partials/dashboard/sidebar.blade.php` | Administration + Help groups |
| 2 | `resources/views/dashboard/settings/index.blade.php` | Remove admission tab |
| 3 | `app/Http/Controllers/Web/DashboardSettingController.php` | Remove admission redirects/logic |
| 4 | `resources/views/dashboard/fee-payments/index.blade.php` | Payment Configuration button |
| 5 | `app/Http/Controllers/Web/DashboardActivityController.php` | Query Spatie Activity |
| 6 | `resources/views/dashboard/activity/index.blade.php` | Use `causer`/`created_at`/`properties` |
| 7 | `resources/views/dashboard/ledger/*.blade.php` | Ledger partial (verify) |
| 8 | `app/Console/Commands/BackupRunCommand.php` + `app/Http/Controllers/Web/DashboardBackupController.php` | Backup list (verify) |
| 9 | `resources/views/site/gallery.blade.php` | data-filter wiring (verify) |
| 10 | `app/Models/WebsiteMedia.php` (new) + migration | Media model/table |
| 11 | `app/Http/Controllers/Web/DashboardMediaController.php` (new) + routes + views | Media CRUD |
| 12 | `resources/views/dashboard/cms/fields/image.blade.php` + `cms/edit.blade.php` + `app/Http/Controllers/Web/CmsWebController.php` | CMS direct uploads |
| 13 | `config/cms_pages.php` | Slider + hero_design for home |
| 14 | `resources/views/home.blade.php` + `resources/views/site/hero/*.blade.php` (new) | Slider + 4 hero designs |
| 15 | `database/seeders/RolePermissionSeeder.php` | `manage_media` permission |

## Verification

```bash
php artisan optimize:clear
# Sidebar shows "Administration" and "Help" groups with correct links
# /dashboard/settings/general → tabs: Theme, Localization, Payment, Library (no Admission)
# /dashboard/fee-payments → Payment Configuration button opens ?tab=payment
# /dashboard/activity → shows real entries (perform an edit first), no errors
# /dashboard/ledger?type=cash and ?type=bank → render without error
# /dashboard/backups → lists backup zips after `php artisan backup:run`
# Public /gallery → tabs filter photos
# /dashboard/media → upload a file, see it listed, copy URL, delete
# CMS page editor → upload image saves file to /storage/media/... and renders on public page
# Homepage → slider section renders + hero design changes when hero_design changes
php artisan test --testsuite=Feature
```
