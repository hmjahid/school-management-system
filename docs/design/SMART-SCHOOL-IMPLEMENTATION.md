# Smart School Management System — Implementation Plan

**Date:** 2026-09-16
**Status:** `[ ] proposal` · planning only — no code shipped yet
**Scope:** All Eskoofy products (Laravel app = source of truth + `eskoofy-php` port +
`eskoofy-theme`) and the marketing copy on the **branding website** (`eskoofy-website` —
**not a product**, only sells/markets the products).
**Status key:** `[ ] todo` · `[~] in progress` · `[x] done`

> **Confirmation gate:** this plan is the *proposal*. Before ANY feature below is
> implemented, the per-product file plan must be presented and confirmed per
> `docs/design/FEATURE-PROPAGATION.md`. Nothing is applied silently across products.
> **BD/INT rule:** every smart feature is config/data-driven (`config/eskoolfy.php` +
> `build/profiles/*`), never hardcoded `if (variant)` branching in code.

---

## 1. Vision

"Smart school management" = the existing 42-module management system **plus three
layers of intelligence**, layered so schools can adopt as much or as little as they want:

| Tier | Name | Nature | Requires cloud/AI? | Default |
|---|---|---|---|---|
| **T1** | **Automation engines** | Deterministic rules (solver, schedulers, reminders, rollovers) | No | **On** (both variants) |
| **T2** | **Analytics & prediction** | Statistics over existing data (risk scores, forecasts) | No (pure math on DB) | On, opt-out |
| **T3** | **AI/LLM assistant** | Optional external model (lesson plans, remarks, drafting) | Yes (API key, opt-in) | **Off** — even INT starts off |

T1 is the "smart automation" spine: auto timetables, auto attendance rollup, auto
promotions, auto fee reminders, auto salary. T2 turns the same data into foresight
(at-risk students, cash-flow forecast). T3 is a governance-gated convenience layer.

---

## 2. Guiding principles

1. **Deterministic first.** T1/T2 are pure math on the local DB — offline, auditable,
   testable, and identical across products. T3 never becomes a hard dependency.
2. **Idempotent automation.** Every scheduled engine can re-run without double effects
   (attendance already rolled, fee reminder already sent). Audit trail in
   `automation_logs`.
3. **Feature parity.** App is source of truth; the raw-PHP port mirrors controllers +
   views byte-for-byte where the feature is server-rendered; the theme gets the same
   smart features through `esk-*` tables + `inc/` handlers; the website only gets
   **marketing copy** (never code parity for internal engines).
4. **BD/INT = config.** Tier on/off, reminder day offsets, risk thresholds, AI provider,
   SMS driver all live in `config/eskoolfy.php` overridden by `build/profiles/*`.
5. **Privacy default-off.** `eskoofy-app/archive/` and historical docs are never inputs
   to T3. **No student PII (names, phone, guardian data) is ever sent to an external AI**
   service — only aggregated/non-identifying context, and only when the school admin
   explicitly enabled the feature for their account. Per-school opt-in, per-product flag.
6. **No vendor lock-in.** T3 provider is a config value (`provider`, `model`, `base_url`,
   `key`) — OpenAI-compatible HTTP is the contract.
7. **Confirmation before implementation** per `docs/design/FEATURE-PROPAGATION.md` — every stage
   below ships its per-product file map first.

---

## 3. Feature catalog

Legend: **T** tier · **P** priority (P0 = core automation, P1 = valuable, P2 = nice) ·
**V** variant (`both` / `int-first`).

### 3.1 T1 — Automation engines

