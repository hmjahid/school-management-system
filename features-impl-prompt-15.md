# Features Implementation Prompt 15 — About Page, Hero Fields, Media Popup, Theme Styles, Settings

## Context

Laravel 12 school management system with Blade + Tailwind CSS v4 + Vite. Dashboard views extend `layouts.dashboard`. SQLite in-memory for tests, `database/database.sqlite` for dev.

## Task List

### 1. Remove "About this website software" section from public About page

**Problem**: The About page has an "About this website software" section that should not be on the public site.

**Fix**:
- `database/seeders/WebsiteContentSeeder.php`: Remove the "About this website software" section from the about page content definition.
- Clean dev DB: update the about page `content_en` and `content_bn` to remove the software section.

### 2. Add dedicated About page link in dashboard sidebar

**Problem**: There is no direct link to edit the About page in the dashboard. Admins must navigate through CMS → Pages → About → Edit.

**Fix**:
- `resources/views/partials/dashboard/sidebar.blade.php`: Add an "About Page" link in the Website section that goes directly to `route('dashboard.cms.edit', ['page' => 'about'])`. Place it near the other CMS page links.

### 3. Ensure recent events is above upcoming events on homepage

**Problem**: Verify and ensure the "Recent Events" section renders immediately above "Upcoming Events" on the homepage.

**Fix**:
- `resources/views/home.blade.php`: Verify the order is: Recent Events → Upcoming Events. If already correct, no change needed. The current code has Recent Events at lines 321-351 and Upcoming Events at lines 353-383, which is the correct order.

### 4. Show hero content fields based on selected hero design

**Problem**: All hero content fields (headline, subtitle, CTAs, background image) are always shown in the CMS editor, regardless of which hero design is selected. Some designs don't use all fields (e.g., design-5 uses school name from site settings, not from hero fields).

**Fix**:
- `resources/views/dashboard/cms/edit.blade.php`: Add JavaScript that watches the `hero_design` select field and shows/hides the hero content fields based on the selected design.
  - Design 1-4: Show all hero fields (headline, subtitle, CTAs, background image)
  - Design 5: Show only headline and subtitle (school name comes from site settings, background image is the main image)
  - Design 6: Show headline and subtitle (school name from site settings, slider content comes from slider section)
- The hero section (`<section>` containing the hero kv fields) gets a `data-hero-section` attribute.
- The hero_design select gets a `data-hero-design-select` attribute.
- JS toggles visibility of sub-fields based on the design selection.

### 5. Fix Browse Media popup

**Problem**: The media library iframe popup is broken — when clicking "Browse Media", the opened popup shows the full dashboard layout (sidebar, header) inside the iframe, making it unusable.

**Fix**:
- `resources/views/dashboard/media/index.blade.php`: When `?select=1`, render a minimal layout WITHOUT the dashboard chrome. Instead of `@extends('layouts.dashboard')`, detect select mode and render a standalone page with just the media grid.
- Alternative simpler fix: In select mode, add CSS to hide the dashboard sidebar and header within the iframe: `body { overflow: hidden; } .admin-sidebar, .admin-header { display: none !important; } .admin-content { margin-left: 0 !important; padding: 0 !important; }`
- Also fix the iframe sizing in `resources/views/dashboard/cms/fields/image.blade.php` — the overlay modal needs proper z-index and the iframe needs explicit height.

### 6. Add more theme style options

**Problem**: Theme settings only have primary/secondary colors, font, and border radius. Need more style options to customize the frontend.

**Fix**:
- `resources/views/dashboard/settings/index.blade.php` Theme tab: Add new fields:
  - **Header style**: select (transparent, solid white, solid dark) — affects the site header background
  - **Footer style**: select (dark, light) — affects the footer background
  - **Button style**: select (rounded, square, pill) — affects CTA button shapes
  - **Section spacing**: select (compact, default, spacious) — affects py-* on sections
  - **Hero overlay opacity**: range (0-100) — affects dark overlay on hero images
- `app/Http/Controllers/Web/DashboardSettingController.php` `updateTheme()`: Add validation and save for the new fields.
- `resources/views/layouts/app.blade.php`: Apply the new theme settings as CSS custom properties.
- `resources/views/layouts/site-header.blade.php` or equivalent: Apply header style.
- `resources/views/layouts/site-footer.blade.php` or equivalent: Apply footer style.

### 7. Add more features in general settings page

**Problem**: The general settings page (School Info) could use more configuration options.

**Fix**:
- `resources/views/dashboard/modules/settings.blade.php` (School Info page): Add new sections:
  - **Academic Year**: Start month (select 1-12), start year — for academic session defaults
  - **Student ID Format**: Prefix (e.g., "ADM"), digit count (e.g., 4) — for auto-generating admission numbers
  - **Contact Information**: Additional fields for emergency contact, office hours
  - **Map Coordinates**: Latitude/longitude for Google Maps embed on contact page
- `app/Http/Controllers/Web/DashboardModulesController.php` `updateSettings()`: Add validation for new fields.
- `app/Models/WebsiteSetting`: Ensure new fields are in `$fillable`.

## Files to Modify

| # | File | Change |
|---|---|---|
| 1 | `database/seeders/WebsiteContentSeeder.php` | Remove software section from about |
| 2 | `resources/views/partials/dashboard/sidebar.blade.php` | Add About Page link |
| 3 | `resources/views/home.blade.php` | Verify recent events ordering |
| 4 | `resources/views/dashboard/cms/edit.blade.php` | Hero fields visibility JS |
| 5 | `resources/views/dashboard/media/index.blade.php` | Minimal layout in select mode |
| 6 | `resources/views/dashboard/cms/fields/image.blade.php` | Fix popup z-index/sizing |
| 7 | `resources/views/dashboard/settings/index.blade.php` | New theme options |
| 8 | `app/Http/Controllers/Web/DashboardSettingController.php` | Save new theme fields |
| 9 | `resources/views/layouts/app.blade.php` | Apply new theme CSS vars |
| 10 | `resources/views/dashboard/modules/settings.blade.php` | New general settings fields |
| 11 | `app/Http/Controllers/Web/DashboardModulesController.php` | Save new settings fields |
| 12 | `app/Models/WebsiteSetting.php` | Add new fields to fillable |
| 13 | `lang/en/dashboard.php` | New labels |
| 14 | `lang/bn/dashboard.php` | New labels |
| 15 | `tests/Feature/HomeHeroAndSliderTest.php` | Verify about page, hero fields |

## Verification

```bash
php artisan optimize:clear
# About page: no "About this website software" section
# Dashboard sidebar: "About Page" link goes to CMS editor for about
# Homepage: Recent Events appears above Upcoming Events
# CMS editor: hero fields show/hide based on selected hero design
# CMS image fields: Browse Media popup opens correctly with media grid
# Theme settings: new options (header style, footer style, button style, spacing, overlay)
# General settings: new fields (academic year, student ID format, map coords)
php artisan test --testsuite=Feature --filter=HomeHeroAndSliderTest
```
