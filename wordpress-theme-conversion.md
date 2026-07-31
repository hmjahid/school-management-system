# WordPress Theme Conversion Guide — School Management System

This guide documents everything needed to build a separate WordPress theme version of this Laravel school management application. The approach is a **fresh rebuild as a WordPress plugin + theme**, not a direct port.

---

## Architecture Overview

| Laravel Component | WordPress Equivalent |
|---|---|
| Blade views | PHP template files (header, footer, page templates) |
| Controllers | Template files + shortcodes + REST API endpoints |
| Eloquent Models | `WP_Query`, `get_posts()`, `$wpdb` |
| Migrations | Plugin activation hooks (`register_activation_hook`) |
| Routes | WordPress rewrite rules, template hierarchy |
| Middleware (auth, roles) | `current_user_can()`, custom capability checks |
| Blade components | `get_template_part()`, block patterns |
| Vite/asset bundling | `wp_enqueue_style/script`, theme.json |
| Spatie Permissions | WordPress Roles + Capabilities API |
| Sanctum API tokens | WordPress Application Passwords / JWT plugin |
| Blade layouts | WordPress template hierarchy |
| `@yield`/`@section` | WordPress template parts + hooks |
| Tailwind CSS | Tailwind via theme.json + build step |
| Multi-language (lang files) | Polylang / WPML / custom `l10n` |
| CMS page editor | Gutenberg blocks + ACF fields |
| Website Settings (one-row) | `wp_options` table (theme_mod / option) |
| PDF generation (dompdf) | DOMPDF library or TCPDF via plugin |
| Queue/Jobs | WP-Cron or Action Scheduler |
| Activity Log | Custom post type or dedicated table |

---

## Step 1: Foundation — WordPress Plugin + Theme Scaffold

### 1.1 Theme Structure

```
wp-content/themes/school-management/
├── style.css                  # Theme header
├── theme.json                 # Global styles, color palette, typography
├── functions.php             # Theme setup, enqueues, helpers
├── index.php                 # Fallback template
├── header.php                # <head> + opening <body> + site header
├── footer.php                # Footer + closing tags
├── sidebar.php               # Dashboard sidebar (if needed)
├── front-page.php            # Homepage
├── page-{slug}.php           # About, Academics, Admissions, etc.
├── single.php                # News single
├── archive.php               # News archive
├── page-contact.php          # Contact page
├── search.php                # Search results
├── 404.php                   # 404 page
├── template-parts/
│   ├── nav/
│   │   ├── desktop-nav.php
│   │   └── mobile-nav.php
│   ├── hero.php
│   ├── features.php
│   ├── stats.php
│   ├── principal-message.php
│   ├── teachers.php
│   ├── testimonials.php
│   ├── events.php
│   ├── news-section.php
│   ├── partners.php
│   ├── cta.php
│   └── highlights.php
├── dashboard/                # Admin dashboard pages
│   ├── index.php
│   ├── students/
│   ├── teachers/
│   ├── classes/
│   ├── exams/
│   ├── attendance/
│   ├── fees/
│   ├── library/
│   ├── settings/
│   └── ...
├── assets/
│   ├── css/
│   │   ├── tailwind.css
│   │   └── app.css
│   └── js/
│       ├── app.js
│       ├── nav.js
│       └── dashboard.js
├── inc/                      # PHP includes
│   ├── class-school-plugin.php  # Main plugin class
│   ├── class-cpt.php            # Custom post types
│   ├── class-meta-boxes.php     # Meta boxes
│   ├── class-api.php            # REST API endpoints
│   ├── class-shortcodes.php     # Shortcodes
│   ├── class-ajax.php           # AJAX handlers
│   ├── class-roles.php          # Role/capability management
│   ├── helpers.php              # Helper functions
│   ├── dashboard/               # Dashboard functionality
│   └── ...
└── languages/                 # .pot, .po, .mo files
```

### 1.2 Plugin Structure (for backend logic)

