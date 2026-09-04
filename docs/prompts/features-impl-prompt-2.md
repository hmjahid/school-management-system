# Feature Implementation Prompt 2 — School Management System

## Overview
This document outlines all features to implement in the Laravel 12 school management system. The project uses Blade + Tailwind CSS 4 + Vite for frontend, Spatie Permission for roles, and SQLite for dev.

---

## TASK 1: Student & Guardian Login + Profile System

### Requirements
- Add web-based login for students and guardians (separate from admin/teacher login).
- Each student and guardian has a `users` record (email + password) linked via `user_id`.
- Profile pages for students: view/edit own profile, view attendance, exam results, fee payments, certificates, routines.
- Profile pages for guardians: view/edit own profile, see linked students, view student attendance/results/fees.
- **Uniqueness constraint**: Within each role type, email and phone must be unique. Specifically:
  - Two different `users` records with the same role (both students, or both guardians) cannot share the same email or phone.
  - However, a student and a guardian CAN share the same email/phone (cross-role duplicates allowed).
- Demo credentials:
  - Student: `student@demo.com` / `password`
  - Guardian: `guardian@demo.com` / `password`
- Store credentials in `demo-credentials.md`.

### Implementation
1. **Migration**: Add `unique` composite index on `users(email, role_id)` — but since role_id determines the role type, add a unique constraint per role. Simpler approach: add a `unique` constraint on `(email, role_id)` and `(phone, role_id)` via migration.
2. **Login controller**: Create `App\Http\Controllers\Auth\StudentGuardianLoginController` with:
   - `showLoginForm()` — renders a shared login view with role selector (or auto-detect from URL: `/student/login`, `/guardian/login`).
   - `login(Request $request)` — validates credentials, checks role is `student` or `guardian`, authenticates.
   - Both student and guardian users are `User` model entries with `role_id` pointing to `student` or `guardian` Spatie role.
3. **Profile controller**: Create `App\Http\Controllers\Web\ProfileController`:
   - `edit()` — show profile form
   - `update(Request $request)` — update profile fields
4. **Middleware**: Use existing `auth` middleware for both. Add a `StudentGuardianMiddleware` that ensures user has role `student` or `guardian`.
5. **Routes**: Add to `routes/web.php`:
   - `GET /student/login`, `POST /student/login`
   - `GET /guardian/login`, `POST /guardian/login`
   - `GET /profile`, `PUT /profile`
   - `GET /dashboard` (different dashboard view for student vs guardian)
6. **Views**: Create Blade views:
   - `resources/views/auth/student-login.blade.php`
   - `resources/views/auth/guardian-login.blade.php`
   - `resources/views/student/dashboard.blade.php`
   - `resources/views/guardian/dashboard.blade.php`
   - `resources/views/student/profile.blade.php`
   - `resources/views/guardian/profile.blade.php`
7. **Seeder**: Update `DemoStudentSeeder` and create `DemoGuardianSeeder` (or ensure `DemoUsersSeeder` creates student/guardian users with known credentials).
8. **Validation rule**: Create a custom rule `App\Rules\UniqueEmailPerRole` that checks uniqueness scoped to role.

---

## TASK 2: Internal Messaging System

### Requirements
- Users can send messages to each other:
  - Teachers ↔ Principal (admin)
  - Students → Teachers
  - Guardians → Teachers
- Messages have: sender_id, receiver_id, subject, body, read_at (nullable), timestamps.
- Inbox/Sent views for all users.
- Admin can see ALL messages (audit log) and all user activities.
- Real-time not required (polling or simple page refresh is fine).

### Implementation
1. **Migration**: Create `messages` table:
   ```
   id, sender_id (FK users), receiver_id (FK users), subject, body (text), read_at (timestamp nullable), created_at, updated_at
   ```
2. **Model**: `App\Models\Message` with relationships: `sender()`, `receiver()`.
3. **Controller**: `App\Http\Controllers\Web\MessageController`:
   - `index()` — inbox (received messages)
   - `sent()` — sent messages
   - `create()` — compose form
   - `store()` — send message
   - `show($id)` — view message, mark as read
   - `destroy($id)` — delete
4. **Admin audit**: `App\Http\Controllers\Web\AdminMessageLogController`:
   - `index()` — show all messages with filters
5. **Routes**: Add to `routes/web.php` under auth middleware.
6. **Views**: Create Blade views under `resources/views/messages/` and `resources/views/dashboard/messages/`.
7. **Sidebar**: Add "Messages" item to sidebar for all role types (student, guardian, teacher, admin).
8. **Badge**: Show unread count in sidebar and topbar.

