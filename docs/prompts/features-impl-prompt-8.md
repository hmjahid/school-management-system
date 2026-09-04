# Features Implementation Prompt — Batch 8

## Task 1: Add Show/Hide for All Individual Sections (Not Whole Page Content)

### Problem
Currently, the section visibility system covers homepage sections and other page sections (Admissions, Contact, Faculty, Gallery, News, Payments, Events, Notices, Results, Routines, Transport, About/Pages). However, some sections on the portal page and search page lack individual toggles. The goal is to ensure **every** distinct section on **every** public-facing page has its own visibility toggle in the admin settings.

### Current State
- `database/migrations/2026_07_25_000004_add_section_visibility_to_website_settings.php` — adds `section_visibility` JSON column
- `app/Models/WebsiteSetting.php` — casts `section_visibility` to `array`
- `resources/views/dashboard/modules/settings.blade.php` — admin toggles for homepage (14 sections) and other pages (50+ sections)
- All major public pages already wrap sections in `@if($siteSettings->section_visibility['key'] ?? true)` blocks

### Implementation
1. **Audit all public pages** in `resources/views/site/` and `resources/views/home.blade.php` for sections that lack `section_visibility` checks.
2. **Add missing section keys** to `$otherPageSections` in `resources/views/dashboard/modules/settings.blade.php` for any uncovered pages/sections.
3. **Add corresponding `@if` guards** in each Blade template for the new keys.
4. **Update defaults** in the controller (`DashboardModulesController@updateSettings`) and the settings Blade file to include the new keys (default `true`).
5. **Ensure the portal page** (`resources/views/site/portal.blade.php`) sections are individually toggleable via the settings.

### Files to Modify
- `resources/views/dashboard/modules/settings.blade.php` — add missing section toggles
- `resources/views/site/portal.blade.php` — wrap sections in visibility checks
- `app/Http/Controllers/Web/DashboardModulesController.php` — update defaults array in `updateSettings()`
- Any other `resources/views/site/*.blade.php` files missing visibility checks

---

## Task 2: Fix Theme Color Changing Not Working

### Problem
The theme color changing does not work on the **public site**. The root cause is a CSS variable naming mismatch:

- **Public site layout** (`resources/views/layouts/app.blade.php`) defines CSS variables as `--theme-primary`, `--theme-secondary`, `--theme-font`, `--theme-radius`.
- **Dashboard layout** (`resources/views/layouts/dashboard.blade.php`) defines CSS variables as `--brand-500`, `--brand-600`, `--brand-700`, `--accent-500`, `--accent-600` using `color-mix()`.
- **Tailwind theme** (`resources/css/app.css`) maps `--color-brand-500` → `var(--brand-500, oklch(...))` and `--color-accent-500` → `var(--accent-500, oklch(...))`.

Since the public site layout never sets `--brand-500` / `--accent-500`, all Tailwind `brand-*` and `accent-*` utility classes fall back to the hardcoded `oklch()` defaults. Only the custom `.theme-bg-primary` / `.theme-text-primary` etc. classes work.

### Implementation
1. **Add brand/accent CSS variables** to `resources/views/layouts/app.blade.php` `<style>` block, computed from `theme_primary_color` and `theme_secondary_color` using `color-mix()`, matching the pattern used in `dashboard.blade.php`.
2. Keep the existing `--theme-*` variables for backward compatibility with `.theme-bg-primary` etc.
3. The `<style>` block in `app.blade.php` should include:
   ```css
   :root {
       /* Existing theme-* variables */
       --theme-primary: {{ $siteSettings->theme_primary_color ?? '#2563eb' }};
       --theme-secondary: {{ $siteSettings->theme_secondary_color ?? '#f97316' }};
       --theme-font: {{ $siteSettings->theme_font_family ?: "'Inter', sans-serif" }};
       --theme-radius: {{ $siteSettings->theme_border_radius ?: '0.75rem' }};

       /* Brand/accent variables for Tailwind utilities */
       --brand-50: color-mix(in srgb, {{ $siteSettings->theme_primary_color ?? '#2563eb' }} 10%, white);
       --brand-100: color-mix(in srgb, {{ $siteSettings->theme_primary_color ?? '#2563eb' }} 20%, white);
       --brand-500: {{ $siteSettings->theme_primary_color ?? '#2563eb' }};
       --brand-600: color-mix(in srgb, {{ $siteSettings->theme_primary_color ?? '#2563eb' }} 80%, black);
       --brand-700: color-mix(in srgb, {{ $siteSettings->theme_primary_color ?? '#2563eb' }} 65%, black);
       --accent-500: {{ $siteSettings->theme_secondary_color ?? '#f97316' }};
       --accent-600: color-mix(in srgb, {{ $siteSettings->theme_secondary_color ?? '#f97316' }} 80%, black);
   }
   ```

