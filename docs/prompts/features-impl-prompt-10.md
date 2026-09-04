# Feature Implementation Prompt — Batch 10

## Context

You are working on a Laravel 12 school management system with:
- Blade + Tailwind CSS v4 + Vite frontend
- Spatie `laravel-permission` for roles/permissions
- Sanctum for API auth
- SQLite default DB
- English (en) + Bengali (bn) localization via `site_ui()` helper for public, `__()` for dashboard
- Models in `App\Models`, Web controllers in `App\Http\Controllers\Web\`, API in `App\Http\Controllers\Api\`
- Dashboard views in `resources/views/dashboard/`, layouts in `resources/views/layouts/`
- Partial views in `resources/views/partials/`
- Routes in `routes/web.php` (Blade), `routes/api.php` (JSON)

## Existing infrastructure

- **Roles**: admin, teacher, student, parent, accountant, librarian — already seeded
- **Permissions**: ~80+ permissions across user management, academic, finance, library, etc. already seeded
- **Models**: User (with Spatie HasRoles/HasPermissions), Role (custom extending Spatie), Permission (custom)
- **WebsiteSetting**: Singleton model for school settings (name, logo, theme, social links, locale)
- **Payment**: Polymorphic Payment model supports bKash, Nagad, Rocket, Stripe, PayPal methods
- **PaymentGateway**: Model for gateway configurations
- **Assignment**: Model for homework/assignments with batch_id, subject_id, due_date, file
- **AssignmentSubmission**: Student submissions
- **Exam/ExamResult**: Full exam and results system with grade calculation
- **Admission**: Admission model with payment support

## Tasks — Implement ALL of the following:

### 1. Admin User & Roles Management UI

Create a full CRUD interface in the dashboard for managing admin users, roles, and permissions:

**Users Management** (`/dashboard/users`):
- List users with search/filter (by role, status)
- Create user form with: name, email, phone, password, role selection, photo upload
- Edit user: change details, assign/change roles, direct permissions
- Delete/restore users (soft deletes if applicable)
- View user profile showing their roles and permissions

**Roles Management** (`/dashboard/roles`):
- List roles with description
- Create/edit role with name, description, guard_name (defaults to web)
- Assign permissions to roles via grouped checklist (by module: User Management, Academic, Students, Exams, Finance, Library, Settings, etc.)

**Permissions View** (`/dashboard/permissions`):
- Read-only list of all permissions grouped by module
- Show which roles have each permission

**Controllers**:
- `App\Http\Controllers\Web\DashboardUserController` — CRUD
- `App\Http\Controllers\Web\DashboardRoleController` — CRUD + permission assignment
- `App\Http\Controllers\Web\DashboardPermissionController` — List only

**Routes** (web, admin-only `middleware:role:admin`):
- `/dashboard/users` → index/create/store/edit/update/destroy
- `/dashboard/roles` → index/create/store/edit/update/destroy + permission sync
- `/dashboard/permissions` → index

**Views** in `resources/views/dashboard/users/`, `dashboard/roles/`, `dashboard/permissions/`

### 2. Library Feature

Full library management with books, book issues, fines, and reports:

**Database tables needed**:
- `books` — id, title, author, publisher, isbn, category, shelf_location, quantity, available_quantity, purchase_date, price, description, cover_image, status, created_by, timestamps, soft_deletes
- `book_categories` — id, name, description, timestamps
- `book_issues` — id, book_id, student_id, teacher_id (nullable), issue_date, due_date, return_date (nullable), status (issued/returned/lost/damaged), late_fee, fine_paid, notes, issued_by, timestamps, soft_deletes
- `library_settings` — id, late_fee_per_day, max_books_per_student, max_books_per_teacher, issue_duration_days, timestamps

**Models**:
- `App\Models\Book` — SoftDeletes, belongsTo BookCategory, hasMany BookIssue
- `App\Models\BookCategory` — hasMany Book
- `App\Models\BookIssue` — SoftDeletes, belongsTo Book, belongsTo Student (nullable), belongsTo Teacher (nullable)
- `App\Models\LibrarySetting` — Singleton

**Controllers**:
- `App\Http\Controllers\Web\DashboardBookController` — CRUD
- `App\Http\Controllers\Web\DashboardBookCategoryController` — CRUD
- `App\Http\Controllers\Web\DashboardBookIssueController` — CRUD + return/collect fine
- `App\Http\Controllers\Web\DashboardLibraryReportController` — Reports

**Routes** (web, admin-only):
- `/dashboard/library/books` → index/create/store/edit/update/destroy
- `/dashboard/library/categories` → CRUD
- `/dashboard/library/issues` → index/create/store/edit/update/return
- `/dashboard/library/reports` → index (currently issued, overdue, history)

**Views** in `resources/views/dashboard/library/books/`, `library/categories/`, `library/issues/`, `library/reports/`

**Permissions**: Already exist — `manage_books`, `issue_books`, `collect_dues`, `view_library_reports`

### 3. Settings Page

Create a consolidated settings page under `/dashboard/settings` with tabbed/sectioned interface:

**Sections**:
1. **General** — School name (en/bn), tagline (en/bn), logo, favicon, default locale
2. **Theme** — Primary color, secondary/accent color, font family, border radius, dark mode default
3. **Academic** — Current academic session, grading system defaults
4. **Localization** — Timezone selection (dropdown of PHP timezones), date format, time format
5. **Payment** — Payment gateway configuration (bKash merchant number, API credentials, etc.)
6. **Library** — Late fee per day, max books per student/teacher, issue duration
7. **Admission** — Admission settings (notice text, display year, etc.)

**Storage approach**:
- Use `WebsiteSetting` singleton model (already exists) for general/theme/academic
- Add `timezone`, `date_format`, `time_format` fields to `website_settings` table
- Add payment gateway fields to `website_settings` or use existing `PaymentGateway` model
- Use `LibrarySetting` model for library-specific settings
- Use existing `AdmissionSetting` model for admission settings

**Controller**:
- `App\Http\Controllers\Web\DashboardSettingController` — index (show all sections), update (save per section)

**Routes**:
- `/dashboard/settings` → index (GET)
- `/dashboard/settings/update` → update (POST, with section parameter)

### 4. Timezone Selection & Live Time Display

**Timezone selection in settings**:
- Add `timezone` column to `website_settings` table (string, default 'UTC')
- In settings page, show timezone dropdown with `DateTimeZone::listIdentifiers()`
- Save selected timezone to website_settings

**Live time in dashboard header**:
- In `resources/views/partials/dashboard/topbar.blade.php`, add a live-updating clock showing the selected timezone's current time
- Use a small JavaScript snippet that updates every second using the server timezone offset
- Show format: "Tuesday, 14 Oct 2025, 3:45 PM [Timezone Name]"
- Use the timezone stored in website_settings, passed via a view composer or directly from settings

**Implementation details**:
- Read `WebsiteSetting::first()->timezone` in layout/partial
- Pass as `$timezone` to topbar view
- JS: `setInterval` updating `new Date().toLocaleString('en-US', { timeZone: TIMEZONE, ... })`

### 5. Marksheet Download (PDF)

Add marksheet/result download capability:

**From Result Page** (`/dashboard/exams/results/{exam}`):
- Add "Download Marksheet (PDF)" button for each student
- Generate PDF with: school header, student info, subject-wise marks, total, grade, GPA, class/teacher comments
- Use `barryvdh/laravel-dompdf` or built-in Laravel PDF generation

**From Student Dashboard** (`/student/dashboard` or `/portal/progress`):
- Show "Download Marksheet" button for each published exam result
- Same PDF template

**Controller additions**:
- `DashboardExamResultController@downloadMarksheet` — Generate and download PDF for a specific student's exam result

**Views**:
- `resources/views/dashboard/exams/results/marksheet-pdf.blade.php` — PDF template

**Routes**:
- `/dashboard/exams/{exam}/results/{result}/marksheet` → downloadMarksheet (admin)
- `/student/results/{result}/marksheet` → student download (student/guardian auth)

### 6. Class-Specific Homework with Guardian Notes

Enhance the existing Assignment system to support class-specific homework and guardian communication:

**Enhancements to `assignments` table**:
- Add `class_id` (FK to school_classes) — for class-specific assignments
- Add `section_id` (FK to sections, nullable) — optional section filtering
- Add `allow_guardian_notes` (boolean) — allow guardians to add notes
- Add `guardian_notes` to `assignment_submissions` table
- Add `guardian_notified_at` (timestamp, nullable)

**Guardian Flow**:
- Guardian logs in, sees student's homework list
- Can add notes to homework submissions
- Notes appear to teacher when reviewing submissions

**Teacher Flow**:
- When creating/editing assignment, select class (and optionally section)
- See guardian notes when viewing submissions
- Option to toggle "allow guardian notes"

**Student Flow**:
- View homework, submit with file/text
- If guardian notes enabled, see a section showing guardian's note (read only for student)

**Controller updates**:
- `DashboardAssignmentController` — add class_id/section_id to store/update, show guardian notes in submissions view
- `PortalController` or new — guardian homework view

**Views**:
- Update `dashboard/assignments/` forms for class/section selection
- Add guardian notes display in submission detail

### 7. bKash Merchant & Payment Configuration UI

**bKash Merchant Integration**:
- Add bKash merchant fields to settings or to `PaymentGateway` model:
  - Merchant number (bKash wallet number)
  - API credentials (username, password, app key, app secret)
  - Environment (sandbox/live)
  - Callback URL (auto-generated from config)
- Wire existing `Payment` model's `bkash` method to use configured merchant details
- Ensure admission payments, student fee payments, and other payments use the same config

**Payment Configuration UI in Settings**:
- In settings page, "Payment" tab/section with:
  - bKash: merchant number, API key, API secret, sandbox toggle
  - Nagad: merchant number, API config
  - Other gateways as needed
  - Default payment method for admissions
  - Currency settings

**Controller updates**:
- `DashboardSettingController@updatePayment` or similar
- `PaymentGatewayController` — already exists, enhance for bKash config

## Implementation Order

1. Create migrations for all new tables/columns
2. Create/update models
3. Create controllers
4. Create views
5. Add routes
6. Add language strings
7. Update navigation sidebar
8. Test

## Conventions to follow

- Controllers in `App\Http\Controllers\Web\`
- Views extend `layouts.dashboard` for admin, `layouts.app` for public/student/guardian
- Use Tailwind CSS v4 utility classes only
- All dashboard routes prefixed with `/dashboard/` and named `dashboard.*`
- Use `authorize()` with Spatie permissions
- Follow existing patterns in DashboardStudentController, DashboardExamController, etc.
- Add language strings to `lang/en/dashboard.php` and `lang/bn/dashboard.php`
- Add sidebar menu items in `resources/views/partials/dashboard/sidebar.blade.php`
- Form inputs use Tailwind styled components consistent with existing code
- Use modals for delete confirmations (existing `confirm-modal` component)
- Flash messages for success/error notifications
- Pagination for lists
- Search/filter for larger datasets