---

## TASK 3: Modern Dashboard UI Overhaul

### Requirements
- Make all dashboard pages modern, elegant, attractive, enterprise-grade, professional.
- Consistent design language across all pages.
- Better cards, tables, forms, buttons, spacing, typography.

### Implementation
- Review and update all Blade views under `resources/views/dashboard/`.
- Use consistent patterns:
  - Stat cards with icons and gradient backgrounds
  - Modern tables with hover states, striped rows, responsive
  - Form layouts with proper spacing, labels, validation states
  - Buttons with consistent sizing and hover effects
  - Page headers with breadcrumbs and action buttons
  - Empty states with illustrations
  - Loading states and skeleton screens
- Use Tailwind utility classes. Follow existing `brand-*` color system.
- Add subtle shadows, rounded corners, smooth transitions.

---

## TASK 4: Hostel Management

### Requirements
- Add hostel management in dashboard: hostels, rooms, room assignments.
- Track which students are assigned to which rooms.

### Implementation
1. **Migrations**:
   - `hostels` table: id, name, address, description, total_rooms, warden_name, warden_phone, status, timestamps
   - `hostel_rooms` table: id, hostel_id FK, room_number, room_type (single/double/triple/dormitory), capacity, occupied, status, timestamps
   - `hostel_assignments` table: id, student_id FK, room_id FK, check_in_date, check_out_date nullable, status, notes, timestamps
2. **Models**: `Hostel`, `HostelRoom`, `HostelAssignment` with relationships.
3. **Controller**: `App\Http\Controllers\Web\DashboardHostelController` — full CRUD for hostels, rooms, assignments.
4. **Views**: Create views under `resources/views/dashboard/hostels/`.
5. **Routes**: Add to `routes/web.php` with dashboard prefix.
6. **Sidebar**: Add "Hostel Management" menu item under an "Infrastructure" group or similar.

---

## TASK 5: Public Website CMS Content Control

### Requirements
- ALL public website content should be changeable from the dashboard CMS.
- Every section on every page should have an edit option.
- Use checkboxes for show/hide on each section's edit page.
- No separate pages needed — add toggle checkboxes inline on existing edit forms.

### Implementation
1. **Review existing `website_contents` table** — it already stores CMS content.
2. **Review existing `WebsiteContent` model** and controllers.
3. **Ensure every public page section** has a corresponding `website_contents` entry with `is_visible` boolean.
4. **Add edit links** in dashboard for each section.
5. **Add visibility toggles** (checkboxes) on each section's edit form.
6. **Ensure theme changes** (colors, fonts) from dashboard apply to ALL content on the public site.

---

## TASK 6: Testimonials & Certificates in Dashboard

### Requirements
- Admin can manage testimonials (add, edit, delete, show/hide).
- Testimonials display on public site.
- Certificate management for students (issue, view, download).

### Implementation
1. **Migration**: Create `testimonials` table: id, student_id FK nullable, author_name, author_designation, content (text), rating (int 1-5), photo, is_visible, sort_order, timestamps.
2. **Model**: `Testimonial` with relationships.
3. **Controller**: `App\Http\Controllers\Web\DashboardTestimonialController` — full CRUD.
4. **Views**: Dashboard views under `resources/views/dashboard/testimonials/`. Public view on homepage or dedicated page.
5. **Routes**: Add to `routes/web.php`.
6. **Certificate pages already exist** — ensure they work with school info (logo, name, address, established year).

---

## TASK 7: Editable Admit Cards, ID Cards, Certificates, Testimonials

### Requirements
- School logo, name, address, established year should appear on all: admit cards, student ID cards, certificates, testimonials.
- Admin should be able to fully edit the layout/content of these documents.
- These are like printable documents that can be customized.

### Implementation
1. **WebsiteSettings** already has school info fields — ensure they're used everywhere.
2. **AdmitCard, StudentIdCard, Certificate models** already exist — update their views/rendering to pull school info from settings.
3. **Add admin edit forms** for customizing:
   - Which fields to show
   - Layout ordering
   - Color scheme
   - Custom text/headers
4. **Views**: Create/edit forms under dashboard for each document type.

---

## TASK 8: Session Year Format

### Requirements
- Session years should be single years: 2026, 2027, 2028.
- NOT ranges: 2025-2026, 2026-2027.

