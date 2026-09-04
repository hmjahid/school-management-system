# Improvement Suggestions — 24-08-2026

> Compiled from `self-notes.md` and **verified against the current codebase**
> (routes, controllers, models, views, migrations). Each item is tagged:
>
> - **[✅ Done]** — implemented and functional
> - **[🔶 Partial]** — present but incomplete / has gaps
> - **[❌ Not done]** — not implemented (or broken/regressed)

## Status summary

| Area | Done | Partial | Not done |
|---|---|---|---|
| Admissions & Payment | 5 | 3 | 0 |
| Academic & Lifecycle | 3 | 5 | 2 |
| Attendance | 4 | 0 | 0 |
| Finance / Payroll / HR | 6 | 2 | 0 |
| Communication & Messaging | 3 | 2 | 0 |
| Library / Transport / Hostel | 3 | 0 | 0 |
| Public Website & CMS | 9 | 5 | 5 |
| UI/UX & Dashboard | 11 | 0 | 3 |
| Multi-language | 2 | 1 | 0 |
| PWA & Mobile | 1 | 0 | 0 |
| Settings & Config | 1 | 0 | 0 |
| Media & Content | 0 | 1 | 0 |
| Security & Compliance | 3 | 2 | 0 |
| Architecture | 2 | 3 | 4 |

---

## A. Features

### 1. Admission & Online Application
- [✅ Done] Public online admission form with multi-step flow (`/admissions/apply`, 6-step wizard in `admissions-apply.blade.php`).
- [✅ Done] Admission **on/off toggle** in backend; closed page + hero "admission → contact" swap (`AdmissionSetting.is_open`, `DashboardAdmissionController@toggleOpen`, hero designs).
- [✅ Done] Post-submission downloadable **payment receipt** (fee amount + payment number/biller code).
- [🔶 Partial] Application **status page** searchable by applicant ID/mobile — only **applicant ID** search works; mobile search not implemented.
- [🔶 Partial] Applicant submits **payment transaction ID** (works), but nothing ever sets `payment_status = verified` — the `verify-payment` route targets a non-existent controller method, so the **approval/confirmation letter is unreachable**.
- [❌ Not done] Admin list view of **unpaid applications with pending-payment indicator** (no payment-status column/filter on the list).
- [🔶 Partial] **bKash/Nagad/bank** integration configurable-once and reused for **fees**, but **not wired into admission** (admission uses manual offline transaction ID). Salary reuse unverified.
- [✅ Done] Customizable **admission notice** field in backend, rendered on apply page.

### 2. Academic & Student Lifecycle
- [❌ Not done] Student **promotion to next class** (only a permission label exists; no logic/UI).
- [✅ Done] Class / section / subject management (syllabus not separately verified).
- [🔶 Partial] **Routine/timetable management** — class routines done; **exam routines not implemented** (no type flag / CRUD).
- [✅ Done] **Assignment/homework**: class-specific tasks, **guardian notes** (guardians submit via student account), submission tracking + grading.
- [🔶 Partial] **Examination & result processing** — marks by class/section/subject, GPA config, auto-publish to website all done; **SMS push on publish not wired**.
- [🔶 Partial] **Public result page** (Class + Session + Roll, printable) works; **marksheet download missing from the public result page** (download from student dashboard + exam results page works).
- [🔶 Partial] **Document generation**: student ID card ✅ (separate), admit card ✅ (separate), certificates/TC ✅, testimonials ✅, invoices ✅ (printable receipt). **Seat plans ❌, progress reports ❌, selectable templates ❌**. All reflect school logo/name/address and are admin-editable.
- [🔶 Partial] **Dashboard notice page** exists; "urgent" flag was removed (by design); notices themselves do **not** sync to notifications — only announcements do.

### 3. Attendance
- [✅ Done] Student & staff attendance (biometric = architectural only, no device integration).
- [✅ Done] **Bulk attendance** working.
- [✅ Done] **Auto-SMS to guardians** on absence (conditional on `send_absence_sms` setting + template).
- [✅ Done] Daily dashboard widget with present/absent/late/leave counts.

### 4. Finance, Accounting & Payroll
- [✅ Done] **Fee management**: class/category structure, online collection, fee-type filter, table layout.
- [🔶 Partial] **Money receipt** ✅ auto-generated; **bank reconciliation ❌**; **due-reminder SMS ❌**.
- [🔶 Partial] **Expense management**: CRUD ✅; categories are free-text only (no category model/CRUD); **budget tracking ❌**.
- [✅ Done] **Chart of accounts & ledger** — journal voucher, cashbook, bankbook, ledgerbook, cash flow, income statement, balance sheet (ledger page works).
- [✅ Done] **Payroll & HR**: salary structure, payslips, leave application + approval workflow.

