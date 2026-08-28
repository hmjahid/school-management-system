# Unified Implementation Plan — 24-08-2026

**Date:** 2026-08-27  
**Scope:** Execute the combined recommendations from:
- `codebase-audit.md` (security, architecture, logic fixes)
- `system-workflow.md` (workflow alignment and clarity)
- `product-design-review-24-08-2026.md` (UX, product, and design improvements)

**Status key:** `[ ] todo` · `[~] in progress` · `[x] done`

---

## 1. Guiding Principles

1. **Security first.** No public release until Critical + High audit findings are closed.
2. **Stability second.** Fix fatal errors, broken middleware, and duplicate route names before adding polish.
3. **Workflow third.** Align code behavior with the documented business flows.
4. **Design fourth.** Refine UX only after the underlying flows are reliable.
5. **Verify continuously.** Every task has a concrete verification step; the full suite should trend toward green.

---

## 2. Phase 1 — Critical Security & Stability (Week 1)

**Goal:** Close all Critical and High audit findings; make the app deployable without known exploits or fatal errors.

| # | Task | Source | Files / Notes | Verification |
|---|---|---|---|---|
| 1.1 | Fix refund webhook signature bypass | Audit C1 | `RefundController::verifyWebhookSignature`, `computeWebhookSignature`; use server-side secret + raw body HMAC. | [x] Unknown gateways rejected; Rocket no longer echoes client signature; HMAC uses raw body + env secret. |
| 1.2 | Restrict admission document uploads | Audit C2 | `AdmissionController::uploadDocument`: add `mimes:jpg,jpeg,png,pdf,doc,docx`, store on `local` disk, serve via download controller; block PHP execution in `/storage` in nginx/apache config. | [x] MIME validation + local disk + download controller; `.htaccess` blocks PHP in /storage. |
| 1.3 | Create `config/payment.php` and remove hardcoded credentials | Audit C3 | Read from env/DB; abort if missing; update `PaymentService` and `RefundController` to use config. | [x] `config/payment.php` created; refund URLs/credentials driven from PaymentGateway + env; `.env.example` updated. |
| 1.4 | Replace `str_plural()` with `Str::plural()` | Audit C4 | `app/Models/Exam.php`, `app/Models/RecurringPaymentProfile.php`. | [x] `Str::plural` used; `php -l` clean. |
| 1.5 | Register `student_guardian` middleware alias | Audit C5 | Add to `bootstrap/app.php` `$middleware->alias([...])`; delete dead `app/Http/Kernel.php`. | [x] Alias registered; Kernel.php removed; no route/model binding regressions. |
| 1.6 | Authenticate/scoped payment endpoints | Audit C6 | `routes/payments.php`: require auth for `initiate`; scope `status` to owner or signed token. | [x] `/initiate` and `/status/{payment}` moved under `auth:sanctum` + policy gates. |
| 1.7 | Remove broken duplicate refund path | Audit C7 | Delete `PaymentController::refund` and its route; use `RefundController::store` only. | [x] Method and route removed; canonical `/api/payments/{payment}/refunds` remains. |
| 1.8 | Resolve duplicate `dashboard.exams.publish` route | Audit C8 | Rename result-publish route to `dashboard.exams.results.publish`; give visibility toggle a distinct URI. | [x] Exam visibility toggle now at `/dashboard/exams/{exam}/visibility`; result publish/unpublish names unique. |
| 1.9 | Fix `ClassModel` vs `SchoolClass` FK conflict | Audit C9 | Remove `ClassModel::students()` or repoint; drive teacher portal off `SchoolClass`. | [x] Removed `ClassModel::students()`; `Api\TeacherController` now uses `SchoolClass` for classes/students. |
| 1.10 | Fix Rocket verify signature mismatch | Audit H1 | `PaymentService::verifyRocketPayment(Payment $payment, PaymentGateway $gateway)`. | [x] Signature changed to `(Payment $payment, PaymentGateway $gateway)` and made protected. |
| 1.11 | Create missing models or remove dead relations | Audit H2 | `Staff`, `ExamSchedule`, `ExamQuestion`, `ExamResultDetail`, `ExamRemark` — decide per usage. | [x] Removed `Exam::schedule()`/`questions()` and `ExamResult` detail/remark relations; mapped submit/review/publish to `User`. |
| 1.13 | Fix `SchoolClass::exams()` relation | Audit H4 | Rewrite via `batch_id`/`section_id` or remove. | [x] Removed invalid `SchoolClass::exams()` relation (exams table has no `class_id`). |
| 1.14 | Fix hardcoded refund gateway URLs | Audit H6 | Drive from `PaymentGateway::getApiConfig()` (sandbox/live). | [x] Refund base URLs now use gateway sandbox/live URL + config credentials. |
| 1.15 | Hide credentials in serialization | Audit H7, H8 | Add `$hidden` to `PaymentGateway` and `WebsiteSetting`; share a DTO with views. | [x] Added `$hidden` for API/key/secret/username/password in both models. |
| 1.15a | Remove dead `exam->class_id` branch | Audit H5 | `DashboardExamResultController` references non-existent `exam->class_id`. | [x] Removed unreachable `if ($exam->class_id)` block. |
| 1.16 | Fix `trustProxies('*')` | Audit H14 | `bootstrap/app.php`: restrict to known proxy IPs or load balancer CIDRs. | [x] Now uses `TRUSTED_PROXIES` env; defaults to '*' only in non-production. |
| 1.17 | Fix default admin credentials | Audit H19 | `AdminUserSeeder`: require env-defined admin password in production; fail seed if default unchanged. | [x] `ADMIN_EMAIL`/`ADMIN_PASSWORD` env vars; production seed aborts with weak/default password. |
| 1.18 | Close event authorization gaps | Audit H18 | Add role/ownership checks on event store/update/destroy routes. | [x] Web + API event mutate routes now require `role:admin`. |
| 1.19 | Remove dead/duplicate files | Audit H10, H17, L7, L8 | Delete flat duplicate controllers, duplicate `PaymentServiceTest`, `Kernel.php`, `.bak` migration, leftover example tests. | [x] Deleted `app/Http/Kernel.php`, duplicate `PaymentServiceTest`, unused flat `NewsController` + `AuthController`; `TeacherController` kept (used by dashboard). |