| # | Feature | Module | P | V | Config flag | Notes |
|---|---|---|---|---|---|---|
| S1 | Auto timetable generator | Academics | P0 | both | `smart.timetable_auto` | Constraint solver: teachers/rooms/periods, no conflicts, gap-minimize; regen on change with preserved lock. |
| S2 | Attendance auto-rollup + anomaly flags | Daily | P0 | both | `smart.attendance_auto` | Batch-mark by roll/QR/IC; odd-weekend/late-marker flags; daily rollup job. |
| S3 | Auto exam seating & result pipeline | Academics | P0 | both | `smart.exam_auto` | Extend existing seat plans to auto-conflict-free room assign; auto grade/GPA/pass-fail pipeline. |
| S4 | Auto promotion / rollover | Academics | P0 | both | `smart.promotion_auto` | Move batch by term with configurable pass criteria; upgradable, gap-aware report. |
| S5 | Fee auto-billing + smart reminders | Finance | P0 | both | `smart.fee_auto` | Recurring profiles → invoice auto-create; SMS/email at day-offsets; late-fee auto calc; receipt auto-gen. |
| S6 | Auto payroll from attendance/leave | HR | P0 | both | `smart.payroll_auto` | Salary calc from sanctioned leaves + attendance; payslip bulk PDF. |
| S7 | Admission eligibility + waiting list queue | Admissions | P1 | both | `smart.admission_queue` | Auto rank eligible applicants; auto-offer when a freed seat matches. |
| S8 | Library due-date + fine engine | Library | P1 | both | `smart.library_auto` | Due-day reminders; auto fines; hold-request queue. |
| S9 | Transport route/stop suggestions | Transport | P2 | int-first | `smart.transport_auto` | Address→stop grouping; capacity check; export. |
| S10 | Smart notification scheduler | System | P1 | both | `smart.notifications` | Event RSVP reminders, celebratory notices, iCal export (opt-in). |

### 3.2 T2 — Analytics & prediction

| # | Feature | Module | P | V | Config flag | Notes |
|---|---|---|---|---|---|---|
| S11 | At-risk student detection | Academics | P0 | both | `smart.at_risk` | Attendance + grade trend + behavior flags → risk score + guardian flag (via existing notification channels). |
| S12 | Fee collection forecast | Finance | P1 | both | `smart.fee_forecast` | Cash-flow projection by profile/due date; overdue buckets. |
| S13 | Enrollment/seat forecast | Admissions | P2 | int-first | `smart.enrollment_forecast` | Admissions trend → capacity warnings. |
| S14 | Staff workload analytics | HR | P2 | both | `smart.workload` | Periods/classes/roles load map. |

### 3.3 T3 — AI/LLM assistant (opt-in, OFF by default)

| # | Feature | Module | P | V | Config flag | Notes |
|---|---|---|---|---|---|---|
| S15 | AI lesson-plan & question-paper generator | Academics | P1 | int-first | `ai.generator` | Prompt from syllabus/skill list only — no student data. |
| S16 | AI report-card remarks | Academics | P1 | both | `ai.remarks` | Inputs = marks/attendance aggregates (no names/PII); human review required before print. |
| S17 | Staff AI assistant (Q&A over anonymized stats) | System | P2 | int-first | `ai.assistant` | Answers from aggregated school stats; row-level PII never sent. |
| S18 | AI document drafting (notices/letters) | Documents | P2 | int-first | `ai.drafting` | Template + variables → draft; human approves. |

---

## 4. Architecture

### 4.1 App (source of truth) — `eskoofy-app`

```
app/Services/Smart/
  Automation/           engines (Timetable, Attendance, Promotion, Exam, Fee, Payroll…)
  Analytics/            risk + forecast calculators (pure functions)
  Ai/                   provider client (OpenAI-compatible), no PII policy guard
  Contracts/            engine interface (handles dry-run, idempotency keys)
app/Console/Commands/   one command per scheduled engine (e.g. smart:fee-reminders --dry-run)
database/migrations/    automation_logs (+ nothing schema-breaking; reuse existing tables)
resources/views/dashboard/smart/**   admin UI per feature (parity target)
routes/web.php          dashboard.smart.* routes (parity target)
```

Scheduling: Laravel scheduler (`bootstrap/app.php`/Kernel) runs engines; every engine
supports `--dry-run` and an idempotency key (hash of scope+period stored in
`automation_logs`).

### 4.2 Raw-PHP port — `eskoofy-php`

- `app/Controllers/**` mirror each smart controller; `app/Services/Smart/**` mirrors
  engines (pure PHP port, **same algorithm constant-per-period so results match**).
- `resources/views/dashboard/smart/**` stays **byte-identical** Blade.
- Routes added to `routes/web.php` per the parity rule (app `route:list`).
- Scheduling via cron; idempotency via `automation_logs` schema port.

### 4.3 WordPress theme — `eskoofy-theme`

- `inc/database.php`: `smart_options` + `esk_automation_logs` tables.
- `inc/admin-ajax.php` / scheduler hooks (WP-Cron escrow): same engines (PHP port).
- `views/admin/smart-*.php` + `inc/admin-shell.php` (title/icon/sidebar group) parity with
  app dashboard routes.
- Not every T3/AI feature is a theme priority — T1/T2 are; T3 stays app/int-first until
  product decide (documented in the propagation matrix).

