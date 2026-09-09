# Eskoofy Theme (WordPress)

**Status: COMPLETE — plugin-theme hybrid.** Full feature-equivalent port of the Laravel
app as a single WordPress theme. See `WORKPLAN.md` Phase 7.

BD/INT variants are build-time profiles — translations via `.pot`/`.po` and branding via
a per-variant stylesheet/profile, never forks.

## Layout

- `style.css` — theme header + base styles (Tailwind-free, vanilla CSS)
- `functions.php` — theme setup, nav menus, widget areas, text domain, enqueues
- `header.php` / `footer.php` / `sidebar.php` / `single.php` / `page.php` /
  `front-page.php` / `archive.php` / `search.php` / `searchform.php` / `404.php` —
  template files
- `template-admission.php` / `template-results.php` / `template-fees.php` /
  `template-gallery.php` / `template-contact.php` — page templates (public)
- `inc/` — plugin layer (loaded by `functions.php`)
  - `database.php` — creates 54 custom `esk_*` tables on activation
  - `custom-post-types.php` — news, events, notices, galleries, testimonials,
    committee members, careers
  - `admin-pages.php` — 15 admin menu pages (dashboard, students, teachers, classes,
    attendance, exams, results, fees, admissions, notices, settings)
  - `admin-ajax.php` — student search, mark attendance, save results
  - `rest-api.php` — `esk/v1/` endpoints (students, teachers, classes, exams, results,
    fees, payments, admissions, notices, news)
  - `shortcodes.php` — results lookup, admission form, fee payment, student profile,
    class schedule, news/events lists, gallery, contact form, payment gateway
  - `payment-gateways.php` — bKash, Rocket, Nagad, Stripe, PayPal, Paddle, Offline
  - `widgets.php` / `customizer.php` / `helpers.php` / `admin.js` / `admin-style.css`
- `views/admin/` — admin page templates (dashboard, students, student-form, student-detail,
  teachers, teacher-form, classes, class-form, sections, subjects, batches, guardians,
  attendance, attendance-mark, exams, exam-form, results, fees, fee-payments, admissions,
  admission-detail, notices, settings, expenses, transport, hostels, library, SMS, payroll,
  reports, certificates, admit-cards, id-cards, announcements, testimonials, committee,
  careers, academic-sessions, users — 39 views total with full CRUD)
- `languages/` — `eskoofy.pot` + `bn_BD` / `en_GB` `.po`/`.mo` files

## Development

```bash
cd eskoofy-theme
composer install
composer run lint       # PHPCS with WordPress-Theme ruleset
composer run lint:fix   # auto-fix
```

## Build

```bash
./build/export.sh theme bd    # → build/dist/eskoofy-theme-bd.zip
./build/export.sh theme int   # → build/dist/eskoofy-theme-int.zip
```

The export script attempts `wp i18n make-pot` when `wp-cli` is available.
