# Senior Project Manager — Full System Review Prompt

## Purpose

This prompt defines the methodology, scope, and checklist for conducting a senior-level project manager review of the School Management System. It is designed to be executed by an AI agent or human auditor to produce a comprehensive, actionable review report.

---

## Role Definition

You are acting as a **Senior Project Manager** with 15+ years of experience in educational technology systems. You have deep expertise in:

- Laravel/PHP application architecture and security
- School management domain knowledge (admissions, academics, finance, HR, transport, library, hostel)
- Payment gateway integration (Bkash, Nagad, Rocket)
- RBAC and authorization design
- API design and documentation
- Database schema design and migration management
- Code quality, testing, and CI/CD
- Production readiness and deployment

---

## Review Scope

### 1. Code Quality & Architecture Review

**Checklist:**

- [ ] Directory structure follows Laravel conventions
- [ ] Controllers are thin (delegate to services)
- [ ] Services handle business logic appropriately
- [ ] Models have proper relationships defined
- [ ] No God classes (files > 500 lines with mixed concerns)
- [ ] Proper use of traits, concerns, and contracts
- [ ] Consistent naming conventions across files
- [ ] No dead code (unused classes, methods, middleware)
- [ ] No duplicate controllers or routes
- [ ] Proper use of Form Requests for validation
- [ ] API Resources used for JSON response shaping
- [ ] Middleware properly registered and applied

### 2. Security Review

**Checklist:**

- [ ] Authentication is properly enforced on all sensitive routes
- [ ] Authorization (RBAC) is consistent across dashboard and API
- [ ] Payment webhook signature verification is mandatory
- [ ] File upload validation includes MIME type restrictions
- [ ] No mass-assignment vulnerabilities ($fillable/$guarded)
- [ ] Credentials are hidden in API responses ($hidden)
- [ ] CSRF protection on all web routes
- [ ] Rate limiting applied appropriately
- [ ] No hardcoded secrets or credentials in source code
- [ ] Trusted proxies properly configured (not wildcard)
- [ ] Error messages don't leak internal details
- [ ] Session security (HTTPS-only cookies, encryption)
- [ ] CORS properly configured

### 3. Feature Completeness Review

For EACH major module, verify:

| Check | Description |
|---|---|
| Model | Eloquent model exists with proper table, columns, relationships |
| Migration | Database migration exists and is valid |
| Controller (Web) | Blade controller exists with CRUD operations |
| Controller (API) | API controller exists if applicable |
| Views | Blade views exist for all user-facing operations |
| Routes | Routes defined in appropriate route file |
| Tests | Unit tests for model + Feature tests for HTTP layer |
| Policies | Authorization policies exist if needed |

**Modules to review:**

| # | Module | Priority |
|---|---|---|
| 1 | Student Management | Critical |
| 2 | Teacher Management | Critical |
| 3 | Guardian Management | High |
| 4 | Class/Section/Batch Management | Critical |
| 5 | Subject Management | High |
| 6 | Exam Management | Critical |
| 7 | Exam Results & Publishing | Critical |
| 8 | Student Attendance | Critical |
| 9 | Staff Attendance | High |
| 10 | Fee Management | Critical |
| 11 | Fee Payments & Invoices | Critical |
| 12 | Payment Gateway (Bkash/Nagad/Rocket) | Critical |
| 13 | Refund System | High |
| 14 | Admission System | Critical |
| 15 | Assignment Management | High |
| 16 | Routine/Schedule | Medium |
| 17 | Library Management | Medium |
| 18 | Transport Management | Medium |
| 19 | Hostel Management | Medium |
| 20 | Certificate Generation | High |
| 21 | Admit Card Generation | High |
| 22 | Student ID Card | High |
| 23 | Notice/Announcements | High |
| 24 | Event Management | Medium |
| 25 | News Management | Medium |
| 26 | Gallery Management | Medium |
| 27 | CMS/Website Content | High |
| 28 | SMS Notifications | High |
| 29 | Push Notifications | Medium |
| 30 | Email Notifications | Medium |
| 31 | Report Generation | High |
| 32 | Bulk Import/Export | Medium |
| 33 | Backup/Restore | High |
| 34 | Role & Permission Management | Critical |
| 35 | Activity/Audit Logging | High |
| 36 | Dashboard with KPIs | High |
| 37 | Search Functionality | Medium |
| 38 | Public Result Lookup | Critical |
| 39 | Multi-language (EN/BN) | High |
| 40 | API Endpoints | High |
| 41 | Student Portal | High |
| 42 | Payroll Management | Medium |
| 43 | Leave Management | Medium |
| 44 | Accounting/Ledger | Medium |
| 45 | Messaging System | Low |
| 46 | Testimonials | Low |
| 47 | Committee Management | Low |
| 48 | Career/Jobs | Low |
| 49 | Visitor Logging | Low |

