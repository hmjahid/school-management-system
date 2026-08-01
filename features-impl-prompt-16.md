# Features Implementation Prompt #16

## Tasks

### 1. Remove Register Button from Header

**Issue:** The site header shows a "Register" button for guest users that should be removed.

**Files:**
- `resources/views/partials/site/nav.blade.php`

**Changes:**
- Remove the register button from the **desktop nav** (`@else` block, ~line 236): remove the `<a href="{{ route('portal.register') }}">` element
- Remove the register button from the **mobile bottom action bar** (`@else` block, ~line 424): remove the `<a data-site-nav-link href="{{ route('portal.register') }}">` element
- Keep the login button in both locations

---

### 2. Add Select All / Deselect All Buttons

**Issue:** User create page and roles create/edit pages have permission checkboxes but no bulk select/deselect controls.

**Files:**
- `resources/views/dashboard/users/create.blade.php` — permissions section (~line 47)
- `resources/views/dashboard/roles/create.blade.php` — permissions groups (~line 29)
- `resources/views/dashboard/roles/edit.blade.php` — permissions groups (~line 29)

**Changes:**
- Add a "Select All" and "Deselect All" button row above the permissions checkboxes
- For grouped permissions (roles pages): add per-group select all/deselect all AND a global select all/deselect all
- Add simple JS to toggle all checkboxes within the group (or all groups for global)
- Use Tailwind styled buttons consistent with the existing UI

---

### 3. Fix Bulk SMS Individual Students Not Showing

**Issue:** In the bulk SMS compose page, individual students don't appear in the list.

**File:** `app/Http/Controllers/Web/DashboardSmsController.php` (~line 41)

**Root cause:** The query uses `whereNotNull('phone_1')` which excludes students who don't have `phone_1` set but may have `father_phone` or `mother_phone`.

**Changes:**
- Change the query to include students who have at least one phone number available:
  ```php
  ->where(function ($q) {
      $q->whereNotNull('phone_1')
        ->orWhereNotNull('father_phone')
        ->orWhereNotNull('mother_phone');
  })
  ```
- Update the `phone` mapping to use the fallback logic (already correct: `$s->phone_1 ?: $s->father_phone ?: $s->mother_phone`)
- Exclude students where the resolved phone is null (no phone at all)

---

### 4. Fix Activity Log Not Recording

**Issue:** The activity log shows nothing because there are two separate activity systems that don't talk to each other.

**Root cause:**
- Spatie's `LogsActivity` trait writes to `activity_log` table via `Spatie\Activitylog\Models\Activity`
- The dashboard controllers read from a custom `App\Models\Activity` model which uses a different `activities` table
- These are different tables with different schemas

**Files to fix:**
- `app/Models/Activity.php` — Make this model read from `activity_log` table (Spatie's table) OR create a bridge
- `app/Http/Controllers/Web/DashboardActivityController.php` — Update query to use Spatie's Activity model or adapt to the custom model's schema
- `app/Services/DashboardService.php` — Same fix

**Approach:** Update `App\Models\Activity` to use Spatie's `activity_log` table and adapt the controller queries to work with Spatie's schema (which has `description`, `subject_type`, `subject_id`, `causer_type`, `causer_id`, `properties` columns instead of `title`, `message`, `icon`, `color`).

Alternatively, create a simple observer or use the existing `LogsActivity` traits but ensure the dashboard reads from the same table. The simplest fix is to update the custom Activity model to point to the `activity_log` table and map the fields correctly in the controller.

---

### 5. Add Committee Page to CMS > All Pages

**Issue:** The committee page doesn't appear in the CMS > All Pages list.

**Root cause:** The `CmsWebController::pages()` method only shows pages that have a `WebsiteContent` database record. The committee page is registered in `config/cms_pages.php` but never seeded.

**File:** `database/seeders/WebsiteContentSeeder.php`

**Changes:**
- Add a `committee` entry to the seeder so the `WebsiteContent` record exists
- This ensures the page appears in the CMS list when `php artisan db:seed` is run
- Also ensure the `committee_members` section visibility key is added to the homepage CMS settings for show/hide control

---

### 6. Add More Settings Options and Ensure All Label Configurations Are Controlled

**Issue:** The settings page needs more configuration options and all setting label configurations should be manageable from the settings page.

**Files:**
- `resources/views/dashboard/settings/cms.blade.php` — Add missing section visibility toggles
- `app/Models/WebsiteSetting.php` — Ensure all new settings are fillable
- `resources/views/dashboard/settings/index.blade.php` — Add more setting tabs/options

**Changes:**
- Add `committee_members` to the homepage section visibility toggles in CMS settings
- Add `slider` section visibility toggle if missing
- Add any other missing section visibility controls
- Ensure the settings page covers: school info, theme, localization, payment, library, SMS, and any new configuration options needed
