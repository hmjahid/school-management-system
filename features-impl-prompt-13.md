# Features Implementation Prompt 13 — Ledger/Media Fixes, Sidebar Restructure, Hero Toggle, About Page, Principal Message

## Context

Laravel 12 school management system with Blade + Tailwind CSS v4 + Vite. Dashboard views extend `layouts.dashboard`. SQLite in-memory for tests, `database/database.sqlite` for dev. Spatie `laravel-permission` for auth.

## Task List

### 1. Fix ledger page error (Class "bill" not found)

**Problem**: Ledger pages (`/dashboard/ledger/cashbook`, `/dashboard/ledger/bankbook`) throw `Class "bill" not found` because `DemoLedgerSeeder` stored short reference type strings (`bill`, `opening_balance`) that `morphTo()` couldn't resolve.

**Fix**:
- `app/Providers/AppServiceProvider.php`: Added `Relation::morphMap()` in `boot()` mapping `fee_payment` → `FeePayment::class` and `expense` → `Expense::class`.
- `database/seeders/DemoLedgerSeeder.php`: Changed reference type values from short strings (`bill`, `opening_balance`) to FQCNs (`FeePayment::class`, `Expense::class`) and `null` for opening balances.
- Cleaned existing dev DB rows (via tinker): set `reference_type = NULL` for all `bill`/`opening_balance` entries.

### 2. Fix media page error (no such table: website_media)

**Problem**: Dashboard media page (`/dashboard/media`) threw `no such table: website_media` because the migration hadn't been run.

**Fix**:
- Ran `php artisan migrate` to apply `2026_07_31_160000_create_website_media_table.php`.
- Verified media page renders correctly.

### 3. Restructure sidebar into top-level Help and Administration groups

**Problem**: Sidebar used `<details>` sub-groups and a separate footer section for Help/Documentation and Admin items.

**Fix**:
- `resources/views/partials/dashboard/sidebar.blade.php`:
  - Replaced `<details>`-style "Administration" sub-group with top-level `<p>` heading + three `x-admin-nav-link` items: **Users & Roles** (new), Roles, Permissions.
  - Replaced footer Help section with a top-level group heading "Help & Documentation" inside the sidebar nav (next to Configuration).
- `lang/en/dashboard.php` + `lang/bn/dashboard.php`: Added `users_and_roles` key.

### 4. Hide hero admission button when admissions are closed

**Problem**: Homepage hero always shows "Apply for admission" button even when admissions are closed.

**Fix**:
- `app/Http/Controllers/Web/HomeController.php`: Added `$admissionsOpen = AdmissionSetting::getSettings()->is_open` and passed it to the view via `View::share()` (or compact).
- `resources/views/partials/site/hero/design-{1,2,3,4}.blade.php`: Each hero design now checks `@if($admissionsOpen)`:
  - **Open**: shows "Apply for admission" → `route('admissions.apply')`
  - **Closed**: shows "Contact us" → `route('site.contact')` using `site_ui('home.cta_contact')`
- Secondary CTA unchanged → `route('site.about')`.

### 5. Add demo slider content

**Problem**: Homepage slider had no demo content seeded.

**Fix**:
- `database/seeders/WebsiteContentSeeder.php`: Added `slider` array to home content with 4 demo slides (photo, title, caption, link) using `https://picsum.photos/seed/{n}/1600/900` URLs. Bengali variants included.
- Applied to existing dev DB via tinker merge (preserving other home content).

### 6. Add education ministry guidance + software details to About page

**Problem**: About page lacked official guidance about school website requirements and no info about the software.

**Fix**:
- `database/seeders/WebsiteContentSeeder.php`: Added two new `sections` under the About page content:
  1. **"Education ministry website guidelines"** — Bullets: institution profile, recognition, class/gender student info, approved sections, academic info & routine, MPO info, contact/phone, info service centre, complaint redressal officer, teachers/staff list, managing committee.
  2. **"About this website software"** — Paragraph describing features: admissions, students/teachers, attendance, routines, assignments, exams/results, fees, library, transport, hostels, notices/news/gallery, CMS-managed content, multilingual.
- Applied to existing dev DB via tinker merge.
- `resources/views/site/partials/sections.blade.php` already renders CMS `sections` — no view changes needed.

### 7. Improve principal-message section layout

**Problem**: Principal message section used a 2-column `aspect-[4/5]` layout that didn't showcase the photo well.

**Fix**:
- `resources/views/home.blade.php`: Replaced the old `lg:grid-cols-2` layout with a centered `lg:grid-cols-5` layout:
  - Photo column (`lg:col-span-2`): `aspect-[4/3] max-w-xs` with rounded-2xl, shadow, badge overlay (name + designation) at bottom.
  - Message column (`lg:col-span-3`): quote card with orange left border, large quote icon, message text, attribution.
- Uploaded CMS photo (`$principal['photo']`) renders when set; falls back to orange gradient placeholder icon.
- Test verified: setting `principal_photo_en` to `/storage/media/principal.jpg` causes `<img src="/storage/media/principal.jpg"` to appear on homepage.

### 8. Create features-impl-prompt-13.md (this file)

## Files Modified

| # | File | Change |
|---|---|---|
| 1 | `app/Providers/AppServiceProvider.php` | morphMap for ledger reference types |
| 2 | `database/seeders/DemoLedgerSeeder.php` | Fix reference_type values |
| 3 | `resources/views/partials/dashboard/sidebar.blade.php` | Top-level Help/Administration groups, Users & Roles |
| 4 | `lang/en/dashboard.php` | `users_and_roles` key |
| 5 | `lang/bn/dashboard.php` | `users_and_roles` key |
| 6 | `app/Http/Controllers/Web/HomeController.php` | Pass `$admissionsOpen` to view |
| 7 | `resources/views/partials/site/hero/design-1.blade.php` | Admission/contact toggle |
| 8 | `resources/views/partials/site/hero/design-2.blade.php` | Admission/contact toggle |
| 9 | `resources/views/partials/site/hero/design-3.blade.php` | Admission/contact toggle |
| 10 | `resources/views/partials/site/hero/design-4.blade.php` | Admission/contact toggle |
| 11 | `database/seeders/WebsiteContentSeeder.php` | Demo slider + About sections |
| 12 | `resources/views/home.blade.php` | Principal message layout, slider include |
| 13 | `tests/Feature/HomeHeroAndSliderTest.php` | Tests for admission toggle, principal photo, about sections |

## Verification

```bash
# Ledger pages render without error
php artisan tinker --execute="..."
# Media page renders (migration applied)
php artisan tinker --execute="..."

# Hero admission toggle
php artisan test --testsuite=Feature --filter="HomeHeroAndSliderTest"
# All 10 tests pass:
#   - cms editor renders hero design select and slider fields
#   - hero design selection persists
#   - homepage renders selected hero design
#   - homepage defaults to design 1
#   - slider save round trip with image upload
#   - homepage renders cms slider slides
#   - hero shows admission button when admissions open
#   - hero hides admission button and shows contact when closed
#   - uploaded principal photo renders on homepage
#   - about page contains ministry guidelines and software details

# Full suite regression check (48 pre-existing failures in Payment/Refund/Gateway/Teacher/Example tests)
php artisan test --testsuite=Feature
```