### 4.4 Branding website — `eskoofy-website` (NOT a product)

- Copy only: `/features` mentions "Smart automation & analytics"; pricing tier copy
  for an optional "Smart" add-on per `docs/design/PAYMENT-MODEL.md`; `/products/*` feature lists.
- **Code parity does not apply.** Website never implements internal engines.

### 4.5 Config & variants

`config/eskoolfy.php` gains:

```php
'features' => [
    'smart' => [
        'timetable_auto'  => env('ESKOOFY_SMART_TIMETABLE', true),
        'attendance_auto' => env('ESKOOFY_SMART_ATTENDANCE', true),
        'exam_auto'       => env('ESKOOFY_SMART_EXAM', true),
        'promotion_auto'  => env('ESKOOFY_SMART_PROMOTION', true),
        'fee_auto'        => env('ESKOOFY_SMART_FEE', true),
        'payroll_auto'    => env('ESKOOFY_SMART_PAYROLL', true),
        'at_risk'         => env('ESKOOFY_SMART_AT_RISK', true),
        'reminder_days'   => [3, 7, 14],          // S5 offsets
        'risk_threshold'  => env('ESKOOFY_SMART_RISK_THRESHOLD', 0.7),
    ],
    'ai' => [
        'enabled'  => env('ESKOOFY_AI_ENABLED', false), // OFF default (all variants)
        'provider' => env('ESKOOFY_AI_PROVIDER', 'openai-compatible'),
        'base_url' => env('ESKOOFY_AI_BASE_URL'),
        'model'    => env('ESKOOFY_AI_MODEL', ''),
        'key'      => env('ESKOOFY_AI_KEY'),
        'features' => ['generator' => false, 'remarks' => false, 'assistant' => false, 'drafting' => false],
    ],
],
```

Profiles (`build/profiles/*`): `bd` = T1/T2 on, `ai.enabled=false`. `int` = T1/T2 on,
`ai.enabled` optional per-tenant. **No `if (variant == 'bd')` in code.**

### 4.6 New schema (all products)

`automation_logs` (id, engine, scope, period, idempotency_key UNIQUE, run_at, run_status,
meta_json). Everything else reuses existing tables — no risky migration, roll-forward only.

---

## 5. Implementation stages

### Stage 1 — T1 core engines (only after confirmation)

| # | Task | Files / Notes | Verification |
|---|---|---|---|
| 1.1 | Automation engine contract + `automation_logs` | App: `Services/Smart/Contracts/*` + migration; php: schema port; theme: `esk_automation_logs`. | `composer test` green; migration idempotent; dry-run support proof. |
| 1.2 | Attendance auto-rollup + anomaly flags (S2) | App engine + command + dashboard views; php mirror; theme hook. | Feature test: double-run adds no duplicates; weekend flag correct. |
| 1.3 | Smart fee reminders (S5) | Engines + notification templates (SMS/email) + admin UI; copy to website. | Idempotency test (reminder sent once per period); `sms` driver respected (bd log / int twilio). |
| 1.4 | Auto seat exam + result pipeline (S3) | Extend existing exam seat-plan feature; auto grade/GPA. | Exam feature suite green; seats conflict-free; marksheet regression. |
| 1.5 | Payroll automation (S6) | Engine + payslip bulk PDF. | Salary calc test vs leave/attendance matrix. |
| 1.6 | Promotion / rollover (S4) | Solo engine + preview report + confirm. | Dry-run output equals confirm apply; no data lost. |
| 1.7 | Timetable generator (S1) | Constraint solver (pure PHP/TS-portable algorithm). | Solver test: no teacher/room double-booking in generated plan. |
| 1.8 | Admin "Smart" dashboard group | Sidebar group + overview page showing engine status/logs. | Sidebar parity app↔php↔theme; route parity asserted. |

**Stage 1 exit criteria:** all P0 engines live in app + php (byte-identical views) + theme
T1 hooks; every engine idempotent + dry-run capable; full suites green; commerce copy done.

### Stage 2 — T2 analytics

| # | Task | Files / Notes | Verification |
|---|---|---|---|
| 2.1 | At-risk detection (S11) | Pure-function calculator + dashboard widgets + guardian flag. | Unit test: known fixture scores above/below threshold. |
| 2.2 | Fee forecast (S12) | Projection from profiles/due dates; overdue buckets. | Forecast matches a hand-computed fixture. |
| 2.3 | Enrollment/seat forecast (S13) | Admissions trend → capacity warning. | Fixture-based test. |
| 2.4 | Workload analytics (S14) | Load map chart. | Fixture-based test. |

