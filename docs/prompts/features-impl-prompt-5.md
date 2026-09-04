# Features Implementation Prompt 5

## Context

This is a Laravel 12 school management system using Blade + Tailwind CSS 4 + vanilla JS. The dashboard layout is at `resources/views/layouts/dashboard.blade.php` and the public site layout is at `resources/views/layouts/app.blade.php`. Dark mode is toggled via `data-dark-toggle` buttons and persisted in `localStorage` key `school-dark-mode`. The public site theme colors come from `WebsiteSetting` model (`theme_primary_color`, `theme_secondary_color`) and are injected as CSS custom properties in `layouts/app.blade.php`. The dashboard uses hardcoded CSS variables for brand colors. Language switching uses two separate session keys: `session('locale')` for public site and `session('dashboard_locale')` for dashboard.

---

## Task 1: Fix language change resets theme in dashboard

**Problem:** When a user changes language from the dashboard topbar language switcher, the page reloads and the dark mode theme resets automatically.

**Root cause:** The `DashboardLocaleController@switch` does a redirect. The dark mode is stored in `localStorage` and restored via JS, but the `<html>` element doesn't have the `dark` class initially, causing a flash/reset.

**Fix:**
1. In `resources/views/layouts/dashboard.blade.php`, add an inline `<script>` in the `<head>` section (before any CSS renders) that reads `localStorage.getItem('school-dark-mode')` and applies the `dark` class to `<html>` immediately, preventing the flash.
2. Same fix needed in `resources/views/layouts/app.blade.php` for the public site.
3. The inline script should be:
```html
<script>
(function(){var k='school-dark-mode',v=localStorage.getItem(k);if(v==='1'||(v===null&&matchMedia('(prefers-color-scheme:dark)').matches))document.documentElement.classList.add('dark')})();
</script>
```

---

## Task 2: Fix theme change breaks header links and user settings

**Problem:** After manually changing theme (dark mode toggle), the user settings dropdown, language menu, and other header links stop working.

**Root cause:** The dark mode click handler in `resources/js/app.js` uses event delegation on `document` click. It calls `e.target.closest('[data-dark-toggle]')` which works, but the handler doesn't `stopPropagation()`. Meanwhile, the topbar menus (user menu, language menu) have their own click handlers that close menus when clicking outside. The dark mode toggle is outside both menu roots, so clicking it triggers the close handlers, which is correct. However, the issue is likely that the dark mode toggle button is inside the topbar, and the event propagation chain might interfere.

**Fix:**
1. In the dark mode toggle handler in `app.js`, ensure `e.stopPropagation()` is NOT called (we want propagation for the close handlers).
2. Check that the dark mode toggle button in `topbar.blade.php` doesn't have any conflicting event handlers.
3. Actually, the real fix: Ensure the user menu and language menu click-outside handlers don't accidentally close when the dark mode toggle is clicked. The menus already check `e.target.closest('[data-lang-menu-root]')` and `e.target.closest('[data-user-menu-root]')`, so clicking the dark toggle (which is outside both) will close them. This is correct behavior. The issue might be that after dark mode toggle, the page re-renders and the JS event listeners are lost. Since we're using vanilla JS with `addEventListener`, this shouldn't happen.
4. **Most likely fix:** The issue is that the dark mode toggle in `app.js` runs on `DOMContentLoaded` and adds a click handler. But the topbar is loaded via `@include` and its inline `<script>` runs immediately. If the app.js hasn't loaded yet (Vite bundle), the dark mode toggle might not work. Add the dark mode restore logic as an inline script in `<head>` (already done in Task 1).

---

## Task 3: Ensure dashboard/backend pages respect theme colors

**Problem:** Dashboard and backend pages don't change colors based on theme settings.

**Fix:**
1. In `resources/views/layouts/dashboard.blade.php`, fetch `WebsiteSetting::getSettings()` and use the `theme_primary_color` and `theme_secondary_color` to set the CSS custom properties, similar to how `layouts/app.blade.php` does it.
2. Update the `:root` CSS variables in the dashboard layout to use the database theme colors:
```php
$siteSettings = \App\Models\WebsiteSetting::getSettings();
```
```css
:root {
    --brand-500: {{ $siteSettings->theme_primary_color ?? '#2563eb' }};
    --brand-600: {{ $siteSettings->theme_primary_color ?? '#2563eb' }};
    --brand-700: color-mix(in srgb, {{ $siteSettings->theme_primary_color ?? '#2563eb' }} 85%, black);
    --accent-500: {{ $siteSettings->theme_secondary_color ?? '#f97316' }};
    --accent-600: color-mix(in srgb, {{ $siteSettings->theme_secondary_color ?? '#f97316' }} 85%, black);
}
```
3. Also update the sidebar, topbar, and all dashboard views to use `dark:` variants consistently for dark mode support.

