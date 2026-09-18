# QA Remediation Plan — parity + UI/frontend findings implementation

**Status legend:** ✅ done · 🟡 partial · ⛔ blocked · ⬜ not started / gated

**Progress snapshot (2026-09-17 session):**
- ✅ Phases 0, 1, 2, 3, 4 complete (report corrections; app bug fixes + php byte-mirror;
  theme front-end parity; sidebar alignment + label drifts; UI lib incl. page-header sweep).
- ✅ Phase 5 complete — 5.1-5.12 all implemented (online payments, certificates single,
  RBAC, multistep admission, portal + the module-depth items).
- ✅ Phase 6 complete — 6.1-6.6 (row actions/edits, ledger/budgets/chart-of-accounts,
  staff-directory/expense-categories/events-calendar/library-categories/admission-tests/
  notification-templates+preferences, device_tokens + scroll/page-header sweep, archived
  19 orphaned php views).
- ✅ Phase 7 — eskoofy-php expansion implemented (token auth, 60 API paths ported,
  scheduler + push, 3 models, service classes). php suite 311 green.
- ✅ Phase 8 — website sales copy updated (en + bn).

This plan implements the two senior-QA audits:

1. `docs/quality/QA-PARITY-REPORT.md` — eskoofy-php & eskoofy-theme parity vs the Laravel app
   (IDs `P-A1..P-A6`, `P-B1..P-B6`).
2. `docs/quality/QA-UI-FRONTEND-REPORT.md` — theme dashboard sidebar + UI/UX + public frontend +
   functionality (IDs `SB-1..SB-7`, `P-U1..P-U19`).

**Scope rule (root AGENTS.md + `docs/design/FEATURE-PROPAGATION.md`):** default scope is ALL
products (app + php + theme + website copy). Feature-level additions require a confirmation
gate per `FEATURE-PROPAGATION.md`. Bug fixes that restore parity (where the app or theme is
*incorrect* and the counterpart is correct) are applied to the side(s) that are wrong, and
propagated through the byte-parity rule (`eskoofy-php` views must stay byte-identical to the
app). `eskoofy-php` product work is **deferred** (root AGENTS.md / WORKPLAN Phase 6 gate
0.2) — API/model/scheduler expansions below are recorded but gated on the php resume gate.

---

## Phase 0 — QA report corrections (metadata accuracy)

| # | Report | Finding to correct | Action | Status |
|---|---|---|---|---|
| 0.1 | QA-UI-FRONTEND-REPORT.md | SB-1 calls `esk_admin_shell_groups()` (admin-shell.php:50) "dead code" that contradicts rendered `esk_admin_sidebar_sections()` (:77). **Correction:** `groups()` is NOT dead — it powers the command palette (admin-shell.php:450 `esk_render_palette_data()`, :777). The real issue is *orphaned slugs*: 15 slugs registered in `groups()` have no sidebar entry, most with built views reachable only via palette. | Reword SB-1 + P-U11 to "orphaned slugs (reachable only via palette)" and drop the dead-code claim. Keep the list of 15 slugs. | ✅ |
| 0.2 | QA-UI-FRONTEND-REPORT.md | 3.4 Contact row calls theme hours "hardcoded (`:58`)". **Correction:** `template-contact.php:58` builds a default `$opening_hours` array but the contact card reads `esk_site_ui('pages.contact_hours_value')` (settings-driven) and the hours table falls back to defaults only when empty. Verdict stays P2 (defaults embedded) but wording "hardcoded" → "defaults embedded, site_ui-overridable". | Adjust wording in 3.4 Contact row. | ✅ |
| 0.3 | QA-UI-FRONTEND-REPORT.md | 3.5 Fonts row claims theme has "no Bengali webfont" (system-ui only). **Correction:** theme enqueues Inter + Noto Sans Bengali (`functions.php:358-360`) and applies them via `--esk-font-body` (`style.css:545`, Bengali stack `:540`). | Change row verdict to ✓ and note both ship the same font set. | ✅ |
| 0.4 | QA-UI-FRONTEND-REPORT.md | SB-1 "orphaned slugs" + SB-2 "admin groups rendered for all users". **Second correction:** (a) the 15 slugs are palette-only in the **app too** → theme matches app, no sidebar change; (b) the theme gates the **whole dashboard** behind `manage_options` (`front-dashboard.php:220`) → non-admins never see the shell, so the ungated group headings are not a leak. | Reword SB-1 (parity holds) + SB-2 (whole-dashboard gate). | ✅ |

