# Eskoofy Product Polish, Rebrand & Documentation — Prompt File

> This prompt file drives the full implementation. Execute the tasks in order below until
> everything is finished. Apply to the whole monorepo unless a task specifies one product.
> Ask for confirmation before making changes where the task says "confirm".

---

## Task 1 — Fix theme homepage redirecting to `/client`

**Status:** ✅ Already fixed in commit `c9def0d` (service worker cache poisoning).

**What happened:** The theme never emitted a `/client` redirect. A stale service worker
cached a poisoned navigation response. The fix bumped the PWA cache to `eskoofy-theme-v2`
and added an `isCacheable()` guard (only 2xx, non-redirected responses cached).

**Verify:**
- `eskoofy-theme/pwa/sw.js` → `CACHE_NAME = 'eskoofy-theme-v2'` + `isCacheable()` guard
- `eskoofy-theme/functions.php` → `/offline` served with HTTP 200
- No `wp_redirect('/client')` anywhere in `eskoofy-theme/`
- Browsers stuck on the old worker need one "Update + hard reload".

## Task 2 — Replace remaining `SchoolEase` branding with `Eskoofy`

**Status:** ✅ Done for app + raw-PHP source files (fallback names, sw cache keys, offline
pages, software/dev-credits page, READMEs, `.env` files). Tests pass (173 Feature tests).

**Concept:** SchoolEase is the legacy product name. Everything must read **Eskoofy**.

**Verify:**
- Run: `rg -i 'schoolease|school.ease' --glob '!archive/**' --glob '!storage/**' --glob '!docs/planning/**' --glob '!docs/UIUX-*' --glob '!docs/FEATURE-IMPROVEMENTS*' eskoofy-app eskoofy-php eskoofy-theme eskoofy-website`
- Only historical planning docs (`docs/planning/wordpress-theme-conversion.md`,
  `docs/UIUX-IMPROVEMENTS.md`, `docs/FEATURE-IMPROVEMENTS.md`) and `archive/` keep the old
  name as historical record — leave those.
- Re-check `eskoofy-app/archive/frontend/src/components/dashboard/Sidebar.jsx` — read-only
  legacy, leave as-is.

## Task 3 — Align theme version frontend + dashboard with the app version

### 3a. Dashboard sidebar group/items parity  ✅ (code done — verify rendering)

The theme sidebar (`eskoofy-theme/inc/admin-shell.php` → `esk_admin_sidebar_sections()` and
`esk_admin_shell_groups()`) must mirror the app sidebar
(`eskoofy-app/resources/views/partials/dashboard/sidebar.blade.php`) one-for-one:

| App section → group | Theme section → group (slugs) |
|---|---|
| Main → Dashboard, Messages, (Communications), Bulk SMS | Main → `esk-dashboard`, `esk-messages`, `esk-sms` |
| Academic → People (Students, Teachers, Parents, All Users) | Academic → People → `esk-students`, `esk-teachers`, `esk-guardians`, `esk-users` |
| Academic → Academics (Classes, Exams, Assignments, Class Routine) | Academic → Academics → `esk-classes`, `esk-exams`, `esk-results`, `esk-assignments`, `esk-routines` |
| Academic → Admissions | → `esk-admissions` (flat) |
| Academic → Daily (Attendance, Bulk mark, Staff attendance) | → Daily → `esk-attendance`, `esk-attendance-mark`, `esk-staff-attendance` |
| Academic → Finance (Fees, Payments, Expenses, Ledger) | → Finance → `esk-fees`, `esk-fee-payments`, `esk-expenses`, `esk-income-statement`, `esk-balance-sheet`, `esk-cash-flow` |
| Academic → HR (Leaves, Payroll) | → HR → `esk-leave-requests`, `esk-leave-types`, `esk-payroll`, `esk-payslips`, `esk-salary-structures` |
| Academic → Documents (Admit Cards, ID Cards, Certificates, Testimonials, Committee) | → Documents → `esk-admit-cards`, `esk-id-cards`, `esk-certificates`, `esk-testimonials`, `esk-committee` |
| Academic → Library (Books, Library Reports) | → Library → `esk-library`, `esk-library-reports` |
| Academic → Events, Transport, Hostel | → `esk-events`, `esk-transport`, `esk-hostels` (flat) |
| System → Activity log, Visitor logs, Backups | System → `esk-activity`, `esk-visitor-logs`, `esk-backup` |
| Website → Website CMS | Website → Website CMS → `esk-cms`, `esk-news`, `esk-gallery`, `esk-announcements`, `esk-notices`, `esk-documents`, `esk-media`, `esk-contact-submissions` |
| Administration → Users & Roles | Administration → Users & Roles → `esk-users` |
| Configuration → Settings, Reports, Bulk import/export | Configuration → `esk-settings`, `esk-reports`, `esk-bulk` |
| Help → About, Help & Documentation | Help → `esk-software`, `esk-help`, `esk-profile` |

