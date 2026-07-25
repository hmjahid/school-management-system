# Prompt for Implementing Dashboard and Frontend Features

You are working on a Laravel 12 School Management System with Blade templating and Tailwind CSS 4.

## Critical Context
- Laravel 12 application with Blade views in `resources/views/`
- Dashboard views: `resources/views/dashboard/`
- Public site views: `resources/views/site/`
- Controllers: `App\Http\Controllers\Dashboard\` for admin, `App\Http\Controllers\Site\` for public
- Models: `app/Models/`
- Migrations: `database/migrations/`
- Routes: `routes/web.php` (web), `routes/api.php` (JSON)
- Lang files: `lang/en/dashboard.php`, `lang/bn/dashboard.php`, `lang/en/site_frontend.php`, `lang/bn/site_frontend.php`
- Multi-language support with `site_ui()` helper for public site text
- Tailwind CSS 4 utility classes only via `@tailwindcss/vite` plugin
- Dashboard uses Livewire components where available

## Tasks to Implement (in priority order)

### 1. Calendar Page in Dashboard
Create a calendar page showing:
- School activities (academic events, exams, etc.)
- Government holidays
- Upcoming events
- Make accessible to all dashboard users

### 2. Public Site Section Visibility Control
Add activation/deactivation feature for every public site section:
- Hero section
- About section
- Gallery section
- Notice section
- Contact section
- etc.
Store settings in database or config file, add toggle switches in dashboard.

### 3. Fix CMS Navigation Jumping Issue
Fix page jumping when clicking on website CMS items (gallery, all pages, etc.).

### 4. SMS Notification System
Setup basic SMS notification system for:
- Student attendance alerts
- Exam results
- General announcements
Integrate with a provider (configurable).

### 5. Student Filter in Dashboard
Add filters for:
- Class selection
- Batch selection
On the dashboard students page.

### 6. Organize Academic Sidebar
Group academic sidebar items into subgroups and make the sidebar more organized.

### 7. Documentation/Help Section
Add a doc/help sidebar item with:
- User documentation for the management application
- Public website guidelines

### 8. Principal Image and Message
Add options for:
- Uploading principal image
- Displaying principal message on homepage (both EN and BN)

### 9. Dashboard Language Switcher
Add language change option in dashboard for EN/BN.

### 10. Enhanced Hero Section
Make homepage hero section more attractive with:
- School name in large font with design options
- Notice section on right side
- Marquee for urgent notices
- Notice page in dashboard for normal and urgent notices

### 11. Fix Application Status Bug
Fix issue where application status shows in public website header despite unchecking "accepting applications".

### 12. Fix Bulk Attendance
Fix bulk attendance functionality that's not working.

### 13. Fix Dashboard Search
Fix dashboard header search button and ensure search works by clicking input field (not ctrl+k).

### 14. Organize Unnecessary Files
Move all unnecessary files to `unnecessary-files/` folder.

### 15. Responsive Navigation
Make public website responsive navigation menu more arranged and attractive.

### 16. Overall Testing
Ensure all features and functionalities are working perfectly.

## Implementation Guidelines

1. Always check existing code structure before implementing
2. Use existing patterns and conventions
3. Add database migrations for new features
4. Update lang files for both EN and BN
5. Create proper routes and controllers
6. Add validation and error handling
7. Test each feature after implementation
8. Ensure responsive design for all new UI
9. Follow Laravel best practices
10. Document any significant changes

## Testing Commands
```bash
composer test
php artisan migrate:fresh --seed
```

Please implement these tasks one by one, starting with the highest priority items. After each implementation, verify it works and move to the next task.
