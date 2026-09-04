# Implementation Plan — School Management System Review Fixes

**Source:** `docs/SENIOR-PM-REVIEW-REPORT.md` (2026-09-04)
**Date:** 2026-09-04
**Goal:** Implement all safe, high-value fixes from the review report.

---

## Scope Selection

Not all findings from the report are safe to implement in a single pass. Each recommendation has been re-assessed for **risk** and **business value**:

| Category | Count | Status |
|---|---|---|
| Critical fixes | 1 | **In scope** |
| High-priority fixes | 8 | 5 in scope, 3 deferred (require schema/data migration) |
| Medium-priority fixes | 7 | 6 in scope, 1 deferred |
| Low-priority fixes | 4 | 2 in scope, 2 deferred |

### Deferred items (documented, not implemented now)

These require **database schema migrations** or are **feature-level expansions** beyond a bug-fix pass. They are tracked for a future sprint:

- **H6: Consolidate `ClassModel` into `SchoolClass`** — `Grade` model reads from `classes` table via `class_id` FK (tested in `TeacherControllerTest`, `GradeTest`). Changing this requires a data migration of `grades.class_id` from `classes.id` → `school_classes.id` plus rewriting the `TeacherController::getClassGrades`, factories, and tests. **Too risky for this pass** — needs its own data-migration ticket.
- **H7/H8/M4: Refund / Push / Email admin UIs + Invoice UI** — New feature work, not bug fixes.
- **H3/H4: Form Requests + `$request->all()` cleanup** — Large refactor touching 31 call sites; medium risk. Tracked separately.
- **L1/L2: Token scoping + 2FA** — Feature-level security enhancements.

---

## Implementation Steps (In Scope)

| # | Priority | Fix | Files | Risk |
|---|---|---|---|---|
| 1 | Critical | Gate error message in `Admin/WebsiteSettingController` | `app/Http/Controllers/Admin/WebsiteSettingController.php` | Low |
| 2 | High | Fix CORS config (no `*` origin with credentials) | `config/cors.php`, `.env.example` | Low |
| 3 | High | Harden session config (secure cookie + encryption defaults) | `config/session.php`, `.env.example` | Low |
| 4 | High | Remove duplicate transport routes | `routes/dashboard.php` | Low |
| 5 | Medium | Migrate deprecated `@test` → `#[Test]` | `tests/Feature/Payment/PaymentServiceTest.php` splash + check all | Low |
| 6 | Medium | Remove `.bak` migration file | `database/migrations/2025_10_11_135800_create_notifications_table.php.bak` | Low |
| 7 | Medium | Remove `ClassModel` from factory/test where safe (document only, keep Grade functional) | `database/factories/GradeFactory.php`, `tests/` | Low |

---

## Detailed Steps

### Step 1 — [Critical] Fix error leakage in `Admin/WebsiteSettingController`

**Problem:** `update()` catch block returns `$e->getMessage()` unconditionally to the caller (a security leak in production).

**Fix:** Gate the exception message behind `config('app.debug')`. In production, return a generic message and log the real error.

**Files:**
- `app/Http/Controllers/Admin/WebsiteSettingController.php`

### Step 2 — [High] Fix CORS configuration

**Problem:** `config/cors.php` has `allowed_origins => ['*']` with `supports_credentials => true`, which is **invalid per the CORS spec** — browsers reject credentialed requests when the origin is `*`.

**Fix:** Make allowed origins env-driven. Default to the app's own URL (no wildcard). This keeps same-origin working in dev while letting ops configure specific origins in production.

**Files:**
- `config/cors.php`
- `.env.example` (add `CORS_ALLOWED_ORIGINS`)

### Step 3 — [High] Harden session configuration

**Problem:** `SESSION_SECURE_COOKIE` and `SESSION_ENCRYPT` both default to insecure values.

**Fix:** Keep the env-driven pattern (so dev can disable), but set **secure production defaults** and document in `.env.example`.

- `config/session.php`: keep `env('SESSION_SECURE_COOKIE', null)` (Laravel auto-detects secure when over HTTPS) — verify, leave as-is; the real fix is documenting in `.env.example` and adding a production checklist note.
- `.env.example`: add `SESSION_SECURE_COOKIE=true` and `SESSION_ENCRYPT=true` guidance.

### Step 4 — [High] Remove duplicate transport routes

**Problem:** `routes/dashboard.php` defines the **identical** transport route group twice (lines 303-321 and 550-568), both inside `role:admin` groups. The second group at line 550 is a pure duplicate and registers shadowing duplicate routes.

**Fix:** Delete the second (redundant) transport group at lines 550-568.

**Files:**
- `routes/dashboard.php`

### Step 5 — [Medium] Migrate deprecated `@test` annotations

**Problem:** PHPUnit's `/** @test */` doc annotations are deprecated in favor of the `#[Test]` attribute.

**Fix:** Replace `@test` annotations with `use PHPUnit\Framework\Attributes\Test;` + `#[Test]` attributes.

**Files:**
- `tests/Feature/Payment/PaymentServiceTest.php`
- Grep all test files to catch any others.

### Step 6 — [Medium] Remove `.bak` migration file

**Problem:** `database/migrations/2025_10_11_135800_create_notifications_table.php.bak` is a stray non-`.php` file in the migrations directory.

**Fix:** Delete it (it's a `.bak`, not executed by Laravel; the real migration exists).

**Files:**
- `database/migrations/2025_10_11_135800_create_notifications_table.php.bak`

### Step 7 — [Medium] Document `ClassModel`/`Grade` legacy schema debt

**Problem:** The `Grade` model reads `grades.class_id` from the legacy `classes` table, while the rest of the app uses `school_classes`. This is an architectural inconsistency (deferred consolidation per scope).

**Fix:** Add a clarifying note/documentation so future engineers understand the legacy relationship. Do **not** change schema in this pass.

**Files:**
- `AGENTS.md` (add migration gotcha note), or a new `docs/NOTES.md`.

---

## Verification

After all steps, run:

```bash
./vendor/bin/pint                # code style auto-fix
./vendor/bin/pint --test         # style check
composer test                    # full test suite (expect 867+ pass)
php artisan route:list           # verify no duplicate transport routes
```

**Success criteria:**
- All tests pass (>= 867, 0 failures)
- `pint --test` clean
- `route:list` shows transport routes registered once (not twice)
- No `@test` annotations remain in `tests/`
- `.bak` file removed from migrations

---

## Deferred / Follow-up Items (not in this pass)

| Ticket | Description | Priority |
|---|---|---|
| FK-001 | Consolidate `ClassModel`/`classes` → `SchoolClass`/`school_classes` (data migration) | High |
| FEAT-001 | Admin dashboard UI for refund management | High |
| FEAT-002 | Admin dashboard UI for push/email notifications | High |
| FEAT-003 | Admin UI + views for Invoice system | Medium |
| REF-001 | Introduce Form Requests + replace `$request->all()` (31 sites) | Medium |
| SEC-001 | Token ability scoping (replace `['*']`) | Low |
| SEC-002 | Two-factor authentication | Low |
| FEAT-004 | Dedicated CRUD for Section/Batch/Subject | Low |