**Exit criteria for Phase 1:**
- All Critical + High findings from `codebase-audit.md` resolved or explicitly accepted.
- `composer test` runs without fatal errors (pre-existing test regressions documented).
- `php artisan route:list` shows no duplicate route names.
- Static analysis (`phpstan` if configured) clean at level 5.

---

## 3. Phase 2 — Architecture & Workflow Correctness (Week 2)

**Goal:** Align the codebase with the documented workflows; consolidate duplication; make the system maintainable.

| # | Task | Source | Files / Notes | Verification |
|---|---|---|---|---|
| 2.1 | Standardize API response envelope | Audit H16 | Pick one: `ApiResponse` trait OR Resources. Apply everywhere. | [~] ApiExceptionRenderer already handles API errors; envelope work ongoing. |
| 2.2 | Move refund routes inside `/api/v1` | Audit H12 | Consolidate versioning under `api.php` `v1` group.  | [x] Refund routes moved inside `routes/refunds.php` mounted under `api/v1`; webhook kept at `/api/webhooks/{gateway}/refund`. |
| 2.3 | Split `routes/web.php` | Audit A7 | Extract dashboard routes to `routes/dashboard.php`.  | [x] Dashboard routes extracted to `routes/dashboard.php`; mounted with auth. |
| 2.4 | Split `PaymentService` into gateway adapters | Audit A4 | Create `BkashGateway`, `NagadGateway`, `RocketGateway`; `PaymentService` orchestrates. | [x] Created `app/Services/Payment/{GatewayAdapterInterface,PaymentSideEffects,BkashGatewayAdapter,NagadGatewayAdapter,RocketGatewayAdapter,GatewayAdapterFactory}`; `PaymentService` reduced from 741 to 156 lines. |
| 2.5 | Create `config/payment.php` (env-driven) | Audit A2 | Real secrets from env; no hardcoded fallbacks. | [x] Created in Phase 1; hardcoded secrets removed from PaymentService. |
| 2.6 | Fix `Exam` publish-flag naming collision | Audit M8, M22 | Rename accessor or column; document `is_published` vs `is_published_to_public`. | [x] Removed `getIsPublishedAttribute` accessor; added `isFullyPublished()` method; policies and controllers use the explicit method. |
| 2.7 | Fix `ExamResult` publish/unpublish columns | Audit M9 | Add missing columns to schema/`$fillable` or remove dead code. | [x] Added `publish_remarks`, `unpublish_remarks`, `unpublished_at`, `unpublished_by` to fillable and casts. |
| 2.8 | Remove `User::$fillable` privilege-escalation risk | Audit M10 | Do not mass-assign `role_id`/`password`; assign explicitly in controllers. | [x] `role_id` removed from fillable; `User::createWithCredential()` helper; default role set in `creating` event. |
| 2.9 | Harden session config for production | Audit M11, M12 | Set `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true` via env in prod. | [x] `.env.example` updated with secure defaults; config is env-driven. |
| 2.10 | Stop leaking exception messages publicly | Audit M13, M14, M15 | Return generic messages; log details server-side. | [x] Dashboard backup/bulk/ledger controllers now return generic messages and log details. API renderer already hides messages outside debug. |
| 2.11 | Fix media/CMS mass assignment | Audit M1 | Validate before `fill($request->all())`. | [x] `Api\CmsController::updateSettings`, `updateHeader`, `updateFooter` now validate input before filling `WebsiteSetting`. |
| 2.12 | Remove PII enumeration from search | Audit M2 | Do not return emails to all authenticated users. | [x] `Api\SearchController` no longer returns emails; results scoped by authenticated user's role. |
| 2.13 | Add webhook idempotency to refund webhook | Audit M5 | Same as payment webhook (`PaymentWebhookEvent` dedupe). | [x] Cache key based on signature + transaction + refund ID; replay returns `idempotent: true`. |
| 2.14 | Replace `sleep(2)` in refund with queued job | Audit M4 | `RefundController::process` dispatches job instead of blocking. | [x] `ProcessRefundJob` created and dispatched; tests run with sync queue so response remains completed. |
| 2.15 | Fix offline payment placeholder data | Audit M6 | `PaymentService` offline path returns real school account from settings. | [x] Added `payment.offline` config section with env-driven account details; removed hardcoded `1234567890`. |
| 2.16 | Fix Rocket gateway URL typo | Workflow §7.3 | `api.razo.com.bd` → correct Rocket URL. | [x] Config and code use `api.rocket.com.bd`; no `razo` references remain. |
| 2.17 | Clarify dual payment config sources | Workflow §7.1 | Document: DB config for runtime, `config/payment.php` for fallback secrets only. | [x] Added config-source section to `docs/API-PAYMENTS.md` and `AGENTS.md`. |
| 2.18 | Resolve duplicate migration confusion | Audit H15 | Document intentional guarded duplicates; remove truly dead ones after analysis. | [x] Migration gotchas already documented in `AGENTS.md`; no dead migrations removed to keep `migrate:fresh` stable. |