```
wp-content/plugins/school-management/
├── school-management.php      # Plugin header + bootstrap
├── includes/
│   ├── class-activator.php       # Activation: create tables, set defaults
│   ├── class-deactivator.php
│   ├── class-cpt.php             # Register all custom post types
│   ├── class-taxonomies.php      # Register custom taxonomies
│   ├── class-roles.php           # Role creation + capabilities
│   ├── class-api.php             # REST API endpoints
│   ├── class-shortcodes.php      # Public shortcodes
│   ├── class-meta-boxes.php      # Custom meta boxes
│   ├── class-ajax.php            # Admin AJAX
│   ├── class-settings.php        # Settings pages
│   ├── class-sms.php             # SMS integration
│   ├── class-pdf.php             # PDF generation
│   ├── class-notifications.php   # In-app notifications
│   ├── class-audit-log.php       # Activity/audit logging
│   ├── class-dashboard-widgets.php
│   ├── class-import-export.php
│   ├── models/                   # Data layer
│   ├── admin/                    # Admin page callbacks
│   └── vendor/                   # Composer deps (dompdf, etc.)
├── templates/                 # Email templates, PDF templates
├── assets/
├── languages/
└── composer.json
```

---

## Step 2: Custom Post Types & Taxonomies

Register these post types via `init` hook in the plugin:

### Core Post Types

| Post Type | Slug | Description | Supports |
|---|---|---|---|
| Student | `student` | Student profiles | title, custom-fields |
| Teacher | `teacher` | Teacher profiles | title, editor, thumbnail, custom-fields |
| Guardian | `guardian` | Parent/guardian | title, custom-fields |
| Class | `school_class` | Academic classes | title, custom-fields |
| Section | `section` | Class sections | title, custom-fields |
| Subject | `subject` | Subjects | title, custom-fields |
| Exam | `exam` | Examinations | title, custom-fields |
| Exam Result | `exam_result` | Exam marks/GPAs | title, custom-fields |
| Batch | `batch` | Student cohorts | title, custom-fields |
| Academic Session | `academic_session` | Yearly sessions | title, custom-fields |
| Fee | `fee` | Fee structures | title, custom-fields |
| Fee Payment | `fee_payment` | Payment records | title, custom-fields |
| Expense | `expense` | Expense tracking | title, custom-fields |
| Book | `book` | Library books | title, custom-fields |
| Book Issue | `book_issue` | Issue/return tracking | title, custom-fields |
| Notice | `notice` | Public notices | title, editor, custom-fields |
| Announcement | `announcement` | Ticker bar items | title, custom-fields |
| Assignment | `assignment` | Homework | title, editor, custom-fields |
| Certificate | `certificate` | Issued certificates | title, custom-fields |
| Admit Card | `admit_card` | Exam admit cards | title, custom-fields |
| Student ID Card | `student_id_card` | Identity cards | title, custom-fields |
| Testimonial | `testimonial` | Student/parent reviews | title, custom-fields |
| Event | `school_event` | School events | title, editor, custom-fields |
| Gallery Item | `gallery_item` | Photo gallery | title, thumbnail, custom-fields |
| Transport Route | `transport_route` | Bus routes | title, custom-fields |
| Vehicle | `vehicle` | Fleet | title, custom-fields |
| Hostel | `hostel` | Hostel management | title, custom-fields |
| Hostel Room | `hostel_room` | Room allocation | title, custom-fields |
| SMS Campaign | `sms_campaign` | Bulk SMS | title, custom-fields |
| Message | `message` | Internal messages | title, custom-fields |
| Leave Request | `leave_request` | Staff leave | title, custom-fields |
| Payroll | `payroll` | Salary records | title, custom-fields |
| Chart of Account | `chart_account` | Accounting | title, custom-fields |
| Journal Entry | `ledger_entry` | Ledger lines | title, custom-fields |

### Custom Taxonomies

| Taxonomy | Post Types | Description |
|---|---|---|
| `class_level` | `student`, `subject`, `exam`, `fee` | Academic level/class |
| `section_term` | `student`, `subject` | Section within class |
| `batch_group` | `student`, `exam` | Batch/cohort grouping |
| `academic_period` | `exam`, `fee`, `attendance` | Session/year |
| `exam_type` | `exam` | Type (term, final, test) |
| `fee_category` | `fee` | Tuition, transport, hostel |
| `notice_type` | `notice` | Normal, urgent |
| `book_genre` | `book` | Category/genre |
| `student_status` | `student` | Active, graduated, transferred |