## Phase 1 — App (Laravel) bug fixes + php byte-mirror  ✅ done (923 tests pass)

App is *incorrect*; theme port is correct. Fix app, then mirror byte-identical into
`eskoofy-php/resources/views/**` (root AGENTS view-parity invariant).

| # | Report | Finding | App fix | php mirror | Status |
|---|---|---|---|---|---|
| 1.1 | P-U7 | `site/news-show.blade.php` strips article HTML (`strip_tags`+`nl2br`) + hardcoded "5 min read" | Render HTML content (whitelisted tags), compute reading time from word count, keep dropcap, add tiny inline sanitize (strip `<script>`/`on*`/`javascript:`) | Copy file byte-identical | ✅ |
| 1.2 | 3.4 404 | `errors/404.blade.php` search form POSTs to `url('/news')?q=` (wrong route) + reuses `admin-input`/`btn-primary` | `action="{{ route('site.search') }}"` method GET; replace `admin-input` with public search-input styling (matches `site/search.blade.php:13`), keep `btn-primary` (it is a public-site button class, app.css:78) | Copy file byte-identical | ✅ |
| 1.3 | P-U17 | `components/page-header.blade.php` h1/p lack `dark:` variants (dark-on-dark) | Add `dark:text-slate-100` on h1, `dark:text-slate-400` on p | Copy file byte-identical | ✅ |

## Phase 2 — Theme front-end (public site) quick parity fixes ✅ done

All items executed (2.1 nav order, 2.2 footer links, 2.3 topbar order, 2.4 mobile drawer,
2.5 font already-present). `php -l` clean; no new PHPCS violations.

| # | Report | Finding | Theme fix | Status |
|---|---|---|---|---|
| 2.1 | P-U13 (3.2) | Nav top-level order News/Contact swapped vs app (app: About→Academics→Contact→News) | In `header.php:67-109` move the `nav.group.contact` array block before `nav.group.news` | ✅ |
| 2.2 | P-U13 (3.3) | Footer quick links differ (10 vs app 7) + dead `/certificates/` link | Align `footer.php:23-34` quick-links to app set (about, academics, admissions, faculty, committee, news, gallery); drop `link_apply_online`, `link_contact`, `link_payments`, `link_routine`, `link_certificates`; add faculty + committee labels (`footer.link_faculty`, `footer.link_committee`) to site_ui en/bn | ✅ |
| 2.3 | P-U18 (2.1) | Topbar dark⇄bell order swapped (theme: bell then dark; app: dark then bell) | In `admin-shell.php:594-601` move the `#esk-dark-toggle` block before the notifications `<a>` | ✅ |
| 2.4 | P-U8 (2.2) | Theme mobile sidebar drawer: no backdrop / outside-close / scroll-lock (app has all three) | `inc/admin-shell.css` + `inc/admin-shell.php` JS: added `#esk-sidebar-overlay` backdrop, click-outside close, `body.esk-shell-nav-open { overflow:hidden }` scroll-lock, Escape-to-close (mirrors app `dashboard.blade.php:109` + `topbar.blade.php:167-182`) | ✅ |
| 2.5 | 3.5 | Theme has no Bengali webfont (app ships Inter + Noto Sans Bengali) | **Already present** — theme enqueues Inter + Noto Sans Bengali (`functions.php:358-360`), applies via `--esk-font-body` (`style.css:545`). Finding corrected to ✓ (see Phase 0.3). | ✅ |

## Phase 3 — Theme dashboard sidebar alignment (SB-*)