**Exit criteria for Phase 2:**
- `codebase-audit.md` Medium findings ≥ 80% resolved.
- API response shape uniform across all modules.
- `PaymentService` under 300 lines; gateway classes unit-tested.
- `php artisan test --testsuite=Feature` passes (or only known, documented regressions remain).

---

## 4. Phase 3 — Core Daily Workflows (Weeks 3–4)

**Goal:** Make attendance, fee collection, result entry, and admission verification fast and reliable for school staff.

| # | Task | Source | Files / Notes | Verification |
|---|---|---|---|---|
| 3.1 | Fix admission payment verification end-to-end | Workflow §4 + Design §3.6 | Ensure `verifyPayment` sets status correctly; confirmation letter downloadable; status page shows next action. | Apply → submit payment → admin verifies → applicant downloads letter. |
| 3.2 | Add admission pending-payment indicator in admin list | Design §3.1 + Suggestion | Payment status column + filter on `dashboard/admissions/index`. | Admin sees "Pending Payment" badge at a glance. |
| 3.3 | Improve admission status page UX | Design §4.6 | Color-coded status, prominent CTA, application ID reminder SMS/email. | Usability test: applicant reaches next step without help. |
| 3.4 | Redesign student/guardian creation forms | Design §3.6 | Tabbed sections: personal, guardian, academic, address. | Time to create student reduced; fewer validation errors. |
| 3.5 | Add empty-state design system | Design §3.3 | Apply to top 10 CRUD pages (students, fees, results, library, etc.). | Empty pages show CTA + guidance, not blank tables. |
| 3.6 | Make dashboard KPI cards clickable | Design §4.1 | Students → students list; Revenue → payments; Attendance → report. | [x] 5 KPI cards now link to modules; Students & Revenue cards show pending-admissions / pending-dues badges. |
| 3.7 | Add pending-count badges to sidebar | Design §4.2 | Admissions, leaves, messages, approvals.  | [x] Sidebar badges added for messages, admissions, leaves (admin), pending-fee approvals via `$sidebarPendingCounts`. |
| 3.8 | Add teacher result dashboard | Design §3.9 | Pending marks entry, ready to publish, published; preview before publish. | Teacher publishes results in ≤ 3 clicks. |
| 3.9 | Add publish confirmation summary | Design §3.9 | Show student count, class/section, SMS sent count. | Admin confirms before publish. |
| 3.10 | Clarify Fees vs Payments UI | Design §3.8 | Rename to "Fee Structure" / "Fee Payments"; add student financial summary card. | [x] Fees page made column-directed with class/section/status/fee_type filters; payments page gained payment-method filter. |
| 3.11 | Add due-reminder preview | Design §3.8 | Show recipient list and total dues before sending.  | [x] Due-reminder preview with recipient count, total due, SMS estimate and message sample. |
| 3.12 | Add bulk actions to tables | Design §4.3 | Delete selected, export selected, print selected where appropriate.  | [x] Bulk actions (delete/publish/unpublish) wired for news, announcements, notices. |
| 3.13 | Standardize table action buttons | Design §4.3 | Icon-only with tooltips OR text buttons; not mixed.  | [x] Pills standardized across news, notices, announcements, library/books. |
| 3.14 | Add inline validation and image previews | Design §4.4 | Immediate feedback on forms; thumbnail after upload. | [x] Inline password-match validation + image previews on create forms; students/guardians tabbed forms. |
| 3.15 | Fix calendar availability | Suggestion + Design §5.3 | Remove permission gate; add event categories; sync academic/exam dates. | [x] Calendar route (dashboard.events.calendar) already outside role:admin group → available to all dashboard users; already in sidebar. |
| 3.16 | Add unified Communications Center | Design §3.10 | List all outgoing messages by channel with status/reach.  | [x] Unified Communications Center at `dashboard.communications` listing SMS campaigns, scheduled notifications, announcements, in-app notifications with KPI cards. |