**Stage 2 exit criteria:** analytics render from real data, `risk_threshold` config-driven,
copy added to website (`/features`, pricing).

### Stage 3 — T3 AI assistant (opt-in)

| # | Task | Files / Notes | Verification |
|---|---|---|---|
| 3.1 | Provider client + no-PII guard | `Services/Smart/Ai/` client; sanitizer that strips PII before any request; per-tenant `ai.enabled` flag. | Unit test: sanitizer removes names/phone/guardian fields from outbound payload. |
| 3.2 | Lesson/question generator (S15) | Prompt template factory (no student data). | Golden-prompt test; provider mocked. |
| 3.3 | Report-card remarks (S16) | Aggregate-only inputs; human-review gate before print. | No-PII assertion; review required in UI flow. |
| 3.4 | Staff assistant (S17) + drafting (S18) | Anonymized stats context only. | Same no-PII guard; rate-limit + audit log. |
| 3.5 | Website copy | `/features`, pricing "AI add-on" tier per `PAYMENT-MODEL.md`. | Copy approved. |

**Stage 3 exit criteria:** `.env`-gated OFF by default; per-school opt-in; PII sanitizer is
battle-tested; every T3 action logged to `automation_logs`.

---

## 6. Propagation matrix (per `docs/design/FEATURE-PROPAGATION.md`)

| Change | eskoofy-app | eskoofy-php | eskoofy-theme | eskoofy-website |
|---|---|---|---|---|
| Engine/controller | ✅ source | ✅ port | ⚪ T1/T2 yes, T3 app-first | — |
| `/dashboard/smart/**` views | ✅ | ✅ byte-identical | ✅ `views/admin/smart-*.php` | — |
| Config flags | ✅ `config/eskoolfy.php` | ✅ `config/**` merged | ⚪ theme options / `esk_*` tables | — |
| Scheduler/cron | ✅ scheduler | ✅ cron | ✅ WP-Cron escrow | — |
| New schema | ✅ migration | ✅ `database/schema/**` | ✅ `inc/database.php` | — |
| Marketing/feature copy | — | — | — | ✅ `/features` + `/pricing` + `/products/*` |

> ⚪ = scope decision documented per feature (T3 theme parity deferred to product owner).

---

## 7. Privacy & security constraints (non-negotiable)

1. `ai.*` is `false` by default in **all** variants; turning it on is a per-tenant,
   admin-confirmed action.
2. **No student PII leaves the server.** Data minimization + a PII-stripping sanitizer
   unit-tested with student/guardian/staff fixtures.
3. T3 output (remarks, letters) is **draft-only**: human review/approval required before
   print/send — same pattern as existing payment confirmations.
4. `automation_logs` is append-only and readable by admin only; engines are idempotent to
   make re-runs safe.
5. LDAP/SSO/SMS keys and AI keys never leave `.env` / gateway config (mirrors existing
   `PaymentGateway` `$hidden` practice).

---

## 8. Roadmap & gates

1. **Gate A (confirm scope):** approve this plan, the T1 feature list, and BD/INT flag
   defaults. → generates `docs/prompts/features-impl-prompt-NN.md` batch 1.
2. **Stage 1 (T1):** automation spine. ~ the longest stage; per-feature confirmation.
3. **Gate B:** demo T1 at a BD school workflow; sign-off on parity tests.
4. **Stage 2 (T2):** analytics dashboards.
5. **Gate C:** INT profile preview — AI OFF, borders on `int` build.
6. **Stage 3 (T3):** opt-in AI; INT brochure pricing tier.
7. **Gate D:** full product parity + website copy final.

---

## 9. Definition of Done (all three products)

- App: `cd eskoofy-app && composer test` green (new engine/idempotency/sanitizer tests).
- Raw PHP: `cd eskoofy-php && composer test` green; `resources/views/**` byte-identical;
  `route:list` parity asserted.
- Theme: `php -l` clean on touched files; `composer run lint`; `esk_admin_shell_groups()`
  shows "Smart"; dashboards render.
- Website: `/features` + `/pricing` + `/products/*` mention the shipped smart features.
- BD profile byte-for-byte today's behavior unless a task explicitly changes it; INT
  profile reflects the enabled feature set.