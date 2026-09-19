# Multi-Product Variant Blueprint — "Laravel app as the reference product"

> Status: **living document** · Owner: TBD · Version: 1.0
> Complements `WORKPLAN.md` (phases/gates), `docs/design/FEATURE-PROPAGATION.md`
> (runner `build/propagate/propagate-feature.sh`), `docs/design/NODEJS-VARIANT.md`
> (Node-specific feasibility), and `docs/parity/product-parity.md` (the drill-down
> audit that records what matches / mismatches today).

## 1. Purpose

Any future Eskoofy product/variant — e.g. `eskoofy-node` (proposed), a new
`int`-style profile, an API-first client, or a desktop/mobile shell — must be
built **from `eskoofy-app` as the source of truth**. The app holds the canonical
routes, views, language strings, schema, services, and tests. This blueprint
explains how to stand up a variant with the app as reference, in phases with
gates, and adds the parity checklist used to close app↔php↔theme gaps.

## 2. Principles (from root `AGENTS.md`)

1. **App first.** Implement/verify a feature in `eskoofy-app`, then propagate.
2. **Parity is feature-complete identical output**, not "similar":
   - `eskoofy-php`: `resources/views/**` + `lang/**` **byte-identical** to app;
     `routes/api.php` mirrors app `route:list`.
   - `eskoofy-theme`: mirrored `views/admin/*.php` + `inc/front-dashboard.php`
     routes + `inc/admin-shell.php` (title/icon/sidebar) + `inc/database.php`
     for table changes.
   - Default scope of any change = **all products**; scope explicitly otherwise.
3. **Variants are build-time profiles of one codebase** (BD/INT via
   `config/eskoolfy.php` + `build/profiles/*`). Never `if(bd)` branching.
4. **Additive merges only** while parity debt exists (nothing gets deleted first).
5. **Every product verifies itself before merge** (commands in §6).

## 3. What "the app is the ideal" means concretely

| App artifact | Role in the app | What a variant mirrors |
|---|---|---|
| `routes/dashboard.php` | all admin routes (auth + `permission:*` / `role:admin` middleware) | route list — a parity contract |
| `resources/views/partials/dashboard/sidebar.blade.php` | sidebar groups/items + `route-is` active states | sidebar structure, item order, permissions |
| `resources/views/dashboard/index.blade.php` | dashboard home (stat cards, charts, quick actions, workbench, setup banner) | dashboard content |
| `resources/views/dashboard/settings/*` | settings tabs incl. **Global Labels** (`dashboard.settings.global-labels`) | settings UI + label-override behavior |
| `lang/{en,bn}/dashboard.php` (+ `site_frontend.php`) | admin + public strings | i18n parity (en + bn) |
| `app/Http/Controllers/Web` + `Api`, `app/Services` | behavior + domain rules (`SetupChecklistService`, API envelope via `StandardizeApiResponse`) | behavior contract; API `{success,message,data[,meta]}` |
| migrations (schema of record) | tables + indexes | schema parity (`inc/database.php` in WP) |
| `config/eskoolfy.php` + `build/profiles/*` | BD/INT differences | profile system in the variant |
| `tests/` (923 passing) | behavioral contract | ported acceptance tests |

**Gotchas carried forward:** the app keeps two class tables on purpose
(`classes` legacy via `App\Models\ClassModel`/`Grade`, `school_classes` via
`SchoolClass`) — do not "clean up" without a data migration ticket; several
duplicate/guarded migrations are intentional. Exams use `batch_id` +
`academic_session_id` + `section_id` (no `class_id`). Exam "fully published" =
`is_published` **and** status `published`.

## 4. Baseline parity debt found this cycle (`docs/parity/product-parity.md`)

Status after the merge (all **fixed** in this cycle, kept as regression list):

- [x] Theme topbar: login/logout removed; auth = sticky nav + mobile bar
      (Dashboard → Logout), matching app mobile bottom bar.
- [x] Social icons: all 5 platforms (facebook/instagram/twitter/youtube/linkedin)
      work in theme header/footer via `esk_social_profiles()` (+ `social`
      settings tab, brand-600 hover CSS).
- [x] app+php sidebar: Finance group gained Expense Categories, Budgets, Bank
      Reconciliation, Income Statement, Balance Sheet, Cash Flow
      (`@can('manage_expenses')`); Configuration gained Reports Builder +
      Analytics; lang `en`+`bn` keys added; php copied byte-identical.
- [x] Theme sidebar: + CMS Settings, Global Labels (settings-tab deep links);
      Global Labels settings tab added; `esk_site_ui()` honors saved overrides.
- [x] Theme dashboard: setup/onboarding banner ported (6-item checklist;
      dismiss via localStorage `dc_setup_reminder_dismissed`), app normally
      gates Workbench while the theme always shows it (kept; document decision).

