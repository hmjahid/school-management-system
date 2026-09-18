# QA UI & Frontend Parity Audit — eskoofy-theme vs eskoofy-app

**Audit date:** 2026-09-16
**Auditor:** Senior QA (10yrs) — dashboard/frontend UI/UX deep-dive following the
inventory audit in `QA-PARITY-REPORT.md`.
**Scope:** `eskoofy-theme` (WordPress) vs `eskoofy-app` (Laravel 12). Covers dashboard
**sidebar item order + accordion sub-items**, dashboard **UI/UX, layout, styles,
content**, public frontend **UI/UX, layout, styles, content**, and **functionalities**.
**Method:** source diffing of the two sidebars + topbar/page shells + all front-end
templates + route/payload inventory. No runtime E2E (no DB lifted in either product);
behavioral verdicts are code-inspection based.

**Severity scale**
- **P0** — broken / data-affecting / ships a dead control to users.
- **P1** — visible feature OR screen present in one product only (or actively broken).
- **P2** — ordering/naming/UX drift, reduced depth, dead code.
- **P3** — cosmetic / hygiene.

---

## Part 1 — Dashboard sidebar: item order & accordion sub-items

Sources: app `eskoofy-app/resources/views/partials/dashboard/sidebar.blade.php` (437 L);
theme `eskoofy-theme/inc/admin-shell.php` (render loop at `:489`, `esk_admin_sidebar_sections()`
at `:77`).

### 1.1 Side-by-side group order

| Order | App label | Theme label | Verdict |
|---|---|---|---|
| 1 | **Main** | **Main** | ✓ |
| 2 | **Academic** | **Academic** | ✓ |
| 3 | — | System | theme ok (app puts System later, see row 5) |
| 4 | **Website** (admin-only) | **Website** (no gate) | order ✓, gating ✗ |
| 5 | **Administration** (admin-only) | **Administration** (no gate) | order ✓, gating ✗ |
| 6 | **Configuration** (admin-only) | **Configuration** (no gate) | order ✓, gating ✗ |
| 7 | **Help** | **Help** | ✓ |

**App-only:** the app **System** label sits **after** Library/flat links (after Hostels,
before Website); the theme renders System label **third**, right after Academic. Section
heading order differs. **[P2]**

**App-only element:** the app shows a **Favorites** pinned group at the very top
(sidebar.blade.php:110-130, DB-backed, cross-device). **[P2]**

### 1.2 Main

| Item | App | Theme | Verdict |
|---|---|---|---|
| Dashboard | ✓ | ✓ | ✓ |
| Messages | ✓ (badge unread) | ✓ (badge via `esk_admin_shell_nav_badge`) | ✓ |
| SMS | `Communications` + `Bulk SMS`, gated by `can send_bulk_sms` (:142-145) | single `SMS` item, **unconditional** | **[P2]** theme shows SMS to everyone; label `Bulk SMS` vs `SMS` |

### 1.3 People

| Item | App | Theme | Verdict |
|---|---|---|---|
| Students | ✓ | ✓ | ✓ |
| Teachers | ✓ | ✓ | ✓ |
| Parents/Guardians | `Parents` (:171) | `Guardians` | naming drift P3 |
| Users | under People (`All Users`, admin-only :177) | `Users` inside People (:89) | ✓ (position matches) |
| Staff Directory | ✓ (:174) | **MISSING** | **[P2]** |
| RBAC per sub-item | each gated | none | P3 (design gap, see Part 5) |

### 1.4 Academics

| Item | App | Theme | Verdict |
|---|---|---|---|
| Classes | ✓ | ✓ | ✓ |
| Exams | ✓ | ✓ | ✓ |
| Results | `My Results` (:199) | `Results` | naming drift P3 |
| Assignments | ✓ | ✓ | ✓ |
| Routine | `Class Routine` (:205) | `Routines` | naming drift P3 |
| Sections / Subjects / Batches / Academic Sessions / Progress Reports / Seat Plans | (app pages exist under other routes) | views `sections.php` `subjects.php` `batches.php` `progress-reports.php` `seat-plans.php` exist but **NO sidebar entry** | **[P2]** orphaned nav (in `esk_admin_shell_groups()` but not rendered) |