### 4. Database & Migration Review

**Checklist:**

- [ ] No duplicate/legacy migrations causing confusion
- [ ] Schema is consistent (no orphaned FK references)
- [ ] Soft deletes used appropriately
- [ ] Proper indexes on frequently queried columns
- [ ] JSON columns used where appropriate
- [ ] Encryption cast on sensitive columns
- [ ] No MySQL-only syntax in migrations (for SQLite dev compatibility)

### 5. Testing Review

**Checklist:**

- [ ] Test files exist for all models (unit tests)
- [ ] Feature tests cover critical user flows
- [ ] Tests run without errors on SQLite in-memory
- [ ] No deprecated test annotations (@test → #[Test])
- [ ] Test files in correct directories (Unit vs Feature)
- [ ] No test isolation issues (shared state)
- [ ] Gateway/payment tests cover all adapters
- [ ] Security tests (mass-assignment, authorization)

### 6. API Design Review

**Checklist:**

- [ ] Consistent response envelope (success/message/data/meta)
- [ ] Proper HTTP status codes
- [ ] Pagination for list endpoints
- [ ] Rate limiting on sensitive endpoints
- [ ] API versioning (v1 prefix)
- [ ] Webhook endpoints excluded from envelope
- [ ] Input validation on all write endpoints
- [ ] Proper error responses

### 7. Production Readiness Review

**Checklist:**

- [ ] No hardcoded credentials in source
- [ ] Environment variables properly documented
- [ ] Error handling doesn't leak internals
- [ ] Queue jobs for async processing
- [ ] Scheduled tasks properly configured
- [ ] Backup/restore procedures documented
- [ ] Deployment checklist exists
- [ ] Performance baseline documented
- [ ] Docker configuration for production

---

## Review Methodology

### Step 1: Static Analysis
1. Read `composer.json` and `package.json` for dependencies
2. Glob for all models, controllers, views, tests
3. Read route files for completeness
4. Check migration files for schema consistency

### Step 2: Code Inspection
For each critical module:
1. Read the model — check relationships, scopes, accessors
2. Read the controller — check CRUD, validation, authorization
3. Read the views — check UI completeness
4. Read the routes — check naming and middleware

### Step 3: Security Audit
1. Grep for `$request->all()` usage
2. Check `$fillable`/`$guarded` on all models
3. Verify `$hidden` on models with sensitive data
4. Check middleware registration in `bootstrap/app.php`
5. Verify webhook signature verification
6. Check file upload validation rules

### Step 4: Testing Verification
1. Count test files per module
2. Check for test coverage gaps
3. Verify test configuration (phpunit.xml)
4. Check for test/isolation issues

### Step 5: Gap Analysis
1. Compare implemented features against standard school management requirements
2. Identify partially implemented features
3. Identify missing features
4. Prioritize gaps by business impact

---

## Output Format

The final report should follow this structure:

```
# School Management System — Comprehensive Review Report

## Executive Summary
(Overall assessment, key findings, risk level)

## 1. System Overview
(Tech stack, architecture, scale metrics)

## 2. Code Quality Assessment
(Strengths, weaknesses, specific findings)

## 3. Security Assessment
(Critical, high, medium, low findings with evidence)

## 4. Feature Completeness Matrix
(Table: Module | Model | Controller | Views | Routes | Tests | Status)

## 5. Database & Migration Review
(Schema issues, migration hygiene)

## 6. Testing Coverage Report
(Coverage by module, gaps, recommendations)

## 7. API Design Assessment
(Consistency, security, documentation)

## 8. Production Readiness Scorecard
(Checklist with pass/fail per item)

## 9. Gap Analysis
(Missing features, partial implementations)

## 10. Recommendations
(Prioritized: Critical → High → Medium → Low)

## Appendix: Files Reviewed
(Complete list of files inspected)
```

---

## Severity Levels

| Level | Definition | Action |
|---|---|---|
| **Critical** | Security vulnerability, data loss risk, or broken core functionality | Fix immediately before any deployment |
| **High** | Significant functional gap, authorization issue, or architectural debt | Fix within 1-2 sprints |
| **Medium** | Code quality issue, missing validation, or incomplete feature | Fix within 1 month |
| **Low** | Cosmetic issue, dead code, or minor inconsistency | Fix when convenient |

---

## Notes

- This prompt is designed for a **read-only audit** — no code changes during review
- All findings must cite **exact file paths and line numbers**
- Previous audits exist in `codebase-audit.md` and `docs/system-architecture-review.md` — cross-reference but do not blindly trust (code may have been fixed since)
- The `archive/` directory is legacy and should be excluded from review
- Test findings should be verified by running `composer test`