---

## Step 3: Database Schema (Plugin Activation)

On plugin activation, create custom tables for data that doesn't fit post meta:

```sql
-- Attendance (high volume, needs aggregation)
CREATE TABLE wp_school_attendance (
    id BIGINT AUTO_INCREMENT,
    student_id BIGINT,
    class_id BIGINT,
    date DATE,
    status VARCHAR(20),  -- present, absent, late, holiday
    marked_by BIGINT,
    PRIMARY KEY (id),
    INDEX (student_id, date)
);

-- Staff Attendance
CREATE TABLE wp_school_staff_attendance (
    id BIGINT AUTO_INCREMENT,
    user_id BIGINT,
    date DATE,
    status VARCHAR(20),
    marked_by BIGINT,
    PRIMARY KEY (id)
);

-- Exam Results (high volume, needs grading queries)
CREATE TABLE wp_school_exam_results (
    id BIGINT AUTO_INCREMENT,
    exam_id BIGINT,
    student_id BIGINT,
    subject_id BIGINT,
    obtained_marks DECIMAL(8,2),
    total_marks DECIMAL(8,2),
    grade VARCHAR(5),
    grade_point DECIMAL(4,2),
    PRIMARY KEY (id),
    UNIQUE KEY (exam_id, student_id, subject_id)
);

-- Class Routines / Timetable
CREATE TABLE wp_school_routines (
    id BIGINT AUTO_INCREMENT,
    class_id BIGINT,
    section_id BIGINT,
    subject_id BIGINT,
    teacher_id BIGINT,
    day_of_week TINYINT,  -- 1=Mon, 7=Sun
    start_time TIME,
    end_time TIME,
    room VARCHAR(50),
    PRIMARY KEY (id)
);

-- Ledger / Accounting
CREATE TABLE wp_school_ledger_entries (
    id BIGINT AUTO_INCREMENT,
    account_id BIGINT,
    type VARCHAR(20),  -- debit, credit
    amount DECIMAL(12,2),
    description TEXT,
    reference_type VARCHAR(50),  -- fee_payment, expense, etc.
    reference_id BIGINT,
    entry_date DATE,
    created_by BIGINT,
    PRIMARY KEY (id)
);
```

**All other data** (students, teachers, settings, etc.) can use:
- WordPress posts table (via custom post types) + post meta
- Options table (`wp_options`) for singleton settings
- User meta for profile data

---

## Step 4: Roles & Capabilities

In plugin activation, map Laravel roles to WordPress:

```php
// Administrator → WordPress Administrator (built-in)
// Teacher → custom 'teacher' role
// Student → custom 'student' role
// Guardian/Parent → custom 'guardian' role
// Accountant → custom 'accountant' role
// Librarian → custom 'librarian' role

add_role('teacher', 'Teacher', [
    'read' => true,
    'edit_students' => true,
    'edit_exam_results' => true,
    'take_attendance' => true,
    // ...
]);

// Capabilities mirror the Laravel Spatie permissions:
// manage_students, manage_exams, manage_fees, manage_library,
// manage_school_settings, publish_results, send_bulk_sms, etc.
```

---

## Step 5: REST API (Headless Option)

Port Laravel API endpoints to WordPress REST API:

```php
// Register custom REST routes
add_action('rest_api_init', function () {
    // Public endpoints
    register_rest_route('school/v1', '/results/lookup', [
        'methods' => 'GET',
        'callback' => 'school_api_result_lookup',
    ]);
    register_rest_route('school/v1', '/students/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'school_api_student_profile',
    ]);

    // Authenticated endpoints
    register_rest_route('school/v1', '/attendance', [
        'methods' => 'POST',
        'callback' => 'school_api_mark_attendance',
        'permission_callback' => fn() => current_user_can('take_attendance'),
    ]);
});
```

Use WordPress native `WP_REST_Controller` class pattern for proper schema, pagination, and permissions.

---

## Step 6: Template Hierarchy Mapping

Map Laravel routes to WordPress templates:

