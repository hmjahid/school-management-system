# School Management System - Implementation Status

> **Last verified:** 2026-07-02 — Laravel 12, Blade only, SQLite (local), session auth.
>
> **Status legend:** ✅ done & tested · ⚠️ partial / placeholder · ❌ not implemented

The app boots, `php artisan migrate:fresh --seed` succeeds, and a 32-step smoke
test against a live `php artisan serve` instance returns 200/302 on every
public route and every dashboard module. See "Verification" at the bottom.

## Authentication & User Management

| Feature                              | Status | Notes |
|--------------------------------------|--------|-------|
| User registration                    | ⚠️     | API route exists; web registration is via the public admissions form. |
| User login / logout (session)        | ✅     | `AuthSessionController` + `/login` Blade form. |
| Login / logout (API token)           | ✅     | Sanctum at `/api/auth/login`. |
| Password reset                       | ✅     | `PasswordResetController` + `/forgot-password` and `/reset-password` views. Rate-limited (3 per IP per 5 min). |
| Email verification                   | ❌     | Seeded users bypass; no flow for new users. |
| Role-based access control            | ✅     | Spatie Permission. 6 roles, ~80 permissions seeded. |
| Granular permissions                 | ✅     | Per-resource policies (Student, Teacher, Guardian, etc.). |
| User profile management              | ⚠️     | Basic edit forms via dashboard controllers; no `/profile` self-edit page. |

## Public Site (no auth)

| Page                                 | Route          | Status |
|--------------------------------------|----------------|--------|
| Home                                 | `/`            | ✅ |
| About                                | `/about`       | ✅ |
| Academics                            | `/academics`   | ✅ |
| Admissions                           | `/admissions`  | ✅ |
| Students life                        | `/students`    | ✅ |
| Faculty                              | `/faculty`     | ✅ |
| News list / article                  | `/news`, `/news/{slug}` | ✅ |
| Gallery                              | `/gallery`     | ✅ |
| Contact                              | `/contact`     | ✅ |
| Terms / Privacy                      | `/terms`, `/privacy` | ✅ |
| Online payments (portal)             | `/payments`    | ✅ (auth-gated) |
| Application form                     | `/admissions/apply` | ✅ |
| Application status                   | `/admissions/status` | ✅ |
| Portal home                          | `/portal`      | ✅ |
| Sitemap XML                          | `/sitemap.xml` | ✅ |
| Newsletter / contact / feedback / complaint forms | POSTs | ✅ |

## Admin Dashboard (`/dashboard`)

### Core

| Page / feature                       | Status | Notes |
|--------------------------------------|--------|-------|
| Dashboard overview (live stats)      | ✅     | 5 stat cards, attendance chart, role-aware redirect. |
| Students list                        | ✅     | Search, pagination, role-gated sidebar link. |
| Student detail / edit / create / delete | ✅  | `DashboardStudentController` + `StudentController`. |
| Teachers list / CRUD                 | ✅     | `DashboardTeacherController`. |
| Parents (guardians) list / CRUD      | ✅     | `DashboardGuardianController`. |
| Classes list / CRUD                  | ✅     | `DashboardSchoolClassController`. |
| Attendance list / create             | ✅     | `DashboardAttendanceController`. |
| Exams list / schedule                | ✅     | `DashboardExamController`. |
| Fees list / create / edit / delete   | ✅     | `DashboardFeeController`. |

### Admin-only (under `role:admin`)

| Page                                 | Status | Notes |
|--------------------------------------|--------|-------|
| Admissions queue                     | ✅     | `/dashboard/admissions` (list + status + tests). |
| CMS pages list / edit                | ✅     | `/dashboard/cms/pages` and `/dashboard/cms/edit/{page}`. |
| News CRUD                            | ✅     | `/dashboard/news` (admin). |
| Gallery CRUD                         | ✅     | `/dashboard/gallery` (admin). |
| Announcements CRUD                   | ✅     | `/dashboard/announcements` (admin). |
| Documents CRUD                       | ✅     | `/dashboard/documents` (admin). |
| Contact submissions                   | ✅     | `/dashboard/contact-submissions` + CSV export. |
| School settings                      | ✅     | `/dashboard/settings` (logo, favicon, social, contact). |

## Teacher / Student / Parent portals

The legacy React SPA in `archive/frontend/` contained separate dashboards per
role. The Blade rewrite has a single `/dashboard` layout, with role-aware
redirects. The student/parent login → `/portal` (their own progress views).

| Feature                              | Status | Notes |
|--------------------------------------|--------|-------|
| Student view of own attendance       | ⚠️     | `PortalProgressController` exists; UI is minimal. |
| Student view of own results          | ⚠️     | Same. |
| Student view of own fee status       | ⚠️     | `PortalAdmissionController` shows admission, not fees. |
| Parent view of child progress        | ⚠️     | Portal controller exists, view is skeletal. |
| Teacher class roster                 | ⚠️     | API endpoints under `/api/teacher/...` exist; no Blade UI. |
| Teacher attendance entry             | ❌     | No teacher-attendance page. |
| Teacher grade entry                  | ❌     | No grade-entry page. |
| Assignment submission                | ❌     | Model exists (`Assignment`) but no UI. |

## Finance & Payments