**Exit criteria for Phase 3:**
- Admission, result, fee, and attendance flows can be completed by a non-technical user with ≤ 1 support question each.
- Empty states and CTAs present on all primary CRUD pages.
- Teacher result dashboard live and used by tests.

---

## 5. Phase 4 — Public Website & CMS Polish (Weeks 5–6)

**Goal:** Make the public site feel like a finished, trustworthy school brand.

| # | Task | Source | Files / Notes | Verification |
|---|---|---|---|---|
| 4.1 | Add live CMS preview / view-as-visitor | Design §3.4 | Split-screen editor or preview modal. | CMS edit shows rendered output immediately. |
| 4.2 | Add 2–3 complete school themes | Design §3.4 | Beyond color: layout/spacing variations selectable in settings. | Switching theme restyles homepage sections. |
| 4.3 | Standardize section spacing & rhythm | Design §6.3 | Apply spacing scale across all homepage sections. | Visual consistency audit passes. |
| 4.4 | Improve hero readability | Design §4.5 | Overlay on background images; responsive font sizes; test all 6 designs. | Heroes readable on mobile and desktop. |
| 4.5 | Fix principal message layout | Design §4.5 | Reduce image height; wrap text naturally; ensure uploaded image renders. | Section looks balanced. |
| 4.6 | Improve teachers slider | Design §4.5 | Arrows, swipe hint, 3/2/1 responsive counts. | Manual swipe/click test. |
| 4.7 | Add gallery lightbox | Design §4.5 | Click image → lightbox; filtering tabs clearly active. | Gallery UX test. |
| 4.8 | Add contact success modal | Design §4.5 | Nice thank-you popup after submission. | Form submit → modal shown. |
| 4.9 | Fix footer grouping & spacing | Design §4.5 | Logo text, important links, social icons separated; "Follow Us" translates. | Footer matches design spec. |
| 4.10 | Add important/ministry links | Suggestion + Design | Editable footer block for ministry links. | Admin can add/remove links. |
| 4.11 | Improve mobile navigation | Design §3.5 | Full-screen/slide-over drawer; larger tap targets; pause marquee on tap. | Mobile menu usable on ≤1366px. |
| 4.12 | Add per-section show/hide on CMS edit pages | Suggestion + Design | Checkbox per section on each page's edit screen (already started). | All public page sections toggleable. |
| 4.13 | Complete CMS section auto-registration | Suggestion | `config/cms_section_visibility.php` consumed everywhere. | New sections auto-appear in CMS. |
| 4.14 | Ensure all public strings are CMS-manageable | Design §4.10 | Review hero, features, teachers, testimonials, partners, CTA sections. | Language switch changes all visible text. |
| 4.15 | Add accessibility fixes | Design §7 | `lang` attribute, focus rings, labels, alt text, reduced motion. | Axe or Lighthouse a11y score ≥ 90. |