### 5. Staff & HR
- [✅ Done] Staff directory, roles, staff attendance, leave approval chain, payroll slips/increments/deductions.

### 6. Communication & Messaging
- [🔶 Partial] **Internal messaging** (teacher↔principal, student↔teacher, guardian↔teacher) ✅ works; **admin log of all messages/activities ❌** (no admin-wide listing, messages not written to activity log).
- [🔶 Partial] **Bulk SMS** (class/section/individual/role/all-users, just below Messages) ✅; **shift-targeting ❌** (only class/section/individual/role/all).
- [✅ Done] **Announcements/noticeboard**: audience, BN/EN fields, "show in" target (header/notification/both), urgent flag removed, translates with language change.
- [🔶 Partial] **Push notifications** infrastructure ✅ (Firebase/Log push, scheduled notifications, preferences); **triggers for attendance / result / fee-due / events not wired** (some commented out / TODO).
- [✅ Done] Dashboard **notifications page** listing announcements/notices with unread + preferences.

### 7. Library, Transport & Hostel
- [✅ Done] **Library**: catalog, issue/return, fines, categories, reports.
- [✅ Done] **Transport**: vehicle/route, student pickup points.
- [✅ Done] **Hostel**: room allocation, resident register.

### 8. Public Website & CMS
- [❌ Not done] **Per-section show/hide checkbox** on each section's own edit page — only a whole-page `is_active` / `section_visibility` toggle exists.
- [✅ Done] **Form-based CMS editing**, no JSON editor.
- [❌ Not done] **Important/ministry links** section in footer (hard-coded Quick Links instead).
- [✅ Done] **Social icons show/hide** (appear only when URL set + show flag).
- [🔶 Partial] Newsletter **still present** in footer; privacy/terms/sitemap links with BN labels ✅.
- [🔶 Partial] **Brand colors**: primary + secondary only (no separate accent); colors apply to public + dashboard on change.
- [❌ Not done] **Multiple theme styles** (only color/font/radius customization).
- [✅ Done] **Hero**: 6 selectable designs, notice box, header marquee (design-specific input toggling limited).
- [✅ Done] **Teachers slider** (3/2/1 responsive), see more/less on teachers page, "Faculty"→"Teachers".
- [🔶 Partial] **Committee page + homepage slider + CMS** ✅; **no show/hide settings toggle for committee**.
- [✅ Done] **Recent events/activities slider** above upcoming events, CMS-managed, demo content.
- [✅ Done] **Principal message** cleanup, image rendered, BN/EN.
- [🔶 Partial] **Website link** opens site in new tab ✅; "**Follow Us**" footer label not present/translatable.
- [✅ Done] **Homepage/section contents CMS-manageable** (a few section headers still from lang, not CMS).

### 9. UI/UX & Layout
- [✅ Done] Header **category-grouped menu** + responsive breakpoint (desktop ≥1367px, hamburger ≤1366px).
- [✅ Done] **Login page** professional with top/bottom padding.
- [✅ Done] **BN label = "বাংলা"** everywhere (header, CMS, settings, install-app, footer sitemap).
- [❌ Not done] **Remove theme-changer / dark-mode toggle** — still present in topbar + sidebar.
- [✅ Done] Modern enterprise **dashboard**; "Administrator tile behind dashboard tiles" bug not present in current code.
- [✅ Done] **Sidebar grouping** (academic subgroup, admission below academic, Users & Roles under Administration, Help & Documentation under Help group).
- [❌ Not done] **Calendar page** for all dashboard users — route exists but sidebar link is permission-gated (`viewAny Event`).
- [✅ Done] **Help & documentation** interactive, BN+EN.
- [✅ Done] **Search** (header/sidebar/website) works on click, not only Ctrl+K.
- [✅ Done] **Analytics dashboard** widgets (KPIs, revenue/expense chart, attendance, quick actions).