---

## Task 4: Separate website language and dashboard language systems

**Status:** Already implemented! The codebase has:
- `LocaleController` (public site) → `session('locale')`
- `DashboardLocaleController` (dashboard) → `session('dashboard_locale')`

**Remaining work:**
1. Ensure the dashboard language switcher in `topbar.blade.php` uses `route('dashboard.locale.switch')` (already does).
2. Ensure the public site language switcher in `nav.blade.php` uses `route('locale.switch')` (already does).
3. Verify that the dashboard pages read from `session('dashboard_locale')` and public pages read from `session('locale')`. Check if there's a middleware that sets `app()->setLocale()` from the session. If not, create one.
4. Create/update a `SetLocale` middleware that reads both session keys and sets locale appropriately based on the route.

---

## Task 5: Use checkbox for page section show/hide instead of remove button

**Status:** Already implemented! The settings page at `resources/views/dashboard/modules/settings.blade.php` already uses checkboxes for section visibility toggles (lines 265-274).

**Verification:** Confirm no other page uses "remove" buttons for section hiding. If found, replace with checkboxes.

---

## Task 6: Add notice system under News menu as dropdown + hero notice link

**Problem:** Need to add a "Notices" page on the public site, make "News" a dropdown menu item containing "News" and "Notices", and add an "All Notices" link at the bottom of the hero notice panel.

**Implementation:**
1. Create `resources/views/site/notices.blade.php` - a public notices page listing all notices with pagination.
2. Create `App\Http\Controllers\Web\SiteNoticeController.php` with an `index` method.
3. Add route `GET /notices` → `SiteNoticeController@index` named `site.notices` in `routes/web.php`.
4. Update `resources/views/partials/site/nav.blade.php`:
   - Change the standalone News link (line 202) to a dropdown group containing "News" and "Notices".
   - Update the mobile nav similarly.
5. Update `resources/views/home.blade.php`:
   - Add an "All Notices" link at the bottom of the hero notice panel (after line 158).
6. Add lang keys for notices in `lang/en/site_frontend.php` and `lang/bn/site_frontend.php`.

---

## Task 7: Make homepage teachers section a slider, rename Faculty to Teachers

**Problem:** The homepage teachers section is a static grid. Need a slider. Also rename all "Faculty" references to "Teachers".

**Implementation:**
1. In `resources/views/home.blade.php` (lines 255-290):
   - Replace the static grid with a horizontal scrollable slider using CSS `overflow-x: auto` and `scroll-snap`.
   - Add left/right navigation arrows.
   - Add auto-scroll/pause on hover.
   - Pure CSS + vanilla JS implementation (no libraries).
2. Rename "Faculty" to "Teachers" in:
   - `lang/en/site_frontend.php`: Change `nav.faculty` from 'Faculty' to 'Teachers', `home.stats_faculty` from 'Faculty' to 'Teachers', `home.teachers_view_all` from 'View all faculty' to 'View all teachers'.
   - `lang/bn/site_frontend.php`: Update corresponding Bengali translations.
   - `resources/views/site/faculty.blade.php`: Update all "Faculty" references to "Teachers".
   - `resources/views/partials/site/nav.blade.php`: Update nav labels.
   - Keep the route name `site.faculty` for backward compatibility but update the display text.

---

## Task 8: Add profile page for all users

**Problem:** Need a profile page for all users to change password, name, and other information.

**Status:** Profile pages already exist:
- Dashboard profile: `GET /dashboard/profile` → `DashboardProfileController@edit`
- Public profile: `GET /profile` → `ProfileController@edit`

**Improvements needed:**
1. Ensure the dashboard profile page (`resources/views/dashboard/profile/edit.blade.php`) has all necessary fields (name, email, phone, gender, DOB, photo, address, password).
2. Ensure the public profile page (`resources/views/profile/edit.blade.php`) has the same fields.
3. Add profile links in the dashboard sidebar and topbar user menu (already present in topbar).
4. Add a profile link in the public site header/nav for logged-in users.
5. Ensure the `DashboardProfileController@update` handles all fields including password change.

---

## Implementation Order

1. Task 1 (fix theme flash on language change) - Quick inline script fix
2. Task 2 (fix header links after theme change) - Verify and fix JS handlers
3. Task 3 (dashboard theme colors) - Update dashboard layout CSS vars
4. Task 4 (separate language systems) - Add/update locale middleware
5. Task 5 (checkbox for sections) - Verify already done
6. Task 6 (notices system) - New controller, view, routes, nav updates
7. Task 7 (teachers slider + rename) - Homepage slider + rename Faculty
8. Task 8 (profile page) - Verify and improve existing profile pages
