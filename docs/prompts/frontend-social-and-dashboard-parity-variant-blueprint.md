# Frontend Social Parity + Dashboard/Sidebar Product Parity + Variant Blueprint

## Objective
Execute three sequenced tasks across the monorepo and finish with verification:
1. Fix the WP theme **frontend topbar** (remove login/logout from the topbar; auth
   stays in the sticky nav and mobile bar, behind the Dashboard button) and fix
   **social icons not showing** in the theme header topbar/footer.
2. Compare **dashboard home (BLOCK BY BLOCK), sidebar items, and dashboard
   contents** across app / php / theme **side by side**, item by item; merge every
   missing/mismatched item into **all 3 products**; **do not delete anything first**
   (merge additively only).
3. Create a **detailed roadmap/blueprint file** for building another system
   variant with the **Laravel app as the ideal product**.

Golden rules (root + product `AGENTS.md`): php must stay **byte-identical** to app
for `resources/views/**` + `lang/**`; every variant difference is config/data, never
code branching; theme dashboard CSS is regenerated from the app Tailwind build.

The parity audit document `docs/parity/product-parity.md` and the blueprint
`docs/design/VARIANT-BLUEPRINT.md` are artifacts produced by this task.

---

## Task 1 — Theme frontend topbar + socials (`eskoofy-wp-theme`)

### 1a. Topbar
- `header.php` topbar: remove the login/logout link block + its separator.
  Keep the language toggle and the social-row divider.
- Sticky nav: Dashboard/Portal primary button must precede Logout (it already does
  for logged-in users — verify ordering and keep it). Mobile panel actions mirror the
  app bottom bar: logged-in → Dashboard/Portal accent button first, then Logout;
  guests → Login accent; language toggle last.

### 1b. Social icons
- `inc/helpers.php` → `esk_social_profiles()`: add `$defaults` fallback for all 5
  platforms mirroring the app seeder: facebook/instagram/twitter/youtube →
  `https://{key}.com/exampleschool`, linkedin →
  `https://linkedin.com/school/exampleschool`, shown by default.
- `inc/customizer.php`: expose social URL settings for all 5 platforms in the
  `esk_footer` section.
- `views/admin/settings.php`: add a `social` tab (save 5 URL + 5 show-checkboxes).
- `assets/app-public.css`: add brand palette vars + `.bg-brand-600`, `.bg-brand-700`,
  `.hover\:bg-brand-600:hover`, `.hover\:bg-brand-700:hover`,
  `.focus\:border-brand-500:focus`, `.focus\:ring-brand-500\/30:focus` (color-mix)
  so footer social hover / newsletter button / logo letter color correctly.
- Verify: `php -l` on all touched files; header topbar region correct; footer
  socials + topbar socials render 5 icons when shortcode/widget absent.

## Task 2 — Side-by-side dashboard / sidebar / dashboard-contents parity

### 2a. Audit document
Write `docs/parity/product-parity.md` with: verified `diff` note for app vs php
(sidebar, dashboard index, topbar, `lang/en/dashboard.php` = identical); theme
deltas side-by-side per group; dashboard home block matrix; planned merges. Use:
`eskoofy-laravel-app/resources/views/partials/dashboard/sidebar.blade.php`,
`routes/dashboard.php`, `eskoofy-wp-theme/inc/admin-shell.php`
(`esk_admin_sidebar_sections()`), `views/admin/dashboard.php`.

### 2b. app + php sidebar (routes already exist)
Finance group, under `@can('manage_expenses')`: add Expense Categories
(`dashboard.expense-categories*`), Budgets (`dashboard.budgets*`), Bank
Reconciliation (`dashboard.bank-reconciliation*`), Income Statement
(`dashboard.reports.income-statement`), Balance Sheet
(`dashboard.reports.balance-sheet`), Cash Flow (`dashboard.reports.cash-flow`);
extend the Finance `details` open/active `route-is` conditions with those patterns.
Configuration group: add Reports Builder (`dashboard.reports.builder`) + Analytics
(`dashboard.analytics`); tighten Reports `route-is` so Builder highlights its own
item. Add lang keys (`expense_categories`, `budgets`, `bank_reconciliation`,
`income_statement`, `balance_sheet`, `cash_flow`) to `lang/en/dashboard.php` +
`lang/bn/dashboard.php`. Copy the 3 files byte-identical to `eskoofy-php-app` and
confirm with `diff`.

### 2c. Theme sidebar + settings
- `views/admin/settings.php`: add a **Global Labels** tab with a curated
  `site_ui`-key map (nav/home/footer), saving `esk_label_<flat>` options.
- `inc/helpers.php` → `esk_site_ui()`: honor saved `esk_label_<flat>` overrides
  before returning language-file strings.
- `inc/admin-shell.php`: support link items with custom `label`+`url`+`icon`
  (harmless for existing slug links); add **CMS Settings**
  (`esk_dashboard_url('esk-settings','tab=cms')`) and **Global Labels**
  (`tab=labels`) links under the Website group; rename the tab label CMS→CMS
  Settings; add `esk-bank-reconciliation` to Finance details.
- Keep app-missing theme tokens (leave-types, salary-structures, refunds, tools,
  cache, sections, subjects, batches, academic-sessions, progress-reports,
  seat-plans) — richer by design, document in the audit as open question.

### 2d. Theme dashboard home
Port the app setup/onboarding banner into `views/admin/dashboard.php`:
6-item checklist (school info, timezone, academic session, classes, teachers,
payment) computed from WP data; progress bar; CTA → `esk-onboarding`; dismiss via
`localStorage['dc_setup_reminder_dismissed']`; prefix/suffix safe for WP.

## Task 3 — Variant blueprint
Write `docs/design/VARIANT-BLUEPRINT.md`: principles (app-first, parity rules,
additive merges, profile-not-branch), canonical app inventory table, baseline
parity debt inherited from Task 2 (with fix status), phased roadmap
(0 scaffold/gate → 1 schema → 2 auth → 3 dashboard/sidebar → 4 modules →
5 i18n/profiles → 6 API → 7 tests/build-box → 8 release), per-product verification
commands, open questions/owners. Reuse `WORKPLAN.md` phase wording and reference
`FEATURE-PROPAGATION.md` + `NODEJS-VARIANT.md`.

---

## Verification (mandatory before done)
| Product | command |
|---|---|
| app | `cd eskoofy-laravel-app && composer test` (923 passing) + `./vendor/bin/pint --test` |
| php | `diff` views+sidler+lang vs app identical; `php -l` changed files |
| theme | `php -l inc/*.php views/admin/*.php header.php`; `php -l views/admin/settings.php views/admin/dashboard.php` |
| routes | `php artisan route:list | grep` the 8 new sidebar route names resolve |
| docs | parity doc + blueprint exist and match implemented state |

Run everything until green; update `workplan-implementation-plan.md` statuses.