### Files to Modify
- `resources/views/layouts/app.blade.php` — add `--brand-*` and `--accent-*` CSS variables to the `<style>` block

---

## Task 3: Ensure the Application is a PWA (Progressive Web App)

### Problem
The application has **no PWA support**: no `manifest.json`, no service worker, no PWA meta tags.

### Implementation

#### 3a. Create `public/manifest.json`
A standard W3C web app manifest with:
- `name` and `short_name` from `siteSettings->school_name` (rendered via Blade in a new route or generated statically)
- `start_url`: `/`
- `display`: `standalone`
- `background_color` and `theme_color` from theme primary color
- `icons` array referencing generated icons (use a default SVG icon or reference the school logo)
- Since the manifest must be a static file (can't be Blade), create a simple static `public/manifest.json` with sensible defaults. The dynamic values (theme color, name) can be injected via a `<link rel="manifest">` pointing to a route that generates the manifest dynamically.

**Better approach**: Create a route `GET /manifest.json` that returns a JSON response with dynamic values from `WebsiteSetting`, and update the `<link rel="manifest">` in the layout to point to this route.

#### 3b. Create a Service Worker
Create `public/sw.js` (or serve via a route) with:
- Cache-first strategy for static assets (CSS, JS, images)
- Network-first for HTML pages
- Offline fallback page
- Use a simple cache name with versioning

#### 3c. Add PWA Meta Tags to Layouts
In both `resources/views/layouts/app.blade.php` and `resources/views/layouts/dashboard.blade.php`:
- `<link rel="manifest" href="/manifest.json">`
- `<meta name="theme-color" content="...">` from theme primary color
- `<meta name="apple-mobile-web-app-capable" content="yes">`
- `<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">`
- `<link rel="apple-touch-icon" href="...">`

#### 3d. Register the Service Worker
Add to both layouts (before closing `</body>` or via `app.js`):
```js
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js');
}
```

#### 3e. Create Offline Fallback
Create a simple `public/offline.html` page for when the user is offline.

### Files to Create/Modify
- **Create**: `app/Http/Controllers/Web/ManifestController.php` — generates manifest.json dynamically
- **Create**: `public/sw.js` — service worker file
- **Create**: `public/offline.html` — offline fallback page
- **Create**: `resources/views/site/manifest.blade.php` — not needed if using controller
- **Modify**: `routes/web.php` — add route for `/manifest.json`
- **Modify**: `resources/views/layouts/app.blade.php` — add PWA meta tags and SW registration
- **Modify**: `resources/views/layouts/dashboard.blade.php` — add PWA meta tags and SW registration

### PWA Manifest Route
```php
Route::get('/manifest.json', [ManifestController::class, 'index'])->name('site.manifest');
```

### Service Worker Strategy
- **Static assets** (`/build/*`, images, fonts): Cache-first with network fallback
- **HTML pages**: Network-first with cache fallback
- **API calls**: Network-only (don't cache API responses)
- **Offline fallback**: Serve `offline.html` when both network and cache miss

---

## Verification Checklist
- [ ] All public page sections have individual show/hide toggles in admin settings
- [ ] Toggling a section off in settings hides it on the public site
- [ ] Theme color changes in settings immediately affect the public site (all brand-* and accent-* classes)
- [ ] Theme font and border radius changes work on the public site
- [ ] `manifest.json` is accessible at `/manifest.json` with correct content
- [ ] Service worker registers successfully (check browser DevTools > Application)
- [ ] App can be installed (check browser install prompt)
- [ ] Offline mode works (disconnect network, navigate to cached pages)
- [ ] No console errors related to PWA or theme