| Laravel Route | WordPress Template | Notes |
|---|---|---|
| `/` (home) | `front-page.php` | Show homepage sections |
| `/about` | `page-about.php` | Static page |
| `/academics` | `page-academics.php` | Static page |
| `/admissions` | `page-admissions.php` | Admission flow |
| `/faculty` | `page-faculty.php` | Teacher directory |
| `/news` | `archive-news.php` | Custom post type archive |
| `/news/{slug}` | `single-news.php` | Single news |
| `/gallery` | `page-gallery.php` | Gallery layout |
| `/contact` | `page-contact.php` | Contact form |
| `/results` | `page-results.php` | Result lookup |
| `/routine` | `page-routine.php` | Timetable |
| `/notices` | `page-notices.php` | Notice board |
| `/payments` | `page-payments.php` | Payment portal |
| `/transport` | `page-transport.php` | Transport info |
| `/students` | `page-students.php` | Student life page |
| `/portal` | `page-portal.php` | Login-required portal |
| `/dashboard/*` | Custom admin pages | Admin menu pages |

---

## Step 7: Theme Features & Shortcodes

### Homepage Sections (front-page.php)

Each section is a template part that pulls data from Settings → Homepage CMS:

```
Template part              Data source
──────────────             ────────────────────────
template-parts/hero.php    theme_mod + customizer
template-parts/features.php    ACF repeater or CPT
template-parts/stats.php       Options
template-parts/principal.php   Options (wysiwyg + image)
template-parts/teachers.php    WP_Query('teacher')
template-parts/testimonials.php    WP_Query('testimonial')
template-parts/events.php     WP_Query('school_event')
template-parts/news.php       WP_Query('post' or 'news')
template-parts/partners.php   Options (repeater)
template-parts/cta.php        Options (text + buttons)
template-parts/highlights.php Options (list)
```

### Shortcodes for Public Features

```php
// [student_result class="10" roll="25" session="2026"]
add_shortcode('student_result', 'school_result_shortcode');

// [teacher_list class="science"]
add_shortcode('teacher_list', 'school_teacher_list');

// [notice_board count="5"]
add_shortcode('notice_board', 'school_notice_board');

// [event_calendar]
add_shortcode('event_calendar', 'school_event_calendar');

// [fee_structure class="10"]
add_shortcode('fee_structure', 'school_fee_structure');

// [admission_form]
add_shortcode('admission_form', 'school_admission_form');

// [class_routine class="10" section="A"]
add_shortcode('class_routine', 'school_class_routine');

// [gallery_grid category="annual"]
add_shortcode('gallery_grid', 'school_gallery_grid');
```

---

## Step 8: Customizer & Theme Options

Use WordPress Customizer API for live-preview settings that map to `WebsiteSetting`:

```php
// School Info
$wp_customize->add_section('school_info', [
    'title' => __('School Info', 'school'),
    'priority' => 30,
]);
// school_name, school_name_bn, tagline, tagline_bn, logo, favicon, email, phone, address

// Theme Colors (replaces Laravel theme_primary_color etc.)
$wp_customize->add_setting('theme_primary_color', ['default' => '#4f46e5']);
$wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'theme_primary_color', [
    'section' => 'colors',
]));

// Social Links
// facebook_url, twitter_url, instagram_url, etc. with show/hide toggles

// Homepage Sections — show/hide toggles
// hero, features, stats, principal, teachers, testimonials, etc.

// Footer
// about_text, copyright, important links (repeater)

// Admissions Settings
// on/off toggle, display_year, bar_title, notice

// Payment Settings
// bkash_merchant_number, nagad_merchant_number, currency
```

For more complex settings (repeaters, multi-language text), use ACF Pro options pages.

---

## Step 9: Multi-Language Support

Two approaches:

### Option A: Polylang / WPML (Recommended)
- Install Polylang (free) or WPML (premium)
- Register all CPTs as translatable
- Translate theme strings via `.po/.mo` files
- Translate ACF fields per-language

### Option B: Custom (No External Plugin)
```php
// In functions.php
function school_t(string $key, string $locale = null): string {
    $locale = $locale ?? determine_user_locale();
    $strings = include get_template_directory() . "/languages/{$locale}/frontend.php";
    return $strings[$key] ?? $key;
}

// Usage in templates
<h1><?= school_t('nav.home') ?></h1>
```

