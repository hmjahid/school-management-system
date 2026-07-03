# Plan: Build Incomplete README Features (Results + Reports + Events + Bulk)

## Scope (locked with user)

Per user choice: **Results Processing, Financial Reports, Events & Calendar, Bulk Import/Export**. New models/migrations allowed where needed.

README features explicitly **out of scope** (not building now):
- Timetable & Scheduling
- Dark/Light Mode
- Library Management
- Transport Management
- Hostel Management
- SMS & Email Notifications
- Online payment gateway flow (already a stub)

## Backend already in place (audit findings)

These exist and are testable — we just need Blade UI on top:

| Piece | Status | Source |
|---|---|---|
| `Exam` model + `calculateGrade()` + `getStatistics()` + `grading_scale` | ✅ | `app/Models/Exam.php` |
| `ExamResult` model + scopes (published/pending/passed/failed) + `publish()` | ✅ | `app/Models/ExamResult.php` |
| `ExamResultPolicy` (view/update/publish/unpublish) | ✅ | `app/Policies/ExamResultPolicy.php` |
| `Event` model + scopes + `isRegistrationOpen` | ✅ | `app/Models/Event.php` |
| `Payment` model + scopes + `markAsCompleted` | ✅ | `app/Models/Payment.php` |
| `FeePayment` model | ✅ | `app/Models/FeePayment.php` |
| `DashboardController` (live stats) | ✅ | `app/Http/Controllers/Web/DashboardController.php` |

## Work to do

### A. Result Processing

**New controller:** `app/Http/Controllers/Web/DashboardExamResultController.php`
- `index(Exam $exam)` — list students + their result
- `store(Request, Exam $exam)` — bulk save marks
- `update(Request, Exam $exam, ExamResult $result)` — single update
- `publish(Exam $exam)` / `unpublish(Exam $exam)`
- `export(Exam $exam)` — CSV

**New views:**
1. `resources/views/dashboard/exams/results.blade.php` — table with marks input
2. `resources/views/dashboard/exams/show.blade.php` — exam detail + stats
3. `resources/views/dashboard/students/results.blade.php` — student results

**Routes:**
```
GET   /dashboard/exams/{exam}/results
POST  /dashboard/exams/{exam}/results
GET   /dashboard/exams/{exam}/results/export
POST  /dashboard/exams/{exam}/publish
POST  /dashboard/exams/{exam}/unpublish
GET   /dashboard/students/{student}/results
```

### B. Financial Reports

**New controller:** `app/Http/Controllers/Web/DashboardReportController.php`
- `index()` — picker
- `fees(Request)` — date-range, group by month, status breakdown
- `attendance(Request)` — per-class, per-month
- `students(Request)` — per-class, status breakdown
- `export(Request)` — CSV

**New views:**
1. `resources/views/dashboard/reports/index.blade.php` — picker
2. `resources/views/dashboard/reports/fees.blade.php`
3. `resources/views/dashboard/reports/attendance.blade.php`
4. `resources/views/dashboard/reports/students.blade.php`

**Routes:**
```
GET /dashboard/reports
GET /dashboard/reports/fees
GET /dashboard/reports/attendance
GET /dashboard/reports/students
GET /dashboard/reports/export/{type}
```

### C. Events & Calendar

**New controller:** `app/Http/Controllers/Web/DashboardEventController.php`
- `index()` — list
- `calendar()` — month grid
- `create/store/edit/update/destroy`

**New views:**
1. `resources/views/dashboard/events/{index,calendar,create,edit}.blade.php`
2. `resources/views/site/events.blade.php` — public

**Routes:**
```
GET    /dashboard/events
GET    /dashboard/events/calendar
GET    /dashboard/events/create
POST   /dashboard/events
GET    /dashboard/events/{event}/edit
PUT    /dashboard/events/{event}
DELETE /dashboard/events/{event}
GET    /events  (public)
```

### D. Bulk Import/Export (CSV only, no extra deps)

**New controller:** `app/Http/Controllers/Web/DashboardBulkController.php`
- `index()` — picker
- `export(Request)` — CSV
- `import(Request)` — preview
- `importStore(Request)` — write rows

**New views:**
1. `resources/views/dashboard/bulk/index.blade.php`
2. `resources/views/dashboard/bulk/import.blade.php`

**Routes:**
```
GET  /dashboard/bulk
GET  /dashboard/bulk/export/{resource}
GET  /dashboard/bulk/import/{resource}
POST /dashboard/bulk/import/{resource}
```

### E. Sidebar + public site updates

`resources/views/partials/dashboard/sidebar.blade.php` — add Reports, Events, Bulk links (admin).
`SitePageController` — add `events()` method for public.

## Files created

| File | Purpose |
|---|---|
| 4 controllers | results / reports / events / bulk |
| 12 Blade views | listed above |
| `routes/web.php` (modify) | new routes |
| `sidebar.blade.php` (modify) | nav links |
| `SitePageController.php` (modify) | public events |

## Verification

1. `php artisan route:list | wc -l` → grows by ~25
2. `php artisan migrate:fresh --seed` → still clean
3. Smoke test all new URLs return 200
4. CSV import: upload 2-row students CSV, confirm rows created
5. CSV export: download students CSV, verify headers
6. Publish exam results: flip flag, then visible in `/portal/progress` for student

## Out of scope

- PDF generation (no DOMPDF install; use print CSS)
- ICS calendar export
- Email/SMS notifications
- Real-time calendar drag-drop

## Risks

- Bulk import security: enforce role + CSRF + valid headers
- Result publish: must wire to existing `PortalProgressController` (already filters `is_published = true`)
- Grade scale: `Exam::calculateGrade()` has default fallback