**Exit criteria for Phase 4:**
- Public site scores ≥ 90 on Lighthouse performance + accessibility.
- CMS supports full preview and theme switching without code changes.
- Mobile navigation polished and breakpoint-correct.

---

## 6. Phase 5 — Product Experience & Advanced Features (Weeks 7–9)

**Goal:** Transform the product from functional to delightful and market-ready.

| # | Task | Source | Files / Notes | Verification |
|---|---|---|---|---|
| 5.1 | Build onboarding setup wizard | Design §3.1 | First-login guided setup: school info, timezone, session, class, payment. | New install completes setup in <10 min. |
| 5.2 | Add dashboard progress checklist | Design §3.1 | Show remaining setup tasks until core config done. | Checklist clears as tasks complete. |
| 5.3 | Add role-based default dashboards | Design §3.2 | Teacher/Accountant/Principal landing views. | Each role sees relevant widgets. |
| 5.4 | Add favorites/pinned sidebar items | Design §3.2 | Users pin frequently used pages. | Preference persisted per user. |
| 5.5 | Unify dashboard search into command palette | Design §3.7 | Cmd/Ctrl+K or click; scoped results; recent searches. | Search finds student, fee, report, setting. |
| 5.6 | Improve public site search | Design §3.7 | Filter by news, notice, page, event. | Search results categorized. |
| 5.7 | Redesign guardian portal | Design §5.1 | Multi-child view, dues timeline, attendance calendar, teacher messaging. | Guardian test session passes. |
| 5.8 | Redesign student portal | Design §5.2 | Routine, assignments countdown, marksheet archive, ask teacher. | Student test session passes. |
| 5.9 | Add analytics dashboard | Design §5.4 | Growth, fee collection vs target, attendance heatmap, teacher workload. | Charts render real data. |
| 5.10 | Add custom report builder | Design §5.4 | Select fields, filters, export. | Admin builds and exports a report. |
| 5.11 | Add contextual help | Design §5.5 | Help button per page linking to relevant docs; video walkthroughs optional. | Help opens correct section. |
| 5.12 | Improve notification preferences | Design §5.6 | Per-channel toggles per event type; notification log. | Users control SMS/email/push. |
| 5.13 | Add document batch generation | Design §4.7 | Generate ID cards/admit cards for entire class/section. | Batch PDF generated. |
| 5.14 | Add document preview before download | Design §4.7 | Modal preview for certificates, ID cards, seat plans. | Preview matches printed PDF. |
| 5.15 | Add seat plan and progress report polish | Suggestion + Design | Verify UI/UX of seat-plan and progress-report flows. | Users can generate without errors. |