Store bilingual content in post meta with language suffix:
```php
// English: meta_key = 'section_title'
// Bengali: meta_key = 'section_title_bn'
```

---

## Step 10: Dashboard / Admin Pages

Register admin menu pages for school management:

```php
add_action('admin_menu', function () {
    // Main menu
    add_menu_page('School', 'School', 'manage_school', 'school-dashboard',
        'school_dashboard_page', 'dashicons-welcome-learn-more', 3);

    // Submenu pages
    add_submenu_page('school-dashboard', 'Dashboard', 'Dashboard',
        'manage_school', 'school-dashboard', 'school_dashboard_page');

    add_submenu_page('school-dashboard', 'Students', 'Students',
        'manage_students', 'school-students', 'school_students_page');

    add_submenu_page('school-dashboard', 'Teachers', 'Teachers',
        'manage_teachers', 'school-teachers', 'school_teachers_page');

    // ... Classes, Exams, Attendance, Fees, Library, Settings, etc.
});
```

Each admin page uses WordPress admin UI patterns (tables, forms, notices) styled with Tailwind or WordPress admin styles.

---

## Step 11: CMS / Page Builder Integration

For the CMS page editor (Laravel's `config/cms_pages.php`):

### Option A: Gutenberg Blocks
Create custom Gutenberg blocks for each CMS section type:
- `school/hero-section` — Headline, subtitle, CTA buttons, background
- `school/features-grid` — 4 feature cards with icons
- `school/principal-message` — Image + WYSIWYG
- `school/teachers-grid` — Dynamic teacher query
- `school/stats-counter` — 4 stat fields
- `school/testimonials-slider` — Quote cards
- `school/events-list` — Dynamic event query
- `school/news-grid` — Dynamic news query
- `school/partners-strip` — Logo repeater
- `school/cta-banner` — Title, intro, 2 buttons

### Option B: ACF Flexible Content (Faster to Build)
Create an ACF options page "Homepage CMS" with a flexible content field containing all section layouts. Each section type is a layout with its own fields.

---

## Step 12: Authentication & User Management

| Feature | WordPress Implementation |
|---|---|
| Login | `wp_login_form()`, custom login page template |
| Registration | `wp_create_user()`, custom registration form |
| Password reset | WordPress native password reset |
| Role-based access | `current_user_can()` checks throughout |
| API auth | Application Passwords (WordPress 5.6+) or JWT plugin |
| Student/Guardian portal | Custom page templates with `is_user_logged_in()` |
| Profile editing | `edit_user_profile` hook + custom fields |

---

## Step 13: Third-Party Integrations

| Laravel Package | WordPress Equivalent |
|---|---|
| `barryvdh/laravel-dompdf` | `dompdf/dompdf` composer package + `wp_mail` attachment |
| `spatie/laravel-permission` | WordPress Roles + `WP_User->add_cap()` |
| `spatie/laravel-activitylog` | Custom `wp_school_activity_log` table + admin list table |
| `laravel/sanctum` | Application Passwords or JWT Auth plugin |
| Twilio/Nexmo SMS | respective PHP SDKs via composer in plugin |
| Firebase Cloud Messaging | `kreait/firebase-php` or WP plugin |
| Payment gateways (bKash/Nagad) | Direct API integration in plugin |

---

## Step 14: PDF Generation (Certificates, Marksheets, Receipts)

Use `dompdf` in the plugin:

```php
use Dompdf\Dompdf;

function school_generate_marksheet($student_id, $exam_id) {
    $dompdf = new Dompdf();
    ob_start();
    include SCHOOL_PLUGIN_PATH . 'templates/marksheet.php';
    $html = ob_get_clean();
    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream("marksheet-{$student_id}.pdf");
}
```

---

## Step 15: Frontend Assets

### Tailwind CSS Setup
```json
// theme.json (add to theme root)
{
  "version": 2,
  "settings": {
    "color": {
      "palette": [
        { "slug": "primary", "color": "var(--wp--custom--color--primary)" }
      ]
    }
  }
}
```

Build process:
```bash
npm init -y
npm install tailwindcss @tailwindcss/postcss postcss
npx tailwindcss init
```

`tailwind.config.js`:
```js
module.exports = {
  content: ['./**/*.php'],
  theme: {
    extend: {
      colors: {
        brand: {
          50: 'var(--brand-50)',
          100: 'var(--brand-100)',
          // ... generate from primary color
        }
      }
    }
  }
};
```

Build command in `package.json`:
```json
{
  "scripts": {
    "build": "npx tailwindcss -i ./assets/css/tailwind.css -o ./assets/css/app.css --minify",
    "dev": "npx tailwindcss -i ./assets/css/tailwind.css -o ./assets/css/app.css --watch"
  }
}
```

### Dynamic CSS Custom Properties
```php
// In header.php
$primary = get_theme_mod('theme_primary_color', '#4f46e5');
?>
<style>
:root {
  --theme-primary: <?= $primary ?>;
  --brand-50: <?= hexToRgba($primary, 0.05) ?>;
  /* etc. */
}
</style>
```

---

## Step 16: PWA Support

Use a WordPress PWA plugin or add manually:
```php
// In header.php
?>
<link rel="manifest" href="<?= home_url('/manifest.json') ?>">
<meta name="theme-color" content="<?= get_theme_mod('theme_primary_color', '#4f46e5') ?>">
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= get_template_directory_uri() ?>/service-worker.js');
}
</script>
```

---

## Step 17: Webpack Instructions

```bash
# Build the theme from scratch
mkdir -p wp-content/themes/school-management && cd $_

# Initialize
npm init -y
npm install tailwindcss @tailwindcss/postcss postcss autoprefixer

# Tailwind config
npx tailwindcss init

# Build
npm run build
```

---

## Step 18: Complete Conversion Prompt

> "Create a WordPress theme and plugin for a school management system. The system needs:
>
> **Custom Post Types**: student, teacher, guardian, school_class, section, subject, exam, exam_result, batch, academic_session, fee, fee_payment, expense, book, book_issue, notice, announcement, assignment, certificate, admit_card, student_id_card, testimonial, school_event, gallery_item, transport_route, vehicle, hostel, hostel_room, sms_campaign, message, leave_request, payroll, chart_account, ledger_entry
>
> **Custom Taxonomies**: class_level, section_term, batch_group, academic_period, exam_type, fee_category, notice_type, book_genre, student_status
>
> **User Roles**: Administrator (built-in), Teacher, Student, Guardian, Accountant, Librarian — each with granular capabilities matching: manage_students, manage_teachers, manage_exams, manage_fees, manage_library, manage_school_settings, manage_attendance, publish_results, send_bulk_sms, manage_transport, manage_hostel, manage_notices, manage_announcements, manage_events, manage_gallery, manage_roles, manage_permissions, manage_users, manage_certificates, manage_admit_cards, manage_student_id_cards, manage_testimonials, manage_assignments, manage_expenses, manage_ledger, manage_payroll, manage_leaves, view_reports, backup_database
>
> **Frontend Public Pages**: Homepage (10 sections: hero, features, stats, principal message, teachers slider, testimonials, events, news, highlights, partners, CTA, admissions bar), About, Academics, Admissions (with online application, payment, status tracking), Faculty/Teachers directory, News & Events, Notices, Gallery, Contact, Results lookup, Class Routine, Payments, Transport, Student Life, Portal (student/guardian login), Privacy, Terms
>
> **Admin Dashboard Pages**: Dashboard with analytics widgets, Student CRUD, Teacher CRUD, Guardian CRUD, Class/Section/Subject management, Exam & Result management with marksheet PDF, Attendance (student + staff) with bulk entry, Fee structure & payment management with receipt PDF, Expense tracking, Full accounting (chart of accounts, journal, cashbook, bank book, income statement, balance sheet), Payroll & leave management, Transport (vehicles, routes, assignments), Hostel (rooms, assignments), Library (books, categories, issue/return), SMS campaigns, Internal messaging, Notices & Announcements, News & Events, Gallery, Certificate/Admit Card/ID Card generation with PDF, Testimonials, Assignments with submissions, Backup & restore, Activity log, Visitor log, Help documentation, Website CMS with Gutenberg blocks, Global Labels, School settings (info, theme, localization, payment gateway, admission, library)
>
> **Key Features**: Multi-language (Bengali + English via Polylang or custom), Dynamic CSS theme colors, Mobile-responsive with breakpoint at 1367px for nav, Dark mode, PWA support, REST API for headless use, SEO with schema.org, Role-based access control, Bilingual content in post meta
>
> **Database**: Custom tables for attendance, exam_results, routines, ledger entries. Use WordPress posts + post meta for all entities, options table for settings, wp_users + usermeta for profiles.
>
> **Tech Stack**: WordPress theme with Tailwind CSS, custom plugin for backend logic, DOMPDF for PDF generation, Polylang/WPML for translations, custom REST API endpoints, Gutenberg blocks for CMS editing.
>
> Follow WordPress coding standards, use esc_attr/esc_html/wp_kses for escaping, nonce verification for forms, and capability checks throughout."

---

## Step 19: Development Workflow

```bash
# 1. Set up local WordPress
wp core download
wp config create --dbname=school --dbuser=root --dbpass=root
wp db create
wp core install --url=http://localhost:8000 --title="School Management" --admin_user=admin --admin_password=admin --admin_email=admin@school.test

# 2. Create theme (from step 1.1 scaffold)
mkdir -p wp-content/themes/school-management/{inc,dashboard,template-parts,assets/{css,js},languages}

# 3. Create plugin (from step 1.2 scaffold)
mkdir -p wp-content/plugins/school-management/{includes,admin,templates,assets,languages}

# 4. Install dependencies
cd wp-content/themes/school-management
npm install tailwindcss @tailwindcss/postcss postcss autoprefixer

cd wp-content/plugins/school-management
composer require dompdf/dompdf

# 5. Activate
wp plugin activate school-management
wp theme activate school-management

# 6. Import demo data (optional)
wp school import-demo-data
```

---

## Step 20: Data Migration from Laravel

For migrating existing Laravel data to WordPress:

```php
// WP-CLI command in plugin
class School_Migration_Command {
    public function import_laravel_db($args, $assoc) {
        $laravel_db = $assoc['db'];

        // Connect to Laravel DB
        $laravel = new PDO("sqlite:{$laravel_db}");

        // Migrate website_settings → theme_mods + wp_options
        $settings = $laravel->query("SELECT * FROM website_settings")->fetch();
        foreach ($settings as $key => $value) {
            if ($key === 'id') continue;
            set_theme_mod("school_{$key}", $value);
        }

        // Migrate students → wp_posts (post_type: student) + postmeta
        $students = $laravel->query("SELECT * FROM students")->fetchAll();
        foreach ($students as $s) {
            $post_id = wp_insert_post([
                'post_type' => 'student',
                'post_title' => "{$s['first_name']} {$s['last_name']}",
                'post_status' => 'publish',
            ]);
            foreach ($s as $k => $v) {
                update_post_meta($post_id, $k, $v);
            }
        }

        // ... similar for teachers, classes, exams, results, etc.
    }
}
WP_CLI::add_command('school', 'School_Migration_Command');
```

Run: `wp school import-laravel-db --db=/path/to/laravel/database.sqlite`

---

## Summary Checklist

- [ ] Theme scaffold (step 1.1)
- [ ] Plugin scaffold (step 1.2)
- [ ] All CPTs registered (step 2)
- [ ] All taxonomies registered (step 2)
- [ ] Custom tables created on activation (step 3)
- [ ] Roles + capabilities (step 4)
- [ ] REST API endpoints (step 5)
- [ ] Frontend template files for all pages (step 6)
- [ ] Homepage sections as template parts (step 7)
- [ ] Shortcodes for dynamic content (step 7)
- [ ] Customizer settings for school info/colors (step 8)
- [ ] Multi-language setup (step 9)
- [ ] Admin menu pages for all management (step 10)
- [ ] Gutenberg blocks or ACF for CMS (step 11)
- [ ] Auth + user management (step 12)
- [ ] Third-party integrations (step 13)
- [ ] PDF generation (step 14)
- [ ] Tailwind CSS build (step 15)
- [ ] PWA support (step 16)
- [ ] SEO + schema.org markup
- [ ] Demo data import command (step 20)