### 1.5 Post-Academics flat items

| Item | App | Theme | Verdict |
|---|---|---|---|
| Admissions | ✓ flat, badge | ✓ flat | ✓ |
| — | — | **Events, Transport, Hostels placed here** | theme order drift (below) |

**Order drift [P2]:** app order is `Admissions → Daily → Finance → HR → Documents → Library → Events → Calendar → Transport → Hostels → System`. Theme order is `Admissions → Daily → Finance → HR → Documents → Library → Events → Transport → Hostels → System` but **Events/Transport/Hostels** come straight after Library **before** any System heading in the actual rendered list — no Calendar item at all.

### 1.6 Daily

| Item | App | Theme | Verdict |
|---|---|---|---|
| Attendance | ✓ | ✓ | ✓ |
| Bulk mark | `Bulk Mark` (:226) | `Attendance Mark` | naming drift P3 |
| Staff Attendance | ✓ | ✓ | ✓ |

### 1.7 Finance

| Item | App | Theme | Verdict |
|---|---|---|---|
| Fees | ✓ | ✓ | ✓ |
| Payments | `Payments` (:246) | `Fee Payments` | naming drift P3 |
| Expenses | ✓ | ✓ | ✓ |
| Ledger | ✓ (:258, can manage_chart_of_accounts) | **MISSING** (no Ledger/Chart-of-Accounts) | **[P1]** ledger absent |
| Income Statement / Balance Sheet / Cash Flow | under **Reports** (not sidebar) | **sidebar items** (:93) | **[P2]** theme adds 3 sidebar entries app keeps under Reports |
| Refunds | (back-office workflow) | `refunds.php` view exists but **no sidebar entry** | **[P2]** orphaned |

### 1.8 HR

| Item | App | Theme | Verdict |
|---|---|---|---|
| Leaves | ✓ `Leaves` (badge) | `Leave Requests` + `Leave Types` as **2 items** | naming/content drift P2 |
| Payroll | ✓ `Payroll` | `Payroll` | ✓ |
| — | — | `Payslips`, `Salary Structures` as sidebar items | theme extra (app has them under payroll page) P3 |
| Staff Directory | ✓ | **MISSING** in HR | **[P2]** |

### 1.9 Documents

| Item | App | Theme | Verdict |
|---|---|---|---|
| Admit Cards | ✓ | ✓ | ✓ |
| ID Cards | `Student ID Cards` | `ID Cards` | naming drift P3 |
| Certificates | ✓ | ✓ | ✓ |
| Testimonials | ✓ | ✓ | ✓ |
| Committee | `Committee Members` | `Committee` | naming drift P3 |

### 1.10 Library

| Item | App | Theme | Verdict |
|---|---|---|---|
| Books / Categories / Issues / Reports | 4 items (:330-333) | only `Library` + `Library Reports` | **[P2]** Books/Categories/Issues folded into single `Library` |

### 1.11 System

| Item | App | Theme | Verdict |
|---|---|---|---|
| Activity Log | ✓ (can view_audit_log) | ✓ | ✓ |
| Visitor Logs | ✓ | ✓ | ✓ |
| Backups | ✓ (can backup_database) | ✓ | ✓ |
| Notifications / Search | — | views exist (`notifications.php`, `search.php`?) but **no sidebar entry**; `esk-notifications`/`esk-search` in dead `groups()` | **[P2]** orphaned |
| Gating | **admin-only** group (`$isAdmin`, :348) | rendered for **all** users | **[P2]** RBAC leak (admins-only items visible) |

### 1.12 Website CMS