### 10. Dashboard & Admin Experience
- (Sidebar grouping, calendar, help/docs, search, analytics covered in §9.)
- [✅ Done] Enterprise admin shell: persistent sidebar, top bar with global search, quick actions, role-based widgets.
- [❌ Not done] Calendar available to **all** dashboard users (gated — see §9).
- [✅ Done] Analytics dashboard (student count, today's attendance, fee collection MTD, pending dues, notices, upcoming exams).

### 11. Multi-language & Localization
- [✅ Done] **Separate website (`locale`) and dashboard (`dashboard_locale`)** systems; dashboard menu changes with dashboard language.
- [🔶 Partial] App is **eligible for N languages** (config-driven), but the **switcher does not become a `<select>` when >2 languages** exist (renders button list only).
- [✅ Done] Full BN/EN for public + dashboard strings (exhaustive per-string audit not performed).

### 12. PWA & Mobile
- [✅ Done] **PWA**: manifest (name syncs to school name), service worker registered, **install-app option in sidebar** (non-header), BN install text on language change.

### 13. Settings & Configuration
- [✅ Done] Consolidated settings: School Setting (Website group, BN/EN name+tagline), General Setting (Configuration group: theme/localization/payment/library/academic/sms), CMS Setting page, Global Labels page, **timezone selection + live clock**, payment config linked from payment page, About-this-software page.

### 14. Media & Content Management
- [🔶 Partial] **Media library**: category + search filter, direct upload, browse-media popup, dark/light footer logos, gallery tab filtering — **date filtering not implemented**.

### 15. Security & Compliance
- [✅ Done] **Same user type cannot reuse mobile/email** (DB unique constraint on `phone`+`role_id`; unique email validation).
- [🔶 Partial] **Activity logs** for critical actions — admission/payment changes logged; **role changes NOT logged** (spatie pivot bypasses model events).
- [✅ Done] **Backup & restore** (DB + media zip, scheduled, list page).
- [🔶 Partial] Security review gaps: **SMS/payment credentials stored unencrypted**; **media upload lacks MIME validation**; authorization/CSRF largely OK.

---

## B. Architecture

### 1. Localization architecture
- [❌ Not done] Locale should resolve **centrally in `SetLocaleFromSession`** — but `AppServiceProvider::boot()` also calls `app()->setLocale(session('dashboard_locale'))`, a conflicting resolver. Remove the provider-level `setLocale`.

### 2. Theme & branding system
- [✅ Done] Single source of truth for brand colors in `WebsiteSetting`; applied as CSS variables; **decoupled from dark mode**.

### 3. CMS architecture
- [🔶 Partial] Form-based sections + working `section_visibility` toggles, but **section keys are hard-coded** in blades/CMS settings rather than auto-discovered from a single registry; per-section edit-page checkboxes (not whole-page) still missing.

### 4. Multi-institution / SaaS readiness
- [❌ Not done] No tenant scoping of settings/CMS/branding/languages (single-school assumption throughout).

### 5. Activity & audit logging
- [✅ Done] Reliable, queryable activity log (config + controller + API); limited by role-change gap above.

### 6. Code organization & cleanup
- [🔶 Partial] `unnecessary-files/` folder still present (not removed); duplicate migrations correctly kept guarded; redundant CMS sections not fully cleaned.

### 7. Security hardening
- [🔶 Partial] Authorization + CSRF OK; **encrypt SMS/payment credentials**, **add MIME validation to media uploads**, add uniform rate limiting on dashboard state-changing routes.

### 8. WordPress theme conversion path
- [❌ Not done] Documented path (`wordpress-theme-conversion.md`) to expose the public site as a customizable WordPress theme — not produced.

### 9. Scalability & hosting
- [🔶 Partial] Queue worker + cache basics present; no documented hosting/sizing guidance or CDN/media strategy for scale.

---

## C. Open questions / decisions to confirm
- Final branding name (separate `name-suggetions.md`) — affects sidebar label + PWA name.
- Whether v1 keeps student/guardian logins disabled (earlier note) or includes them (later note asks to add + demo credentials).
- Exact session-year format (`2026` vs `2025-2026`) — currently free-text `AcademicSession.name`.
- Education-ministry guidelines to follow for the public site.

## D. Highest-priority gaps to close next
1. **Admission payment verification** broken (ghost `verify-payment` route) → blocks approval letters.
2. **Remove conflicting `setLocale` in `AppServiceProvider`** (architecture).
3. **Encrypt SMS/payment credentials** + **media MIME validation** (security).
4. **Per-section show/hide** on CMS edit pages (whole-page toggle only today).
5. **Student promotion**, **exam routines**, **seat plans / progress reports**, **multi-theme styles** (academic + website).
6. **Calendar** available to all dashboard users (currently gated).
7. **Log role changes** in activity log.