Outstanding (tracked separately, **not** fixed): theme-only tokens with no app
route (`leave-types`, `salary-structures`, `refunds`, `tools`, `cache`,
`sections`, `subjects`, `batches`, `academic-sessions`, `progress-reports`,
`seat-plans`) — richer sidebar than app by design; decide whether to promote to
app or hide in theme behind caps.

## 5. Phased roadmap for a new variant

### Phase 0 — Scaffold & decision gate
- [ ] Pick product folder (`eskoofy-node/` per NODEJS-VARIANT.md, or host / target
      profile for php per WORKPLAN gate 0.2). **Gate:** a named target market/host.
- [ ] Copy repo conventions: own `AGENTS.md`, `composer test`-style test command,
      lint command, CI parity check.
- [ ] Wire variant into `build/` (profiles + `export.sh`) and vote on first scope.

### Phase 1 — Schema & data model
- [ ] Mirror app migrations to the variant (tables, FKs, guarded duplicates rule).
- [ ] Port seeders (`WebsiteSettingSeeder` demo URLs, payment gateways, roles).
- [ ] **Gate:** schema diff script passes; `migrate:fresh --seed` equivalent clean.

### Phase 2 — Auth, roles & permissions
- [ ] Portal login/logout, roles (admin/teacher/staff/accountant/librarian →
      parents/students portal), spatie-equivalent permission checks.
- [ ] **Gate:** login → dashboard renders for each role.

### Phase 3 — Dashboard + sidebar parity
Use `docs/parity/product-parity.md` as the checklist. Every sidebar item in the
app must exist in the variant at the same group/order with the same permission
gate; every dashboard block (stat cards, Revenue vs Expenses, Today's
Attendance, Attendance Trend, Quick Actions, Workbench, setup banner) present.
- [ ] **Gate:** side-by-side screenshot/diff review passes (one per group).

### Phase 4 — Module CRUD parity (priority order)
Students → Teachers → Parents/guardians → Classes/batches/sessions →
Attendance (mark + bulk + staff) → Fees/payments/expenses/ledger → Exams &
results (batch-based) → Documents (admit/ID/certificates) → Library →
Events/Calendar → Transport → Hostels → Website CMS (pages, news, gallery,
announcements, notices, documents, media, form submissions, careers) →
Notifications/messages (templates + preferences) → Bulk SMS →
HR/payroll (leaves, payslips, salary structures) → Reports + Reports Builder +
Analytics → Settings (tabs incl. Global Labels) → Onboarding/setup checklist.
- [ ] Each module: route list parity + CRUD parity + permission parity.
- [ ] **Gate:** feature-matrix diff re-run: 0 missing items.

### Phase 5 — i18n + profiles
- [ ] `en` + `bn` string parity (wg `lang/**` mirrors).
- [ ] BD/INT profile config parity (gateways, ministry links, bKash/Rocket/Nagad
      vs PayPal/Stripe/Paddle) — **config/data only, no variant branching code**.
- [ ] **Gate:** lint rejects hardcoded variant conditionals.

### Phase 6 — API parity
- [ ] `/api/v1/*` envelope `{success,message,data[,meta]}` (mirror
      `StandardizeApiResponse`); webhook/callback paths never rewrapped.
- [ ] **Gate:** contract tests pass against app test fixtures.

### Phase 7 — Test gate + build-box
- [ ] Port acceptance tests; run full suite (app: `composer test`, 923).
- [ ] Wire `build/export.sh` + `build/propagate/propagate-feature.sh` for the new
      product; add php `routes/api.php` ↔ app `route:list` diff check.
- [ ] **Gate:** CI parity matrix green; export produces a runnable artifact.

## 6. Verification commands every producer runs before merge

| Product | Command |
|---|---|
| app | `cd eskoofy-app && composer test` (+ `./vendor/bin/pint --test`) |
| php | `diff -r` views/lang vs app; `php -l` changed files; route:list diff |
| theme | `php -l inc/*.php views/admin/*.php`; `composer run lint` (CS not gated); regenerate `inc/app-dashboard.css` from app Tailwind build when utilities change |
| any | `build/propagate/propagate-feature.sh <feature>` dry-run |

## 7. Open questions (owners)

- [ ] Promote theme-only HR/System/Documents tokens to app routes, or hide in theme?
- [ ] App Workbench gating vs theme always-visible Workbench — unify?
- [ ] Global Labels: app stores per-page label maps; theme exposes a curated
      subset — acceptable drift, or build the full per-page editor into the theme?
- [ ] Node variant concrete stack + market (blocks Phase 0 gate), ref
      `docs/design/NODEJS-VARIANT.md`.