| Item | App | Theme | Verdict |
|---|---|---|---|
| All Pages / CMS Settings / Global Labels | 3 separate items (:369-371) | single `CMS` | **[P2]** folded |
| News/Events | `News/Events` | `News` | P3 |
| Gallery, Announcements, Notices, Documents, Media | ✓ | ✓ | ✓ |
| Form Submissions | `Form Submissions` | `Contact Submissions` | naming drift P3 |
| Careers | — (under CMS elsewhere) | sidebar item | theme extra P3 |
| **School Info** flat link | ✓ (:382, between Website & Administration) | **MISSING** | **[P2]** |

### 1.13 Administration

| Item | App | Theme | Verdict |
|---|---|---|---|
| Users & Roles group | Users + **Roles** + **Permissions** (:396-398) | Users **only** (:110) | **[P1]** roles/permissions absent |
| Gating | `can manage_users` | none | P3 |

### 1.14 Configuration + Help

| Item | App | Theme | Verdict |
|---|---|---|---|
| Settings / Reports / Bulk | ✓ | ✓ (`Bulk` vs `Bulk Import/Export`) | ✓ P3 label |
| About vs Software | `About` (dashboard.about) | `Software` (esk-software) | naming drift P3 |
| Help | ✓ | ✓ | ✓ |
| Profile | — (topbar user menu) | **sidebar item** `esk-profile` | theme extra P3 |

### 1.15 Sidebar findings summary

| ID | Sev | Finding |
|---|---|---|
| SB-1 | P2 | **Correction (post-audit):** `esk_admin_shell_groups()` (`admin-shell.php:50`) is **NOT dead code** — it powers the command palette (`admin-shell.php:450` `esk_render_palette_data()`, `:777`). **Second correction:** the 15 "orphaned" slugs (student-add, teacher-add, sections, subjects, batches, academic-sessions, progress-reports, seat-plans, refunds, bank-reconciliation, notifications, search, onboarding, reports-builder, analytics) are **palette-only in the app too** — the app sidebar (`sidebar.blade.php`) does not list them either. So the theme matches the app: both expose these via palette/direct URL, not the sidebar. Parity holds; no sidebar change required. |
| SB-2 | P2 | **Correction (post-audit):** the theme gates the **entire dashboard** behind `current_user_can('manage_options')` (`front-dashboard.php:220`) — non-admins get a 403 and never see the shell. The System/Website/Administration/Configuration groups rendering for "all users" is therefore unreachable by non-admins in practice. App instead admits several roles with per-item gating; theme restricts harder. Parity note (P3): theme could admit accountant/teacher/staff roles with per-group gating to match app UX, but there is **no admin-info leak**. |
| SB-3 | P2 | App-only: Favorites group, School Info flat link, Calendar flat link, Staff Directory (People + HR) |
| SB-4 | P2 | Theme-only sidebar items: Income/Balance/Cash-Flow, Leave Types, Payslips, Salary Structures, Profile (all expose features app nests deeper) |
| SB-5 | P2 | Library sub-items condensed 4 → 2; Website CMS condensed 10 → 9 |
| SB-6 | P3 | SMS unconditional in theme; label drifts (Guardians, Results, Routines, ID Cards, Fee Payments, Contact Submissions, Bulk, Software) |
| SB-7 | P3 | Accordion open/active logic differs: theme keys by single slug list (`admin-shell.php:514`, `in_array($current,…)`), app by `routeIs()` globs (`sidebar.blade.php:155`) |

---

## Part 2 — Dashboard UI/UX, layout, styles, content

Sources: theme `inc/admin-shell.php` (topbar `:559+`, palette `:661+`, footer `:542-555`),
`inc/admin-shell.css`, `inc/admin-style.css`, `inc/admin.js`, `views/admin/**`; app
`resources/views/layouts/dashboard.blade.php`, `partials/dashboard/topbar.blade.php`,
`resources/js/app.js`, `resources/css/app.css`.

### 2.1 Topbar / shell