### Implementation
1. **Review `academic_sessions` or `batches` table** — the `year` or `name` column.
2. **Update seeder data** to use single years.
3. **Update any date range formatting** in views to show single years.
4. **Migration if needed**: Update existing data.

---

## TASK 9: Website Link in Dashboard

### Requirements
- Add a "Website" link in the dashboard topbar that opens the public site in a new tab.

### Implementation
1. **Edit** `resources/views/partials/dashboard/topbar.blade.php`.
2. **Add** a link: `<a href="/" target="_blank" rel="noopener">🌐 Website</a>` styled consistently with other topbar items.

---

## TASK 10: Theme Changes Affect All Content

### Requirements
- When admin changes theme (colors, fonts) in dashboard settings, ALL public site content should update.

### Implementation
1. **Review existing theme system** in `WebsiteSetting` model.
2. **Ensure CSS custom properties** (or Tailwind config) are driven by database settings.
3. **Add `View::composer` or inline CSS** that injects theme variables from settings into every page.
4. **Test** that color/font changes propagate everywhere.

---

## TASK 11: Dashboard Language Switching

### Requirements
- When admin changes language in dashboard, sidebar menu names and all dashboard text should update.
- Keep English as default.

### Implementation
1. **Create lang files**: `lang/en/dashboard.php` and `lang/bn/dashboard.php` with all sidebar/menu labels.
2. **Update sidebar** to use `__('dashboard.menu.students')` etc. instead of hardcoded strings.
3. **Update all dashboard views** to use translation helpers.
4. **The `DashboardLocaleController`** already exists and works — ensure it triggers re-render with new locale.

---

## TASK 12: Notice Page Sync

### Requirements
- Dashboard has a Notice management page.
- Notices created/edited/deleted in dashboard are synced to public site.
- Normal and urgent notices both managed from dashboard.

### Implementation
1. **Notice model** already exists with `is_urgent` column.
2. **`DashboardNoticeController`** already exists — ensure it has full CRUD.
3. **Views**: Ensure `resources/views/dashboard/notices/` has index, create, edit views.
4. **Public site**: `HomeController` already pulls notices — ensure it reflects changes.
5. **Add notice count/management** to dashboard sidebar.

---

## TASK 13: Hero Section Improvements

### Requirements
- School name in big font should be like shadow/overlay text, center aligned.
- Use lighter color in hero background.
- If background image is used, ensure proper overlay.

### Implementation
1. **Edit** `resources/views/home.blade.php`.
2. **School name**: Make it a large, centered text with text-shadow or semi-transparent overlay effect.
3. **Background**: Use lighter gradient. Add overlay div with semi-transparent dark layer when background image is set.
4. **Ensure** background image URL comes from `siteSettings`.

---

## TASK 14: All Pages Content Controllable

### Requirements
- Not just homepage — ALL pages' content should be controllable.
- Add checkbox beside each section on its own edit page for show/hide.
- No separate management pages needed.

### Implementation
1. **Review all public pages**: home, about, admissions, contact, gallery, news, faculty, etc.
2. **For each page section**: ensure `website_contents` has entries with `is_visible` flag.
3. **Add edit links** in dashboard for each page/section.
4. **Add toggle checkboxes** on edit forms.
5. **Check visibility** in controllers/views before rendering sections.

---

## TASK 15: Move Admission Below Academic in Sidebar

### Requirements
- In the dashboard sidebar, the "Admission" menu item should be positioned just below "Academic" section.

### Implementation
1. **Edit** `resources/views/partials/dashboard/sidebar.blade.php`.
2. **Move** the Admission menu item/group to be directly after the Academic group.

---

## Implementation Order

1. **Task 15** (Quick fix — sidebar reorder) — 5 min
2. **Task 9** (Website link in topbar) — 5 min
3. **Task 8** (Session year format) — 15 min
4. **Task 12** (Notice sync) — 30 min
5. **Task 11** (Dashboard language) — 1 hour
6. **Task 13** (Hero improvements) — 30 min
7. **Task 1** (Student/Guardian auth + profiles) — 2 hours
8. **Task 2** (Messaging system) — 2 hours
9. **Task 4** (Hostel management) — 1.5 hours
10. **Task 6** (Testimonials) — 1 hour
11. **Task 7** (Editable documents) — 1.5 hours
12. **Task 5** (CMS content control) — 1 hour
13. **Task 10** (Theme propagation) — 30 min
14. **Task 14** (All pages content control) — 1 hour
15. **Task 3** (Dashboard UI modernization) — 3 hours (ongoing)
