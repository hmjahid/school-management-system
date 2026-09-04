# Implementation Prompt — Phase 4

## Context
Laravel 12 school management system at `/home/mdjahidhasan/Documents/GitHub/school-management-system`. Tailwind v4 + Vite. Session-based auth. Spatie Permission for roles. SQLite for dev.

## Tasks

### Task 1: Fix Dashboard Link Issue in Topbar
The topbar user dropdown has Dashboard, Settings, Logout. The issue is that after language change, `back()` redirects may fail. The topbar dropdown links need to work correctly regardless of locale.
- **Check**: `resources/views/partials/dashboard/topbar.blade.php` — the user dropdown menu toggle (hamburger or avatar click). Ensure the dropdown actually opens/closes via Alpine.js or similar.
- **Fix**: If the dropdown JS is missing or broken, add proper Alpine.js toggle behavior.

### Task 2: Fix Website Language Change
The public site language switcher at `resources/views/partials/site/nav.blade.php` uses `route('locale.switch', ['locale' => $loc])`. The `LocaleController` stores session but the issue may be:
- The `SetLocaleFromSession` middleware reads `session('locale')` but the redirect back may not refresh the page properly.
- The site layout uses `__('key')` helpers that depend on `app()->getLocale()`.
- **Fix**: Ensure `LocaleController@switch` also calls `app()->setLocale($locale)` immediately (like `DashboardLocaleController` does), so the redirect response already has the correct locale applied. Also ensure the nav highlights the active locale.

### Task 3: User Profile Page & Settings in Dashboard
Currently `/profile` exists but uses `layouts.app` (public layout). Admin/teacher users need a profile page inside the dashboard layout.
- Create `resources/views/dashboard/profile/edit.blade.php` extending `layouts.dashboard`
- Create `DashboardProfileController` (or reuse existing `ProfileController` with dashboard view)
- Add route `GET /dashboard/profile` → `dashboard.profile.edit` and `PUT /dashboard/profile` → `dashboard.profile.update`
- Add "My Profile" link in the topbar user dropdown (between Dashboard and Settings)
- The profile form should allow: name, email, phone, photo, password change
- Add dashboard translation keys for profile-related strings to `lang/en/dashboard.php` and `lang/bn/dashboard.php`

### Task 4: Website Visitor Log (Admin Only)
Create a system to log every website visitor (page views on the public site).
- Create migration: `visitor_logs` table with columns: `id`, `ip`, `url`, `method`, `user_agent`, `referer`, `user_id` (nullable FK), `created_at`
- Create model `App\Models\VisitorLog`
- Create middleware `App\Http\Middleware\LogVisitor` that logs every non-API, non-asset request
- Register middleware in `bootstrap/app.php` on the `web` group (after SetLocaleFromSession)
- Create `DashboardVisitorLogController` with `index` method (paginated, filterable by date/user)
- Create view `resources/views/dashboard/visitor-logs/index.blade.php` extending `layouts.dashboard`
- Add route `GET /dashboard/visitor-logs` → `dashboard.visitor-logs.index` (admin-only, `role:admin`)
- Add sidebar link "Visitor Logs" under the admin section
- Add translation keys for visitor log strings

### Task 5: Activity Log Dashboard Page Enhancement
Spatie activitylog is already installed and a `DashboardActivityController` exists. Verify:
- The controller at `app/Http/Controllers/Web/DashboardActivityController.php` works correctly
- The view exists and extends `layouts.dashboard`
- The sidebar link `dashboard.activity.index` works
- If the view is missing or broken, create `resources/views/dashboard/activity/index.blade.php`

### Task 6: Move Teachers Section Below Principal Message
In `resources/views/home.blade.php`:
- Currently: Teachers (line ~192) → Stats → Principal's Message (line ~255)
- Target: Stats → Principal's Message → Teachers
- Cut the Teachers section block and paste it after the Principal's Message section block
- Keep all visibility checks (`$sectionVis['teachers']`) intact

## File Reference
- Topbar: `resources/views/partials/dashboard/topbar.blade.php`
- Sidebar: `resources/views/partials/dashboard/sidebar.blade.php`
- Homepage: `resources/views/home.blade.php`
- Public nav: `resources/views/partials/site/nav.blade.php`
- LocaleController: `app/Http/Controllers/Web/LocaleController.php`
- DashboardLocaleController: `app/Http/Controllers/Web/DashboardLocaleController.php`
- SetLocale middleware: `app/Http/Middleware/SetLocaleFromSession.php`
- ProfileController: `app/Http/Controllers/Web/ProfileController.php`
- Dashboard layout: `resources/views/layouts/dashboard.blade.php`
- Routes: `routes/web.php`
- Lang en: `lang/en/dashboard.php`, `lang/en/site_frontend.php`
- Lang bn: `lang/bn/dashboard.php`, `lang/bn/site_frontend.php`
- Activity controller: `app/Http/Controllers/Web/DashboardActivityController.php`

## Conventions
- Controllers in `App\Http\Controllers\Web\`
- Dashboard views extend `layouts.dashboard`
- Site UI text via `site_ui('key')` helper
- Navigation items added in both `lang/en/` and `lang/bn/` lang files
- Sidebar links use `@role('admin')` for admin-only items
- Routes named with dot notation: `dashboard.xxx.yyy`
