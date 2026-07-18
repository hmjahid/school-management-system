# Enterprise-Grade School Management System — Full Enhancement

You have FULL access to the codebase at the project root. This is a Laravel 12 school management system with an extensive backend (85+ controllers, 65+ models, REST API, Spatie permissions, Sanctum auth, multi-language EN/BN).

Your task is to transform it into a **production-ready enterprise-grade system** across three pillars:

---

## 1. PUBLIC SITE — Enterprise UI/UX Overhaul

Every public-facing Blade view must be redesigned for a premium, modern look. Use Tailwind CSS v4 only (NO custom CSS beyond what's in `resources/css/app.css`). The design must feel like a top-tier educational institution website.

### Files to enhance (in priority order):

#### `resources/views/layouts/app.blade.php`
- Add smooth scroll behavior
- Add a scroll-to-top button (appears after 300px scroll)
- Add a floating WhatsApp/support button
- Add loading bar at top (NProgress-style) for page transitions
- Add proper meta tags for social sharing (OG, Twitter cards)

#### `resources/views/partials/site/nav.blade.php`
- Add sticky nav with backdrop blur on scroll
- Add a prominent "Apply Now" / "Admissions Open" CTA badge
- Add announcement/ticker bar above nav (if announcements exist)
- Add mobile bottom nav bar (iOS-style) as alternative to hamburger
- Add search overlay (expandable search input)

#### `resources/views/partials/site/footer.blade.php`
- Multi-column enterprise footer with:
  - School info + logo + tagline + social links
  - Quick links (grid)
  - Programs/ Academics links
  - Contact info with map embed placeholder
  - Newsletter signup form
  - Copyright + legal links
- Add back-to-top button

#### `resources/views/home.blade.php`
- Hero: Full-screen with animated gradient overlay, particle effect or animated shapes, headline with typewriter effect, dual CTA buttons with hover glow, stats bar overlay at bottom
- Featured programs section with icon cards (animated on scroll)
- Video tour / virtual tour section
- Notice board / ticker with latest announcements
- Count-up animation on stats numbers
- Testimonials carousel (not just static cards)
- Partner/affiliation logos strip
- Blog/news grid with hover effects
- CTA section with parallax effect

#### `resources/views/site/page.blade.php` (generic CMS pages)
- Add table of contents sidebar for long pages
- Add breadcrumb schema
- Add print-friendly styles
- Add "Last updated" date
- Add share buttons

#### `resources/views/site/news.blade.php` + `news-show.blade.php`
- News list: Magazine-style grid with category badges, author avatars, estimated read time, lazy-loaded images, load-more button (not pagination)
- News show: Article layout with featured image hero, table of contents, related articles sidebar, comment section placeholder, social share buttons

#### `resources/views/site/gallery.blade.php`
- Masonry grid layout with lightbox (Fancybox or similar via vanilla JS)
- Category filter tabs
- Infinite scroll or load more
- Image lazy loading with blur placeholder

#### `resources/views/site/events.blade.php`
- Calendar toggle (month/week/list views) using vanilla JS
- Event cards with countdown timer
- Filter by category/date range
- "Add to Calendar" button (download .ics)

#### `resources/views/site/faculty.blade.php`
- Directory grid with cards (photo, name, qualification, subjects, experience)
- Search + filter by department/subject
- Hover card flip effect revealing bio
- Click to expand full profile modal

#### `resources/views/site/contact.blade.php`
- Split layout: form left, info right with Google Maps embed, animated contact cards
- Form validation with inline error messages
- CAPTCHA placeholder
- FAQ accordion section below form
- Business hours display

#### `resources/views/site/admissions.blade.php` + `admissions-apply.blade.php`
- Admissions landing: Process timeline (step 1-2-3-4), fee structure table, download prospectus, FAQ accordion
- Apply form: Multi-step wizard with progress bar, form validation per step, file upload with preview, review step before submit, success animation on completion
- Admission status checker with animated spinner

#### `resources/views/site/results.blade.php`
- Modern search panel with animated form
- Results card with grade badge, percentage donut chart (SVG), subject-wise marks table
- Print button with print-specific layout
- Download PDF button

#### `resources/views/site/transport.blade.php`
- Interactive route map (SVG or placeholder map)
- Route cards with stops timeline, timings, fare
- Vehicle fleet showcase with specs

#### `resources/views/site/portal.blade.php`
- Dashboard-style layout for students/parents
- Profile card, quick stats (attendance %, upcoming exams, fee due), notice board
- Tabbed interface for different sections

### Design System Implementation

Add to `resources/css/app.css`:

```css
@theme {
  --color-surface: oklch(0.99 0 0);
  --color-surface-secondary: oklch(0.97 0.01 250);
  --color-on-surface: oklch(0.15 0.02 250);
  --color-on-surface-muted: oklch(0.55 0.02 250);
  --radius-card: 1rem;
  --radius-button: 0.625rem;
  --shadow-card-hover: 0 8px 30px rgba(0, 0, 0, 0.08);
  --transition-default: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

html { scroll-behavior: smooth; }

.reveal { opacity: 0; transform: translateY(2rem); transition: opacity 0.6s ease, transform 0.6s ease; }
.reveal.visible { opacity: 1; transform: translateY(0); }

.btn-primary { @apply inline-flex items-center justify-center rounded-[--radius-button] bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-brand-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-brand-500/50 active:scale-[0.98]; }

.card-hover { @apply transition-all duration-200; }
.card-hover:hover { transform: translateY(-2px); box-shadow: var(--shadow-card-hover); }

.skeleton { @apply animate-pulse rounded bg-slate-200; }

.spinner { @apply inline-block h-5 w-5 animate-spin rounded-full border-2 border-current border-t-transparent; }

.hero-overlay { background: linear-gradient(135deg, oklch(0.15 0.05 250 / 0.85) 0%, oklch(0.15 0.05 250 / 0.4) 100%); }
```

Add to `resources/js/app.js`:
- Intersection Observer for scroll reveal animations
- Count-up animation utility
- Lightbox for gallery
- NProgress-style loading bar
- Multi-step form controller
- Countdown timer utility

---

## 2. DEMO DATA — Comprehensive Seeders

Enhance ALL existing seeders and add missing ones to create a rich, interconnected demo dataset:

### `database/seeders/DemoAcademicSeeder.php` (NEW)
Create:
- 3 Academic Sessions (2024-2025, 2025-2026, 2026-2027)
- 6 School Classes (Play, Nursery, KG-1, KG-2, Class 1-10)
- 3 Batches per class (Alpha, Beta, Gamma)
- 2 Sections per batch (A, B)
- 8 Subjects per class (Bangla, English, Math, Science, Social, Religion, ICT, Arts)
- Realistic teacher-subject assignments

### `database/seeders/DemoStudentSeeder.php` (NEW)
Create 200+ students across classes with:
- Realistic Bangladeshi names (EN + BN)
- Guardian/parent relationships (1-2 guardians per student)
- Roll numbers per class
- Profile photos (placeholder URLs)
- Address, phone, DOB, gender diversity

### `database/seeders/DemoTeacherSeeder.php` (NEW)
Create 30+ teachers with:
- Specializations matching subjects
- Qualification details
- Salary structures
- Class teacher assignments
- Subject assignments

### `database/seeders/DemoExamSeeder.php` (NEW)
Create:
- 3 exam terms per academic session (Midterm, Final, Pre-Test)
- Exam schedules with dates
- Exam results for 100+ students with realistic marks
- Grade assignments

### `database/seeders/DemoAttendanceSeeder.php` (NEW)
Generate 30 days of attendance for 50 students:
- ~85% present, ~8% absent, ~5% late, ~2% leave
- Realistic patterns (same student not absent 3+ days consecutively)

### `database/seeders/DemoFeeSeeder.php` (NEW)
Create:
- Fee structures per class (tuition, transport, library, lab, activities)
- 3 months of fee records for 50 students
- Mix of paid, unpaid, partial, overdue statuses
- Payment transactions with dates

### `database/seeders/DemoTransportSeeder.php` (NEW)
Create:
- 5 vehicles (bus, microbus) with details
- 4 routes with 6-8 stops each
- Transport assignments for 30 students

### `database/seeders/DemoEventSeeder.php` (NEW)
Create 15 events across the year:
- Cultural events, sports day, parent-teacher meetings, holidays
- Past, current, and upcoming events

### `database/seeders/DemoGallerySeeder.php` (NEW)
Create 8 gallery albums with 40+ images using placeholder image URLs.
Categories: sports, cultural, academic, graduation, field trips

### `database/seeders/DemoLeaveSeeder.php` (NEW)
Create 10 leave requests for teachers: approved, pending, rejected.
Different leave types (sick, casual, annual)

### `database/seeders/DemoLedgerSeeder.php` (NEW)
Create chart of accounts and 50+ ledger entries across accounts with realistic income/expense transactions.

### Update `DatabaseSeeder.php`

```php
public function run(): void
{
    $this->call([
        RolePermissionSeeder::class,
        AdminUserSeeder::class,
        DemoUsersSeeder::class,
        WebsiteSettingSeeder::class,
        WebsiteContentSeeder::class,
        PaymentGatewaySeeder::class,
        DemoAcademicSeeder::class,
        DemoTeacherSeeder::class,
        DemoStudentSeeder::class,
        DemoExamSeeder::class,
        DemoAttendanceSeeder::class,
        DemoFeeSeeder::class,
        DemoTransportSeeder::class,
        DemoEventSeeder::class,
        DemoGallerySeeder::class,
        DemoLeaveSeeder::class,
        DemoLedgerSeeder::class,
    ]);
}
```

### Demo Users to Create

| Name | Email | Password | Role |
|---|---|---|---|
| Admin | admin@school.com | password | admin |
| Teacher 1-5 | teacher1@school.com — teacher5@school.com | password | teacher |
| Accountant | accounts@school.com | password | accountant |
| Student 1-3 | student1@school.com — student3@school.com | password | student |
| Parent 1-3 | parent1@school.com — parent3@school.com | password | parent |

---

## 3. DASHBOARD — Enterprise Admin Panel Enhancement

### `resources/views/layouts/dashboard.blade.php`
- Add dark mode toggle (class-based: `dark` class on html)
- Add keyboard shortcuts panel
- Add responsive improvements for tablets
- Add breadcrumbs component
- Add page loading indicator
- Add notification sound (optional)

### `resources/views/partials/dashboard/sidebar.blade.php`
- Add collapse/expand animation on nav groups
- Add search in sidebar
- Add recent pages / favorites
- Add tooltips on collapsed state
- Add active indicator with animated bar

### `resources/views/partials/dashboard/topbar.blade.php`
- Add global command palette (Cmd+K) search
- Add dark mode toggle
- Add quick-create dropdown (new student, teacher, fee, etc.)
- Add system status indicator
- Add fullscreen toggle

### `resources/views/dashboard/index.blade.php`
- Add interactive charts (use vanilla JS SVG charts, NO chart library):
  - Revenue/expense line chart (last 12 months)
  - Student enrollment bar chart (by class)
  - Attendance pie chart
  - Fee collection donut chart
- Add recent activity feed
- Add quick action cards
- Add upcoming events/list widget
- Add system health/status widget
- Add birthday/anniversary widget

### Table Views (all modules)
Every table view should have:
- Responsive card layout on mobile (not horizontal scroll)
- Column visibility toggles
- Export buttons (CSV, Excel, PDF)
- Bulk action toolbar
- Inline search with debounce (300ms)
- Sortable columns with visual indicator
- Pagination with page size selector
- Empty state with illustration + CTA
- Loading skeleton rows
- Row hover highlight
- Selected row highlight

### Form Views (all modules)
Every form should have:
- Inline validation with real-time feedback
- Unsaved changes warning
- Auto-save draft indicator
- Keyboard shortcuts (Ctrl+S to save)
- Responsive multi-column layout
- File upload with drag-drop zone + preview
- Character/word count on text fields
- Confirmation before destructive actions

---

## 4. BACKEND-FRONTEND ALIGNMENT

### Audit every controller → view data flow
For each dashboard module, verify:
1. The controller method passes ALL needed data to the view
2. The view properly renders every piece of data
3. Form submissions (store/update) validate ALL fields with descriptive messages
4. Delete operations have proper confirmation + cascade handling
5. Pagination is used on ALL listing views
6. Search/filter parameters are preserved in pagination links

### Fix common issues:
- **Missing `siteSettings`**: Ensure all public views receive `$siteSettings` from a global view composer or base controller
- **Translation gaps**: Every UI string must use `__()` or `site_ui()` — check for raw English strings
- **Permission checks**: Every dashboard route must check permissions; add `@can` / `@cannot` to views
- **Error handling**: Add `@error` directives to all form inputs
- **Flash messages**: All mutations (create/update/delete) must flash a success/error message
- **CSRF**: All POST/PUT/DELETE forms must have `@csrf`

---

## 5. ADDITIONAL ENTERPRISE FEATURES

### Dark Mode
- Toggle in dashboard topbar (persist to localStorage + user preference)
- CSS variables for dark palette
- All components respond to `.dark` class

### Accessibility
- ARIA labels on all interactive elements
- Focus indicators on all focusable elements
- Keyboard navigation for all interactive components
- Skip-to-content link
- Proper heading hierarchy
- Alt text on all images
- Color contrast compliance (WCAG AA)
- Screen reader announcements for dynamic content

### Loading States
- Skeleton screens for all list views
- Button loading state with spinner
- Form submission loading overlay
- Page transition loading bar

### Empty States
- Every list view: illustration + message + CTA button
- No results: "No matches found" + suggestion
- No data: "Get started by creating..." + action button

### Error States
- 401: Stylized unauthorized page
- 403: Stylized forbidden page
- 404: Stylized not found page with search
- 419: Session expired with re-login prompt
- 500: Stylized error page with support contact
- 503: Maintenance mode page

### Print Styles
- Dashboards: hide sidebar, topbar, show clean content
- Results/receipts: clean print layout
- Tables: repeat header on each page

### Notifications System
- In-app notification bell with real-time count
- Toast notifications with different types (success, error, warning, info)
- Notification preferences per user
- Email notification templates

### Activity Log
- Proper logging on all CRUD operations
- Activity viewer with filters (date range, user, action type)
- Activity detail view

---

## IMPLEMENTATION GUIDELINES

1. **Never modify `archive/` or `frontend/` directories**
2. **Never add npm packages** — use vanilla JavaScript only
3. **Only Tailwind CSS v4** — no custom CSS files, no SCSS
4. **Use the existing design tokens** (`brand-500`, `brand-600`, etc.) from `app.css`
5. **All new demo data must be realistic Bangladesh school context** (Bangla names, subjects, curriculum)
6. **Run `composer test` after all changes** to ensure nothing is broken
7. **Run `npm run build`** to verify Vite compiles successfully
8. **For placeholder images**: Use `https://picsum.photos/seed/{unique}/800/600`
9. **For avatar placeholders**: Use `https://ui-avatars.com/api/?name={name}&size=200`
10. **Multi-language**: All UI strings must exist in both `lang/en/site_frontend.php` and `lang/bn/site_frontend.php`

---

## VERIFICATION CHECKLIST

After completing, verify by running:
- [ ] `composer test` — all tests pass
- [ ] `npm run build` — Vite compiles without errors
- [ ] `php artisan migrate:fresh --seed` — seeds complete without errors
- [ ] All public site pages load without errors
- [ ] All dashboard modules load and display data
- [ ] Forms submit and validate correctly
- [ ] Search/filter functions work on list pages
- [ ] Dark mode toggle works across dashboard
- [ ] Print styles render correctly
- [ ] Mobile responsive on 375px, 768px, 1024px, 1440px breakpoints