| Aspect | App | Theme | Verdict |
|---|---|---|---|
| Control order (burger → clock → spacer → Website → Search → Language → Pin → Help → user) | ✓ | ✓ (bell⇄dark swapped, see below) | mostly ✓ |
| **Dark-mode vs notifications order** | dark toggle **then** bell (`topbar.blade.php:120-128`) | bell **then** dark (`admin-shell.php:594-601`) | **[P2]** swapped |
| Notification count badge | **no count** on bell (`topbar.blade.php:126`) | amber count pill (`admin-shell.php:596`) | **[P2]** theme>app (visual drift) |
| Language switcher | select/dropdown with checkmark (`topbar:75-104`) | single toggle link `?esk_lang=` (`admin-shell.php:581`) | P3 |
| User dropdown contents | Dashboard/Setup/Profile/Settings/Logout | same | ✓ |
| Top loading bar | ✓ identical animation | ✓ | ✓ |
| Clock | ✓ timezone aware | ✓ | ✓ |
| PWA install | `data-pwa-install` | `#esk-pwa-install` | ✓ |

### 2.2 Mobile behavior

| Aspect | App | Theme | Verdict |
|---|---|---|---|
| Sidebar drawer | backdrop overlay + tap-outside close + `body.overflow-hidden` (`dashboard.blade.php:109`, `topbar:167-182`) | slides only, **no overlay / no outside-close / no scroll-lock** (`admin-shell.css:534-543`) | **[P1]** theme mobile nav UX reduced |
| Table scroll | `overflow-x-auto` wrapper (`admin-data-table.blade.php:11`) | none — `wp-list-table` overflows small screens | **[P2]** |

### 2.3 Page shell / states

| Aspect | App | Theme | Verdict |
|---|---|---|---|
| Page headers | `<x-page-header>` (title/desc/actions) | legacy `h1.wp-heading-inline` + `page-title-action` + `hr` (`students.php:48-51`) | **[P2]** legacy chrome re-skinned |
| Breadcrumbs | rendered only when set (`dashboard.blade.php:117-128`) | always rendered (`admin-shell.php:626`) | P3 |
| Empty state | rich `<x-empty-state>` (icon/title/message/CTA) | plain `<td colspan>No students found.</td>` | **[P2]** |
| Pagination | app paginates list tables (`admin-data-table:36-40`) | students paginate; **fees/exams unpaginated** | **[P2]** |
| Badges | `<x-badge>` 6 variants | 30+ semantic `esk-badge-*` classes | P3 |
| Toasts | dismissible ×, 5s, top-center/mobile, right-desktop (`app.js:4-24`) | text-only, 3.2s always top-right + converts legacy `.notice` (`admin-shell.php:800-819`) | **[P2]** |
| Confirm modal | data-confirm + danger variant + backdrop close (+focus restore) (`app.js:31-60`) | inline `confirm()` interception, always brand-OK, icon (`admin-shell.php:927-980`) | **[P2]** |
| Command palette | flat rows w/ type icons+pills (`dashboard.blade.php:186-290`) | grouped by sidebar section (`admin-shell.php:1005`) | P3 |
| Dark mode | `.dark` class pre-paint | `body.esk-dark` pre-paint | ✓ same approach |
| App dark-on-dark | page-header h1/desc **lack `dark:` variants** (`page-header.blade.php:13,15`) | handled | **[P2]** app bug |

### 2.4 Styles

- App: **Tailwind v4 utilities** (`app.css`), lucide/Heroicons-style **SVG icons**.
- Theme: **compiled Tailwind copy** `inc/app-dashboard.css` + vanilla `admin-shell.css`; **dashicons** icons (`esk_admin_shell_icon()`).
- Rendering parity is strong; component stack differs by platform (P3, as designed).

---

## Part 3 — Public frontend: UI/UX, layout, styles, content

Sources: app `resources/views/site/*`, `partials/site/nav.blade.php` + `footer.blade.php`,
`routes/web.php`, `resources/css/app.css`; theme `front-page.php`, `header.php`,
`footer.php`, `template-*.php`, `archive-*/single-*`, `inc/shortcodes.php`, `style.css`.

### 3.1 Home — ✅