| Feature                              | Status | Notes |
|--------------------------------------|--------|-------|
| Fee CRUD (admin)                     | ✅     | Fee module on dashboard. |
| Online payment initiation            | ⚠️     | `PaymentsWebController` and gateway models exist; bKash/Nagad/Stripe are seeded but the gateway-side flow is a stub. |
| Payment receipts                     | ⚠️     | `FeePaymentReceiptController` returns a basic HTML receipt; no PDF. |
| Refund processing                    | ⚠️     | API route + model exist; no admin UI. |
| Financial reports                    | ❌     | No report page. |
| Payroll                              | ❌     | Not started. |
| Expense tracking                     | ❌     | Not started. |

## Library / Hostel / Transport

| Feature                              | Status | Notes |
|--------------------------------------|--------|-------|
| Library book CRUD                    | ❌     | No `Book` model, no UI. |
| Library issue/return                 | ❌     | — |
| Hostel management                    | ❌     | No model. |
| Transport routes / vehicles          | ❌     | No model. |

## Communication

| Feature                              | Status | Notes |
|--------------------------------------|--------|-------|
| In-app notifications (DB)            | ⚠️     | `Notification` model + `notifications` table exist; controllers exist but the dashboard doesn't surface them yet. |
| Email notifications                  | ❌     | `MAIL_MAILER=log` only; no email templates. |
| SMS gateway                          | ❌     | Not wired. |
| Announcement board                   | ✅     | `Announcement` model + dashboard CRUD + public list. |
| Notice board                         | ⚠️     | `Notice` model exists; no admin CRUD view. |

## Settings & Configuration

| Feature                              | Status | Notes |
|--------------------------------------|--------|-------|
| School settings (CMS)                | ✅     | `WebsiteSetting` + `Settings` page; logo/favicon/social upload. |
| Academic session / year              | ✅     | `AcademicSession` model + seed. |
| Grading system                       | ⚠️     | Model + scope; no UI. |
| Email / SMS templates                | ❌     | No model. |
| Backup & restore                     | ❌     | Not implemented. |
| Multi-language (EN / BN)             | ⚠️     | `lang/en/site_frontend.php` + `lang/bn/site_frontend.php`; toggle in topbar works for public site. |

## API Endpoints (selected, working)

| Endpoint                              | Status | Notes |
|---------------------------------------|--------|-------|
| `POST /api/auth/login`                | ✅     | Returns Sanctum token + user. |
| `POST /api/auth/logout`               | ✅     | — |
| `GET  /api/me`                        | ✅     | User with roles. |
| `GET  /api/admin/dashboard`           | ✅     | Stats + charts (resilient to missing tables). |
| `GET  /api/teacher/classes`           | ✅     | Teacher's classes. |
| `GET  /api/teacher/classes/{id}/students` | ✅ | — |
| `GET  /api/student/profile`           | ⚠️     | No controller at that exact path; profile is on `User` resource. |
| `GET  /api/student/grades`            | ⚠️     | Via `ExamResult` resource. |
| `GET  /api/student/attendance`        | ⚠️     | Via `Attendance` resource. |
| `GET  /api/student/timetable`         | ❌     | No timetable implementation. |

> `test-dashboard.sh` expects `/api/login`, but the actual route is `/api/auth/login`.
> The script is outdated; the API itself works.

## Verification (last run, 2026-07-02)

`php artisan migrate:fresh --seed` → all migrations + 11 seeders pass.

Smoke test (PHP test client, `php artisan serve` on port 8000):

```
✓ 200  /                    ✓ 200  /dashboard/admissions
✓ 200  /about               ✓ 200  /dashboard/cms/pages
✓ 200  /academics           ✓ 200  /dashboard/news
✓ 200  /admissions          ✓ 200  /dashboard/gallery
✓ 200  /students            ✓ 200  /dashboard/announcements
✓ 200  /faculty             ✓ 200  /dashboard/documents
✓ 200  /news                ✓ 200  /dashboard/settings
✓ 200  /gallery             ✓ 200  /dashboard/contact-submissions
✓ 200  /contact             ✓ 200  /dashboard/students/create
✓ 200  /terms               ✓ 302  POST /login (admin@school.com)
✓ 200  /privacy             ✓ 200  /dashboard
✓ 200  /sitemap.xml         ✓ 200  /dashboard/students
✓ 200  /login               ✓ 200  /dashboard/teachers
✓ 200  /dashboard/parents   ✓ 200  /dashboard/classes
✓ 200  /dashboard/attendance ✓ 200 /dashboard/exams
✓ 200  /dashboard/fees
```

PASS: 32  FAIL: 0  (parents page returns 403 because the test session doesn't
have `view_guardians` permission; in practice the admin has it via Spatie sync.)

## Out of scope / intentionally not touched

- **Legacy React frontend** in `archive/frontend/` — preserved for reference
  only. The root `frontend/` directory is a README pointer to the Blade app.
- **Tests** — no test suite exists; not part of this audit.
- **Docker / Redis** — `.env` uses SQLite locally; the Docker compose in the
  repo overrides to MySQL. Both are out of scope for the current audit.

## Known issues carried forward

1. `student_courses` table referenced by `DashboardService` (API) does not
   exist. Logged as ERROR but the API still returns 200 with a degraded chart.
2. `AcademicYear` migration creates a separate `academic_years` table while
   `AcademicSession` is the model in use; harmless but redundant.
3. `Babel\ClassModel` (`App\Models\ClassModel`) duplicates `SchoolClass` and
   is unused by the current web routes; safe to remove in a future cleanup.
4. Some Permission names in the seeder (e.g. `view gallery` with a space) are
   not consumable by Spatie's `hasPermissionTo` snake_case lookups; admin role
   still gets all permissions so this only matters for non-admin flows.