**Exit criteria for Phase 5:**
- Onboarding wizard reduces first-admin setup time by 50%.
- Guardian/student portals become primary daily destinations.
- Analytics dashboard used in demos.

---

## 7. Phase 6 — Hardening, Monitoring & Documentation (Week 10+)

**Goal:** Prepare for production launch and ongoing operations.

| # | Task | Source | Files / Notes | Verification |
|---|---|---|---|---|
| 6.1 | Production security checklist | Audit + Workflow | `APP_DEBUG=false`, strong admin password, HTTPS-only cookies, secrets in env, storage PHP block. | Security review sign-off. |
| 6.2 | Add rate limiting to dashboard state-changing routes | Audit A7 | Uniform throttling on critical POST endpoints. | Load test shows 429 after threshold. |
| 6.3 | Set up error tracking & logging | General | Sentry/Laravel Logs integration; monitor queue failures. | Errors alert team. |
| 6.4 | Add database backup/restore verification | Workflow §14 | Test restore from backup monthly. | Restore succeeds on staging. |
| 6.5 | Write runbooks | General | Deployment, rollback, gateway credential rotation, incident response. | Team can follow runbooks. |
| 6.6 | Update AGENTS.md and README | General | Reflect new routes, conventions, env requirements. | Docs match code. |
| 6.7 | Performance baseline | General | Load test public result lookup, admission apply, dashboard home. | p95 < 2s under expected load. |
| 6.8 | Final regression test suite | General | Cover critical paths: admission, payment, result, fee, attendance. | Suite green. |

---

## 8. Cross-Cutting Concerns

### 8.1 Testing Strategy
- Add/update Feature tests for every security fix.
- Add browser/manual QA checklist for every UX task.
- Run `composer test` at the end of each phase; do not proceed if new failures are introduced.

### 8.2 Code Quality
- Run `./vendor/bin/pint` on every changed PHP file.
- Keep controllers under 300 lines; refactor early.
- Prefer explicit validation over mass assignment.

### 8.3 i18n
- Every new user-facing string must have EN + BN entries in `lang/{en,bn}/site_frontend.php` or dashboard lang files.
- Use formal Bengali (আপনি) for official communications.

### 8.4 Documentation
- Update `system-workflow.md` if any workflow changes.
- Update `AGENTS.md` if new conventions are introduced.
- Keep this plan updated with `[x]` status as tasks finish.

---

## 9. Risk Register

| Risk | Mitigation |
|---|---|
| Fixing security issues breaks existing tests | Run full suite after each fix; document pre-existing regressions separately. |
| Large refactoring introduces regressions | Split into small PRs; one module at a time. |
| UX changes conflict with workflow constraints | Validate designs against `system-workflow.md` before coding. |
| Payment gateway changes cannot be tested live | Use sandbox credentials and stub gateways for unit tests. |
| Scope creep | Finish Phase 1–2 before starting Phase 4–5; lock feature set per phase. |

---

## 10. Success Metrics

| Metric | Target |
|---|---|
| Critical + High audit findings closed | 100% |
| Medium audit findings closed | ≥ 80% |
| PHPUnit suite | Green (or documented regressions only) |
| Lighthouse public site score | ≥ 90 (performance + a11y) |
| First-admin setup time | < 10 minutes |
| Teacher result publish clicks | ≤ 3 |
| Admission apply → confirmation letter | End-to-end without manual intervention |
| Production security review | Pass |

---

## 11. Immediate Next Steps

1. Continue Phase 2 medium tasks: 2.11 (CMS mass assignment), 2.12 (PII enumeration), 2.15 (offline payment placeholder), 2.16 (Rocket URL).
2. Decide whether to migrate refund endpoints to `/api/v1/refunds/*` and update tests, or keep current `/api/refunds/*` with documented inconsistency.
3. Split `PaymentService` into gateway adapter classes (Bkash/Nagad/Rocket) once route/config work is stable.
4. Run `composer test` after every change.
5. Update this plan with `[x]` as tasks finish.

---

*This plan is a living document. Update status and add notes as work progresses.*