Both render the **same 14 sections in the same order** (Hero design-1..6 → Features →
Stats → Principal Message → Teachers → Committee → Testimonials → Remarkable Students →
Photo Slider → Events → News → Highlights → CTA → Partners). `section_visibility` +
`site_ui`/`esk_site_ui` fallbacks mirrored.

### 3.2 Navigation — group ORDER differs [P2]

App (`nav.blade.php:162-182`): About → Academics → **Contact → News**.
Theme (`header.php:67-109`): About → Academics → **News → Contact**.
Child item sets identical; top-level **News/Contact swapped**.

### 3.3 Footer — item sets differ [P2]

- App quick links (7): About, Academics, Admissions, Faculty, Committee, News, Gallery.
- Theme quick links (10): About, Academics, Admissions, **Apply online**, News & Events,
  Gallery, **Contact, Payments, Routine, Certificates**.
- Theme `Certificates` → `/certificates/` has **no public template** — dead link. **[P2]**
- Ministry links identical (4). P3: transport link only in theme fallback branch.

### 3.4 Per-page comparison

| Page | Verdict | Notes |
|---|---|---|
| Home | ✓ | 14-section order identical |
| About/Academics/Students | ✓ | CMS-driven both |
| Admission apply | **[P1]** | app: 6-step multistep (session/batch, guardian, photo, T.C./birth-cert/uploads, review); theme: single-page shortcode form (name/gender/DOB/phones/address only) `shortcodes.php:142-250` |
| Results | P2 | app: class+session+roll lookup + donut chart + print/PDF; theme: admission_number+exam_id, no chart/PDF (`shortcodes.php:25+`) |
| Fees/Payments | **[P1]** | app: full payments page (structures, gateways, history); theme: "login to pay" CTA card + sections (`template-fees.php`) |
| Faculty | P2 | app: search/filter/to-details; theme: avatar grid `display_name ASC`, no search/designation |
| Committee | P2 | app: intro + bios + email/phone icons; theme: photo/name/designation/phone only |
| Contact | P2 | app: settings-driven hours + CMS sections **before** cards + FAQ; theme: **embedded defaults, site_ui-overridable** (`:58` builds default `$opening_hours`; card reads `pages.contact_hours_value` from site_ui; table falls back to defaults only when empty), sections after form, FAQ may fall back `''` |
| News list | [P2] | app: Load More JS + **placeholder sidebar** (dummy "4/6/2" counts, `#` hrefs); theme: real `paginate_links()` |
| **News single** | **[P1] app bug** | app: `strip_tags()`+`nl2br()` kills article HTML, hardcoded "5 min read"; theme: `the_content()` real HTML + computed reading time + JSON-LD |
| Notices | P3 | app audience chips; theme pinned badge only |
| Events | ✓ | structural parity (upcoming/past + countdown) |
| Gallery | ✓ | category filters + lightbox |
| 404 | [P2] app bug | app: search form POSTs to `/news?q=` (wrong route) + reuses `admin-input`/`btn-primary`; theme: inner-hero + back-home |
| Search | ✓ | both (theme has 2 surfaces to reconcile) |
| Transport | P2 | app full page (routes, stops, fares, fleet); theme CMS-only |
| Login | P2 | app: 3 role-specific carlogins (no hero); theme: single login + hero/breadcrumb |
| **Portal** | **[P1]** | app: `/portal` full student/parent portal (tabs: Profile/Attendance/Exams/Fees/Routine/Dues/Calendar/Message, assignments, announcements — `portal.blade.php` 417 L); theme: CTA card + read-only student-profile shortcode (`shortcodes.php:362-415`), **no auth check on `[id]`** |

### 3.5 Frontend styling

| Aspect | App | Theme | Verdict |
|---|---|---|---|
| Stack | Tailwind v4 (Vite) | vanilla CSS `esk-*` classes | P3 |
| Fonts | Inter + Noto Sans Bengali (`app.css:11-13`) | Inter + Noto Sans Bengali enqueued (`functions.php:358-360`) + applied via `--esk-font-body` (`style.css:545`), Bengali stack override at `:540`; base reset `:34` is system-ui but body uses the var stack | ✓ |
| Brand tokens | `oklch` `--brand-*`/`--accent-*` | `--esk-accent` vars | ✓ |
| Dark mode | `.dark` pre-paint | `body.esk-dark` pre-paint | ✓ |
| Reduced motion / focus visible | ✓ | ✓ mirrored | ✓ |
| Reset isolation | n/a | `:where(body:not(.esk-admin-shell))` | ✓ |