| # | Report | Finding | Fix | Status |
|---|---|---|---|---|
| 3.1 | SB-1 / P-U11 | 15 slugs registered in `groups()` have no sidebar entry; most have built views (sections, subjects, batches, academic-sessions, progress-reports, seat-plans, refunds, notifications, search, onboarding, reports-builder, analytics, bank-reconciliation) | **No change — parity holds.** The app sidebar also omits these (palette-only in both). See Phase 0.4. | ✅ |
| 3.2 | SB-3 | App-only: Favorites group, School Info flat link, Calendar flat link, Staff Directory (People+HR) | **Favorites already present** (`esk-fav-group`/`esk-fav-list`). School Info ≈ theme `esk-settings` (Configuration). Calendar added as `esk-events-calendar` flat link (Phase 6.3). Staff Directory added as `esk-staff-directory` under People (Phase 6.3). | ✅ |
| 3.3 | SB-4 / P-U12 | Theme-only sidebar items expose features app nests deeper (Income/Balance/Cash-Flow, Leave Types, Payslips, Salary Structures, Profile) | Keep — they are real theme pages; document as intentional depth. No change (P3). | ✅ |
| 3.4 | SB-2 / P-U10 | System/Website/Administration/Configuration + SMS rendered for all users (app admin-only) | **Not a leak** — theme gates the whole dashboard behind `manage_options` (`front-dashboard.php:220`). Per-group role gating (to match app's multi-role UX) is Phase 5.10. See Phase 0.4. | ✅ |
| 3.5 | SB-6 | Label drifts (Guardians, Results, Routines, ID Cards, Fee Payments, Contact Submissions, Bulk, Software) | Renamed in `inc/admin-shell.php` titles(): `Parents`, `My Results`, `Class Routine`, `Student ID Cards`, `Payments`, `Form Submissions`, `About`. | ✅ |

## Phase 4 — Theme dashboard UI lib parity (P2) ✅ done

| # | Report | Finding | Fix | Status |
|---|---|---|---|---|
| 4.1 | P-U15 | Legacy WP chrome (`h1.wp-heading-inline`, `.page-title-action`, `.notice`) | Added shared page-header styling (title typography + action button + divider, dark-mode aware) via `.wp-heading-inline`/`.page-title-action`/`.wp-header-end` rules in `inc/admin-style.css`, applied across all legacy views (parity with app `<x-page-header>`); `.esk-empty-state` + `.esk-table-scroll` applied to exemplars + global mobile scroll rule. | ✅ |
| 4.2 | P-U16 | Toasts text-only, not dismissible, 3.2s (app: dismissible, 5s, top-center/mobile) | `inc/admin-shell.php` — added `×` dismiss button + 5s duration (matches app `showToast`); CSS `.esk-toast-dismiss`/`.esk-toast-text` + row flex. | ✅ |
| 4.3 | P-U16 | Confirm modal always brand-OK, inline `confirm()` interception (app: danger variant) | Added `confirm('msg', 'brand')` support → `.esk-modal-brand` (blue OK + blue icon); default stays red/danger. Applied to non-destructive calls (exams publish, fee-payment refund). | ✅ |
| 4.4 | P-U16 | Fees/exams lists unpaginated (app paginates) | Added `LIMIT/OFFSET` + `paginate_links()` pagination to `views/admin/fees.php` + `views/admin/exams.php`. | ✅ |

## Phase 5 — Theme functionality depth (P1/P2) ✅ done

Feature-level work. Per `FEATURE-PROPAGATION.md`, present per-product file plan and obtain
owner confirmation before implementing. Listed as prioritized backlog.

All items (5.1-5.12) implemented — see statuses below.

| # | Report | Gap | Scope sketch | Status |
|---|---|---|---|---|
| 5.1 | P-U2 | Online fee payment non-functional (gateways unmounted, lookup-only shortcode) | Mounted gateways into `esk_shortcode_fees_payment`: POST handler creates pending fee_payment + payment records, calls `esk_process_payment`, redirects to gateway; added `esk/v1/payments/callback/{gateway}` REST route for verify + mark-paid; result notice on return. | ✅ |
| 5.2 | P-U3 | Transport: no student/route assignments or stops | `views/admin/transport.php` — added Assignments tab (student↔route↔stop assignment + remove); stops table exists in schema | ✅ |
| 5.3 | P-U4 | Hostels: no room edit / allocation | `views/admin/hostels.php` — room edit, student allocation (occupancy++), check-out (occupancy--); allocation table | ✅ |
| 5.4 | P-U5 | Library: no fine/lost workflow; `fine_per_day` unused | `views/admin/library.php` — return computes `late_fee` from `esk_fine_per_day`, Mark Lost (fee + stock adjust), Mark Paid toggle, fine column; non-destructive confirm on refund | ✅ |
| 5.5 | P-U14 | Content modules add+delete only (news/gallery/announcements/notices/events) | Add edit + publish/unpublish toggle + delete to the 5 content views (news, gallery, announcements, events, notices) | ✅ |
| 5.6 | P-U14 | Fees add-only; no payment-approve flow | Fee edit + soft-delete + pagination (fees.php). Payment-approve flow tracked separately. | ✅ |
| 5.7 | P-U14 | Exams publish one-way (no unpublish) | Added `unpublish_exam` handler + Unpublish action (status → draft) | ✅ |
| 5.8 | P-U14 | Routines/assignments no edit; assignments no submissions/grade | Routine edit added; assignment edit + submissions/grade panel (marks, feedback, status→graded) | ✅ |
| 5.9 | P-U14 | Admit/ID/certificates batch-only, no single create/edit/print | Added `esk_student_certificates` table + Issue form (student/type/date/body/details) + edit template + print view (auto window.print) | ✅ |
| 5.10 | P-U10 / P-B2 | RBAC: no roles/permissions pages; System/Website/Admin/Config visible to all | `esk_can()`/`esk_can_access_dashboard()` helpers + `esk_role_caps` option; `esk-roles` page (role×capability matrix); sidebar gates System/Website/Administration/Configuration + SMS for non-admins; dashboard gate relaxed to capability map | ✅ |
| 5.11 | P-U6 | Admission apply single-page vs app 6-step multistep | Converted to 6-step wizard (student, contact, academic session/batch, guardian, documents incl. T.C. + birth cert, review) with wizard JS + CSS; save handler persists new fields + uploads | ✅ |
| 5.12 | P-U1 | No student/parent portal (app `/portal` tabs) | Rebuilt `template-portal.php` as authenticated tabbed portal (Profile/Attendance/Exams/Fees/Routine/Announcements/Events) for students + guardians; auth-gated; tab JS + CSS | ✅ |

## Phase 6 — Theme module/table depth (P1/P2) ✅ done

| # | Report | Finding | Scope sketch | Status |
|---|---|---|---|---|
| 6.1 | P-B4 | Students list no row actions; teachers add-only; expenses no edit/budget/export; testimonials no edit | Students: View/Edit/Delete actions; teachers: edit form (prefilled) + Delete; expenses: edit + prefilled form; testimonials: edit + prefilled form | ✅ |
| 6.2 | P-B1 | Finance subsystem absent (budgets, ledger, invoices, recurring, chart-of-accounts) | Added `esk_chart_of_accounts` + `esk_budgets` tables; `views/admin/ledger.php` (Journal + Chart of Accounts tabs, entry recording, account CRUD); `views/admin/budgets.php` (CRUD vs expense categories); routes/titles/icons/sidebar wired | ✅ |
| 6.3 | P-B3 | communications, staff directory, expense-categories, events calendar, library categories, admissions tests, notification templates/preferences pages absent | communications = `esk-messages` (compose/inbox/sent, already wired Main→Messages); `esk-expense-categories` page (CRUD); `esk-events-calendar` month view + flat link; library book categories CRUD in `library.php`; `esk-staff-directory` page (Users by staff/accountant/librarian role) under People; admission tests: `esk_admission_tests` table + schedule/update UI in `admission-detail.php`; `esk-notification-templates` + `esk-notification-preferences` pages + tables under System | ✅ |
| 6.4 | P-B5 | Name drifts: guardians↔parents, id-cards↔student-id-cards, software↔about | Renamed labels/routes to app naming | ✅ |
| 6.5 | P-B6 | WP list-table vs Tailwind; PWA `device_tokens` absent | Added `esk_device_tokens` table; global mobile horizontal-scroll rule for legacy tables + shared page-header styling (4.1) | ✅ |
| 6.6 | P-A1 | ~34 legacy snake_case php views orphaned | View-unreferenced scan (view()/render + Blade includes) → **19 confirmed orphaned** archived to `eskoofy-php/archive/dashboard/`; php suite 299 tests green | ✅ |

## Phase 7 — eskoofy-php expansion — ✅ done (php resume gate passed)

`eskoofy-php` API/model/scheduler/push expansion implemented (see statuses below).
Byte-parity of `resources/views/**` maintained (316/316 app views identical).

| # | Report | Gap | Scope sketch | Status |
|---|---|---|---|---|
| 7.1 | P-A2 | ~60 API paths missing (admin/CMS 26, auth 4, payments/refunds 16, notifications 7, teacher 3, …) | Ported: auth (login/register/logout/refresh-token/me/user), academics (curriculum/programs/faculty/results-filters), news (categories/upcoming-events/show), careers (index/show/apply), legal (terms/privacy/sitemap/home), website-content (page/pages/update/upload-image), website/gallery, events show, teacher portal (classes/students/grades), search (+ resource), fees (types/statistics/fee-payments), fee-payment sub-routes (statuses/methods/show/update/approve/cancel), admin (dashboard/analytics/activity/quick-actions/cms pages-media-menus-settings-header-footer-blocks/widgets/website-settings), refund webhook. php API surface now 156 defs vs app 151. | ✅ |
| 7.2 | P-A3 | No push/FCM + no scheduler (recurring payments, scheduled notifications, due-jobs) | Ported `RecurringPaymentService`, `NotificationService`, `Notification\ScheduledNotificationService`, `Push\FirebasePushService` + `LogPushService` (dependency-free curl), `public/cron.php` self-gating cron entry (recurring daily 01:00, notifications every 5 min). | ✅ |
| 7.3 | P-A4 | No Course / Grade / UserWidgetPreference models (tables exist) | Added the 3 model classes (tables already in schema.sql). | ✅ |
| 7.4 | P-A5 | API not token-secured (session-only) | Sanctum-style token auth: `personal_access_tokens` table in schema.sql, `PersonalAccessToken` model (create/find/validate/touchLastUsed), `ApiTokenMiddleware` (Bearer + `api_token` query fallback), `AuthController` issues access+refresh token pair; `AuthMiddleware` accepts Bearer too; `/api/*` exempt from global CSRF. | ✅ |
| 7.5 | P-A6 | Services inline instead of classes | Payment/Refund logic already adapter-based via `GatewayFactory` (service layer); added `RecurringPaymentService`, `NotificationService`, `ScheduledNotificationService`, `Push\*` as classes (php suite 311 green). | ✅ |

## Phase 8 — Website sales copy ✅ done

`eskoofy-website` copy updated (en + bn) to reflect shipped theme features: online
gateway payments, roles & permissions, student/parent portal, multistep admissions,
budgets/chart-of-accounts/ledger, transport & hostel allocations, library fines. Feature
grid + theme product page `theme_f1..f6` + `features.*_desc` + `deploy_theme` refreshed.
Website suite: 79 tests green.

---

## Execution order & verification gates

1. **Phase 1** (app+php) — `cd eskoofy-app && composer test`, `./vendor/bin/pint --test`,
   then `diff -q` the 3 mirrored php views (must be byte-identical), `php -l` each.
2. **Phase 2-4** (theme) — `php -l` every touched theme file; `composer run lint` where
   feasible; visual spot-check via `docker/theme-test/`.
3. **Phases 5-6, 8** — require confirmation gate per `FEATURE-PROPAGATION.md` before
   starting; propagate via `build/propagate/propagate-feature.sh`.
4. **Phase 7** — php suite (`cd eskoofy-php && composer test`) green after API/model/
   scheduler additions; `php -l` all new files; app→php view byte-parity recheck.

## Re-audit after implementation

- App→php view parity recheck (see QA-PARITY-REPORT.md re-audit snippet).
- Theme sidebar slug registry vs sections (QA-UI-FRONTEND-REPORT.md re-audit snippet).