All other registered `esk-*` pages (add-forms, sections, subjects, batches, sessions,
progress-reports, seat-plans, refunds, bank-recon, reports-builder, analytics, search,
onboarding, notifications, careers) stay **reachable via the Ctrl+K command palette**
(`esk_render_palette_data()` uses `esk_admin_shell_groups()`), matching the app where those
are also not shown in the sidebar.

**Verify:** `php -l eskoofy-theme/inc/admin-shell.php` and visually render `/dashboard/`.

### 3b. Public-site frontend parity  🟡 (in progress)

The theme's `style.css` already ships rich component classes (`esk-hero`, `esk-section`,
`esk-card`, `esk-gallery-grid`, `esk-event-list`, `esk-faq`…). The templates under-use them.
Bring templates to parity with the app views:

1. `archive-esk_news.php` — hero + featured article + card grid + pagination
2. `single-esk_news.php` — hero image w/ overlay, meta (reading time), share buttons, related
3. `archive-esk_events.php` — hero + past/upcoming filters + event list/cards
4. `archive-esk_galleries.php` — hero + category tabs + grid + lightbox
5. `archive-esk_notices.php` — hero + pinned badges + list
6. `template-admission.php` — hero + process timeline + fee table + FAQ (`esk-faq`)
7. `template-contact.php` — hero + map + opening hours + form
8. `header.php` / `footer.php` — minor polish (icons in nav, social links, mobile bar)

Match the app views under `eskoofy-app/resources/views/site/` (news, news-show, events,
gallery, notices, admissions, contact) for section order and content.
**Do not** change `style.css` unless a truly missing class needs adding.

## Task 4 — Document the whole repo (root + every product + website)

**Status:** 🟡 Root README + product READMEs exist and are strong. Finish by:
1. `docs/README.md` — add `COMPETITIVE-ANALYSIS.md`, `PAYMENT-MODEL.md`,
   `FEATURE-PROPAGATION.md` to the map.  ✅ (add new rows)
2. New `docs/FEATURE-PROPAGATION.md` — cross-product feature consistency system
   (Task 5).
3. Make sure each product `README.md` links to its `AGENTS.md` + relevant `docs/`.

## Task 5 — Cross-product feature-consistency system (confirm before applying)

Design (no execution of actual feature changes without confirmation):

1. **Three source-of-truth pointers** in `AGENTS.md` (root): when adding/changing a feature
   in `eskoofy-app`, the SAME behaviour must land in `eskoofy-php` + `eskoofy-theme`
   (and sale copy in `eskoofy-website`), unless the task explicitly scopes to one product.
2. **Feature-defined patterns:** every feature change ships with
   `docs/FEATURE-PROPAGATION.md` instructions that specify file paths per product
   (app → php view parity, app → theme template parity, app → website copy).
3. **Runner script** `build/propagate/propagate-feature.php` (or `.sh`) that reads a
   feature request, and BEFORE making any change prints the per-product file plan and PAUSES
   for explicit confirmation (`continue?` / `--yes`). The script never edits by itself; it
   just orchestrates and asks.
4. **Prompt template** `docs/prompts/features-impl-prompt-NN.md` (existing series) gains a
   per-product "Propagation" checklist section with columns
   `app | php | theme | website` and default = all boxes checked.

Deliverables: `docs/FEATURE-PROPAGATION.md` + `build/propagate/propagate-feature.sh` +
a `--dry-run` + `--yes` mode. Confirm before creating anything beyond the docs if unsure.

## Task 6 — Competitor research (suggestions file)

**Status:** ✅ Done → `docs/COMPETITIVE-ANALYSIS.md` (8 competitors, pricing models,
feature-showcase patterns, social proof, conversion funnel + 5 recommended patterns).

## Task 7 — Payment/subscription model (suggestions file)

**Status:** ✅ Done → `docs/PAYMENT-MODEL.md` (Freemium + 4 tiers: Community free /
School $29/mo / District $79/mo / Enterprise custom; Stripe + bKash rails; license server
via `eskoofy-website`; 14-day trial; revenue projections; key decisions).

---

## Definition of Done

- All 7 tasks complete (Status column: ✅/🟡 resolved to ✅, no ⛔ blockers).
- `cd eskoofy-app && composer test` → green.
- `cd eskoofy-theme && composer run lint` → no new violations beyond baseline.
- `php -l` clean on every touched theme file.
- No *new* `SchoolEase`/`schoolease` occurrences outside the exempted historical files.
- Repo-wide docs updated to match.