---

## Part 4 — Functionalities (dashboard modules + public features)

Sources: theme `views/admin/*`, `inc/front-dashboard.php`, `inc/admin-ajax.php`,
`inc/rest-api.php`, `inc/shortcodes.php`, `inc/payment-gateways.php`; app
`routes/dashboard.php` (637 L), `routes/web|payments|refunds|admissions|students|admin/notifications.php`.

### 4.1 Capability matrix

| Module | App | Theme | Gap | Sev |
|---|---|---|---|---|
| Students | resource CRUD + attendance/results/fees sub-resources | add+edit, list **no row actions**, read-only detail tabs | row-level edit/delete/bulk missing | P2 |
| Teachers | resource CRUD | add-only form + list+search | no edit/delete/payslip | P2 |
| Classes | full | add + edit (`class-form.php`) | ✓ | — |
| Sections/Subjects/Batches | module pages | add + soft-delete | — (orphaned nav SB-1) | P3 |
| Exams | create + **publish toggle both ways** (`dashboard.php:326`) | add + **publish one-way** (no unpublish, `exams.php:65`) | no unpublish | P2 |
| Results | full workflow | marks upsert w/ pass-fail | — | P3 |
| Routines | full CRUD (`dashboard.php:496-504`) | add/delete only | no edit | P2 |
| Assignments | full CRUD + submissions + **grade** (`dashboard.php:506-516`) | add + soft-delete | no submissions/grade | P2 |
| Attendance | period+report | bulk upsert + lists + staff | ✓ | — |
| Fees | full CRUD + payment approve/cancel (`dashboard.php:329-341`) | **add only** + mark-paid + refund request | no edit/delete/approve | P2 |
| **Online fee payment** | initiate/status + public gateways/callback/webhook (`routes/payments.php`) | **status lookup only** (`shortcodes.php:246-360`); gateway classes exist but **unmounted** | **no real online payment** | **P1** |
| Expenses | full CRUD + export + categories + **budgets** | add + hard-delete + categories | no edit/budget/export | P2 |
| Refunds | full workflow | create/process/cancel | ✓ | — |
| Payroll | salary structures + payslips | generate + Mark Paid + leave approve/reject | ✓ | — |
| Leave | types + requests | approve/reject | ✓ | — |
| **Library** | books/categories CRUD + **return/fine/lost** (`dashboard.php:616-635`) | add book/issue/**return only**; `fine_per_day` set but **never applied** | **no fine/lost/edit** | **P1** |
| **Hostels** | full CRUD + rooms + **assignments** (`dashboard.php:558-571`) | add hostel/room + delete hostel | no allocation | **P1** |
| **Transport** | vehicles/routes CRUD + **assignments** (`dashboard.php:304-322`) | vehicles/routes add+delete only | no student-route assignment | **P1** |
| Admissions | public store + status + tests + verify-payment + toggle open | list + approve/reject/enroll | no test scheduling/verify-payment | P2 |
| Messages | communications index | compose/inbox/sent | ✓ | — |
| SMS | compose + **templates + due-reminder** (`dashboard.php:548-556`) | gateway settings + campaign + logs | no templates/due-reminders | P3 |
| News/Gallery/Notices/Announcements/Events | full CRUD + bulk | add + delete only | no edit/unpublish/bulk | P2 |
| Documents/Media | full CRUD vs upload/delete | upload/delete | documents no edit | P3 |
| Users/Roles/Permissions | full CRUD (`dashboard.php:585-604`) | WP users create/delete + role filter | **no roles/permissions** | P2 |
| CMS/Settings | per-page CMS + 10 tab settings incl. mail test/global labels | `esk_website_contents` replace + settings incl. gateways/fine | ✓ | — |
| Backup | create/download/restore/destroy | **same** | ✓ | — |
| Reports/Analytics | report + builder + ledger | read-only + income/balance/cash-flow | no export | P3 |
| Certificates/Admit/ID/Seat | full CRUD + batch + print/preview | template add/delete + **batch-generate only** | no single create/print | P2 |
| Testimonials | full CRUD + print | add + delete only | no edit | P3 |
| Committee/Careers | committee CRUD + careers status | add/delete + app list | no application status | P3 |
| Notifications | template CRUD + types/variables/prefs | list + mark_read only | no template mgmt | P3 |
| Contact submissions | index + export | mark read/delete/export | theme > app | — |

### 4.2 Top-10 functional gaps (app → theme)

1. **Online fee payment non-functional in theme** (`shortcodes.php:246-360` is a lookup only; `template-fees.php` is "login to pay").
2. **Transport**: no student/route assignments or stops (`transport.php`).
3. **Hostels**: no room edit + no student allocation (`hostels.php`).
4. **Library**: no fine collection / lost-book handling; `fine_per_day` unused (`library.php`).
5. **Content modules add+delete only**: no edit/unpublish/bulk (news/gallery/announcements/notices/events).
6. **Fees add-only**: no fee-structure edit/delete, no payment-approve flow.
7. **Exams publish one-way** — no unpublish.
8. **Routines & assignments**: no edit; assignments have no submissions/grade workflow.
9. **Admit/ID cards & certificates**: batch/template-only — no single create/edit/print/preview.
10. **No roles/permissions management**; expenses lack edit/budget/export; teacher edit missing; student list has no row actions.

**Positive**: Backup, Refunds, Payroll, Leave, Attendance, CMS/Settings (incl. gateway
config), Messages, Contact-submissions all reach parity.

---

## Consolidated findings register

| ID | Area | Sev | Finding | Evidence |
|---|---|---|---|---|
| P-U1 | Portal | P1 | Theme has no student/parent portal (app `/portal` full tabs portal); theme profile shortcode has no auth check | `site/portal.blade.php` vs `template-portal.php` |
| P-U2 | Payments | P1 | Theme online fee payment non-functional (lookup-only shortcode; gateways unmounted) | `shortcodes.php:246-360` vs `routes/payments.php` |
| P-U3 | Transport | P1 | No assignments/stops in theme transport | `transport.php` vs `dashboard.php:304-322` |
| P-U4 | Hostels | P1 | No room edit/allocation in theme | `hostels.php` vs `dashboard.php:558-571` |
| P-U5 | Library | P1 | No fine/lost; `fine_per_day` unused | `library.php` vs `dashboard.php:616-635` |
| P-U6 | Adm-apply | P1 | Theme single-page form (5 fields, no uploads/session) vs app 6-step multistep | `shortcodes.php:142-250` |
| P-U7 | News-single | P1 | **App bug**: `strip_tags()` destroys HTML; theme port is correct | `news-show.blade.php` |
| P-U8 | Mobile | P1 | Theme sidebar drawer lacks backdrop/outside-close/scroll-lock | `admin-shell.css:534-543` |
| P-U9 | Finance nav | P1 | Theme ledger/chart-of-accounts absent; income/balance/cash-flow shown as sidebar items (app nests under Reports) | admin-shell `sections():93` |
| P-U10 | RBAC | P1 | Users & Roles: theme Users-only (no Roles/Permissions pages); System/Website/Config groups visible to all | `admin-shell.php:110` vs `sidebar.blade.php:386-401` |
| P-U11 | Sidebar | P2 | Dead `esk_admin_shell_groups()` + 15 registered slugs orphaned (no nav), several with built views | `admin-shell.php:50` vs `sections():77` |
| P-U12 | Sidebar | P2 | App-only: Favorites, School Info, Calendar, Staff Directory; theme-only: Leave Types, Payslips, Salary Structures, Income/Balance/Cash-Flow, Profile | sidebar.blade vs sections() |
| P-U13 | Nav/footer | P2 | Nav News/Contact swapped; footer quick-link sets differ + theme dead `/certificates/` | `nav.blade.php` vs `header.php` |
| P-U14 | Module depth | P2 | Content modules + fees add+delete only; exams no unpublish; routines/assignments no edit/grade; certificates batch-only | views/admin/* |
| P-U15 | UI lib | P2 | Theme legacy WP list-table + `.notice` + `wp-heading-inline` chrome; app `<x-page-header>`/`<x-empty-state>`/`<x-admin-data-table>` | `students.php:48` |
| P-U16 | UI lib | P2 | Empty states, toasts, confirm modal, pagination (fees/exams) reduced in theme | dash shell vs app.js |
| P-U17 | Styles | P2 | App page-header lacks `dark:` variants (dark-on-dark app bug); theme no Bengali webfont | `page-header.blade.php:13` |
| P-U18 | Topbar | P2 | Dark⇄bell order swapped; app no notification badge | `topbar:120-128` |
| P-U19 | UX drift | P3 | Naming/labels: Guardians, Results, Routines, ID Cards, Fee Payments, Contact Submissions, Bulk, Software; single login vs 3 role logins; breadcrumbs always-on | various |

---

## Positive confirmations worth preserving

1. Home page: 14 sections, same order, `section_visibility` + CMS-driven hero design-1..6 matched.
2. Dashboard shell chrome (loading bar, clock, palette trigger, user menu, PWA install, dark-mode toggle) all present + functionally aligned.
3. Events / Gallery / Terms / Privacy / About pages: structural parity.
4. Ministry links identical; OG/PWA/theme-color hooks present on both.
5. Backup, Refunds, Payroll, Leave, Attendance, Messages, Contact-submissions at functional parity with the app.

---

## Recommended remediation (priority)

1. **[P1] App-side quick fixes (highest ROI, zero app work):** fix `news-show` HTML loss; fix 404 search-target route; add `dark:` to page-header. (Bug parity fix later.)
2. **[P1] Theme payments:** mount `payment-gateways.php` into `esk_shortcode_fees_payment` so online payment actually initiates, or carve scope with owner sign-off.
3. **[P1] Theme transport/hostels/library depth:** add assignments (transport/hostels) and fine/lost (library) to reach app parity.
4. **[P1] RBAC:** theme Users&Roles → add roles/permissions page (or WP-role-based view-gating), and gate System/Website/Administration/Configuration + SMS by capability.
5. **[P2] Sidebar alignment:** delete/prune dead `esk_admin_shell_groups()`, render all registered slugs (or remove orphaned views), align Schools Info + Calendar + Favorites, fold theme-only items back into their app-equivalent parents.
6. **[P2] Theme UI lib:** `<x-page-header>`-equivalent headers, empty states, dismissible toasts, pagination for fees/exams.
7. **[P1] Portal + admission-multistep** for theme (needs `FEATURE-PROPAGATION.md` confirmation gate — biggest scope).
8. Every change ships via `build/propagate/propagate-feature.sh` with app `composer test` + theme `php -l`/phpcs gates.

---

## Re-audit commands

```bash
# sidebar slug registry vs rendered sections
rg -o "esk-[a-z-]+" eskoofy-theme/inc/admin-shell.php | sort -u
# rendered sub-items
sed -n '77,122p' eskoofy-theme/inc/admin-shell.php
# app sidebar routeIs globs
rg -o "request\(\)->routeIs\('[^']+'" eskoofy-app/resources/views/partials/dashboard/sidebar.blade.php
# orphaned theme views (views present but slug not in sections)
ls eskoofy-theme/views/admin/
# app dark-less headings
rg -n "text-slate-9|text-slate-6" eskoofy-app/resources/views/ --include=*.blade.php | rg -v "dark:"
# theme dead link check
rg -n "certificates" eskoofy-theme/footer.php eskoofy-theme/header.php
```