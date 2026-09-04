# Features Implementation Prompt 11 — Bugfixes & UI Improvements

## Context

We're working on a Laravel 12 school management system with Blade + Tailwind CSS v4 + Vite. Dashboard views extend `layouts.dashboard`. Spatie `laravel-permission` for auth. SQLite.

## Task List

### 1. Fix dashboard theme toggle (dark mode)

**Bug**: The dark mode `data-dark-toggle` buttons (in topbar + sidebar) toggle the class, but then the app.js handler fires a SECOND time and toggles it back, effectively undoing the user's action.

**Root cause**: Two identical capture-phase click listeners exist — one inline in `dashboard.blade.php` lines 14-20, and another in `resources/js/app.js` lines 447-470. Both listen on `document` with capture phase true. The inline one fires first (added to DOM first), toggling dark ON. Then app.js fires and toggles it OFF again.

**Fix**: Remove the duplicate handler from `resources/js/app.js` (lines 447-470). Keep only the inline `<head>` handler in `dashboard.blade.php` and `app.blade.php`.

### 2. Fix user roles & permissions pages (403 error)

**Bug**: Navigating to Roles or Permissions pages shows 403 / error because `manage_roles` and `manage_permissions` permissions don't exist in the DB.

**Root cause**: `DashboardRoleController` uses `$this->authorize('manage_roles')` and `DashboardPermissionController` uses `$this->authorize('manage_permissions')`. These throw `AuthorizationException` when Spatie can't find the permission in the DB.

**Fix**: Add `'manage_roles' => 'Manage roles (full access)'` and `'manage_permissions' => 'Manage permissions (full access)'` to the permissions array in `database/seeders/RolePermissionSeeder.php`. Re-seed.

### 3. Move School Settings link to Website group in sidebar

**Problem**: Currently "School Settings" is under the "Configuration" section in the sidebar. It should be moved to the "Website" section for better organization.

**Fix**: 
- In `resources/views/partials/dashboard/sidebar.blade.php`:
  - Remove the School Settings link (`route('dashboard.settings.index')`) from the Configuration section (line 338)
  - Add it as a link under the Website CMS dropdown in the Website section, after the "Form Submissions" link (around line 315)
  - Keep Reports and Bulk Import/Export in the Configuration section

### 4. Add section filter to students list page

**Improvement**: The students list (`/dashboard/students`) has class and batch filters. Add a section filter as well.

**Fix**:
- In `app/Http/Controllers/Web/DashboardModulesController.php` `students()` method (line 25):
  - Add `if ($sectionId = $request->integer('section_id')) { $query->where('section_id', $sectionId); }` after the batch filter
  - Pass `$sections = \App\Models\Section::orderBy('name')->get();` to the view
- In `resources/views/dashboard/modules/students.blade.php`:
  - Add a Section select dropdown between the Batch filter and the Filter button

### 5. Fix send message page — target selection first, then individual user

**Problem**: The compose message page (`/messages/create`) shows a flat dropdown of ALL possible recipients, which is overwhelming. Need a two-step selection: choose a role first, then select a specific user.

**Fix**:
- In `app/Http/Controllers/Web/MessageController.php` `create()`:
  - Fetch all roles and pass them to the view
  - Fetch all users grouped by role for JS filtering
  - OR fetch all users with their roles attached; the view will handle filtering
- In `resources/views/messages/create.blade.php`:
  - Add a "Target role" radio/select group first (Admin, Teacher, Staff, Student, Guardian, etc.)
  - Then show a filtered user dropdown that updates via JS when the role changes
  - Keep the Subject and Message body fields the same

### 6. Make bulk SMS recipient field more concise

**Problem**: The bulk SMS compose page has only broad audience types (everyone, by class, by section, staff only). Need more granular control:
- Separate Student and Staff recipients
- Allow individual user selection per role
- Add an "All website users" option

**Fix**:
- In `resources/views/dashboard/sms/compose.blade.php`:
  - Redesign the Audience selector to have clear sections:
    1. "Send to All" option (all website users with phones)
    2. "Students" section: By class, By section, or Individual student select (via JS role→user filter)
    3. "Staff" section: By role (teacher/staff/admin), or Individual staff select
  - Make recipient field concise using a tabbed or radio-button layout
- In `app/Http/Controllers/Web/DashboardSmsController.php`:
  - Update `resolveRecipients()` to handle new audience types: `all_users`, `students_class`, `students_section`, `students_individual`, `staff_role`, `staff_individual`
  - Update `preview()` validation to accept new audience types
  - Pass roles and users to compose view for individual selection

## Files to Modify

| # | File | Change |
|---|---|---|
| 1 | `resources/js/app.js` | Remove dark mode handler (lines 447-470) |
| 2 | `database/seeders/RolePermissionSeeder.php` | Add `manage_roles`, `manage_permissions` |
| 3 | `resources/views/partials/dashboard/sidebar.blade.php` | Move settings link to Website section |
| 4 | `app/Http/Controllers/Web/DashboardModulesController.php` | Add section filter to students() |
| 5 | `resources/views/dashboard/modules/students.blade.php` | Add section dropdown |
| 6 | `app/Http/Controllers/Web/MessageController.php` | Pass roles for target selection |
| 7 | `resources/views/messages/create.blade.php` | Two-step recipient selection |
| 8 | `resources/views/dashboard/sms/compose.blade.php` | Redesigned audience selector |
| 9 | `app/Http/Controllers/Web/DashboardSmsController.php` | Handle new audience types |

## Verification

```bash
php artisan optimize:clear
# Visit dashboard → check dark mode toggles work
# Visit /dashboard/roles → should load without 403
# Visit /dashboard/permissions → should load without 403
# Visit /dashboard/students → should have section filter
# Visit /messages/create → should have role→user two-step
# Visit /dashboard/sms/compose → should have concise recipient selection
```
