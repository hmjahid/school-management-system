# Prompt — Build a Modern School Management System (Laravel + React + Tailwind)

**Goal:**

Build a production-ready, responsive School Management System (SMS) using **Laravel (API)**, **React (SPA frontend)** and **Tailwind CSS**. The app must support Bengali & English, role-based access (student, parent, teacher, admin), theming, certificate design & generation, result publishing, class routine management, notifications, and all typical school workflows. Deliver clean code, tests, docs and deployment scripts.

---

## Short summary to paste into an AI/code-gen tool

> Build a modern School Management System with Laravel (REST API) and React (SPA) styled with Tailwind CSS. Support multi-role login (admin, teacher, student, parent), Bengali & English i18n, dynamic theme switching (light/dark + color presets), student registration (online + admin approval), teacher and class management, class routine scheduling, attendance, grading and result publication, certificate template designer and PDF generation, notices/circulars/news, notices & announcements board, file uploads, role-based permissions, notifications (email + in-app), audit logs, import/export (CSV/Excel/PDF), responsive UI, accessible, unit & integration tests, CI/CD, Dockerized deployment and documentation. Provide REST endpoints, database migrations, seeders, React components, and example tests.
> 

---

## Detailed prompt / project brief (copy-paste ready)

**Project name:** Modern School Manager (or your chosen name)

**Tech stack**

- Backend: Laravel 10+ (API-only, Sanctum or JWT for auth)
- Frontend: React (Vite or Create React App), React Router, React Query (or SWR) for data fetching
- Styling: Tailwind CSS (with headlessui / radix for accessible components)
- Database: MySQL / MariaDB (or PostgreSQL)
- File storage: Local S3-compatible (MinIO) / AWS S3
- PDF generation: laravel-dompdf or Snappy (wkhtmltopdf)
- Notifications: Laravel Mail (SMTP) + in-app notifications stored in DB, optional push with FCM
- i18n: Laravel localization + react-i18next
- Auth: Laravel Sanctum (SPA) with role/permission layer (spatie/laravel-permission)
- Containerization & CI: Docker Compose, GitHub Actions or GitLab CI
- Testing: PHPUnit (backend), Jest + React Testing Library (frontend)
- Optional: Redis for cache & queues, Horizon for queue monitoring

---

## Core Features (must-have)

1. **Authentication & Roles**
    - Signup / login / password reset / email verification.
    - Roles: Super Admin, Admin, Teacher, Student, Parent, Accountant, Registrar.
    - Role & permission management UI (assign granular permissions).
2. **User Profiles**
    - Detailed profiles for students, teachers, parents.
    - Photo upload, contact details, emergency contact, documents (PDF/PNG).
3. **Student Registration**
    - Public online application form with file uploads.
    - Admin review / approve / reject workflow. Generate admission ID on approval.
4. **Class, Section & Batch Management**
    - Create classes, sections, batches, subjects and assign teachers.
    - Seating capacity, class resources.
5. **Class Routine / Timetable**
    - Drag-and-drop timetable builder.
    - Prevent conflicts (teacher, room, batch).
    - Export/print as PDF.
6. **Attendance**
    - Daily attendance for class or subject-level.
    - Bulk import attendance, export reports.
7. **Exams, Marks & Result Publishing**
    - Exam creation (term, annual, module).
    - Add components (written, MCQ, practical), grade mapping, passing marks.
    - Result calculation rules (weighting, final grade).
    - Preview results (admin/teacher) then publish. Notifications to students/parents.
    - Generate transcripts and mark sheets as PDF.
8. **Certificate Designer & Generator**
    - Visual template editor (upload background or choose template).
    - Drag-and-drop text fields (student name, roll, grade, date, signatures).
    - Store templates, preview, generate PDF certificates in bulk.
9. **Teacher Management**
    - Assign subjects, class responsibilities, workload reports.
10. **Parents Portal**
    - Link parents to student(s), view reports, attendance, fees, notices.
11. **Fees & Accounting (basic)**
    - Define fee items, invoices, payment records, receipts (PDF).
    - Integrate with payment gateways (Stripe/Razorpay) optional.
12. **Noticeboard / Circulars / Latest News**
    - Create notices, pin important circulars, mark read/unread.
    - News feed on dashboard.
13. **Messaging & Notifications**
    - In-app messages, email notifications, SMS gateway hooks.
    - Notification center with read/unread.
14. **Role-based Dashboards**
    - Tailored dashboards for Admin, Teacher, Student, Parent with widgets.
15. **Search & Filters**
    - Global search (students, teachers, classes, notices).
16. **Import / Export**
    - CSV/Excel import for students, staff, grades. Export of reports.
17. **Reports & Analytics**
    - Attendance reports, grade distributions, fee reports, teacher workload.
18. **Theme & Appearance**
    - Theme switcher: light/dark + preset color themes.
    - School branding (logo, colors) editable in admin settings.
    - Multi-language toggle (English / বাংলা).
19. **Security & Audit**
    - Input validation, CSRF protection, XSS prevention.
    - Audit logs for critical actions (user changes, result publish).
    - Rate-limiting, password requirements, 2FA optional.
20. **Accessibility & Mobile Responsive**
    - WCAG-friendly components, keyboard navigable.
21. **Documentation & Tests**
    - API documentation (OpenAPI/Swagger).
    - User manual for Admin/Teacher/Parent/Student.
    - Unit and integration tests with CI.

---

## Suggested Database Models (high-level)

- users (id, name, email, password, role_id, profile_data JSON, locale, avatar, ...)
- roles, permissions (spatie)
- students (user_id, admission_no, roll_no, class_id, dob, parent_id, address, documents JSON)
- parents (user_id, children relation)
- teachers (user_id, qualification, subjects JSON)
- classes, sections, batches
- subjects
- routines (class_id, section_id, subject_id, teacher_id, day, start, end, room)
- attendance (student_id, date, status, marked_by)
- exams, exam_terms, exam_results (student_id, exam_id, marks JSON, grade, published_at)
- certificates (template JSON, created_by)
- notices/news (title, content, attachments, pinned, audience)
- fees, invoices, payments
- notifications, audits, uploads

---

## API / Endpoints (examples)

- `POST /api/auth/login`
- `POST /api/auth/register` (for staff/parents via admin or controlled public student application)
- `GET /api/users/me`
- `GET /api/students` / `POST /api/students` / `PUT /api/students/{id}`
- `GET /api/classes` / `POST /api/classes`
- `GET /api/routines` / `POST /api/routines` (with conflict detection)
- `POST /api/exams` / `POST /api/exams/{id}/results` / `POST /api/exams/{id}/publish`
- `POST /api/certificates/templates` / `POST /api/certificates/generate`
- `GET /api/notices` / `POST /api/notices`
- `GET /api/notifications` / `POST /api/notifications/send`
- `GET /api/reports/attendance` / `GET /api/reports/grades`
- `GET /api/settings/theme` / `PUT /api/settings/theme`

Include pagination, filtering, and role-based response shaping.

---

## Frontend UI Structure / Components

- Auth pages (login, register, forgot password)
- Admin Dashboard (widgets: pending admissions, fees due, latest notices)
- Teacher Dashboard (today’s classes, pending marks, attendance)
- Student/Parent Dashboard (recent notices, attendance summary, latest results)
- Student profile & documents
- Admissions form (multi-step)
- Timetable builder (drag-and-drop)
- Marks entry screen (spreadsheet-like)
- Certificate designer (canvas with draggable fields)
- Notices & News list + editor (rich text)
- Theme & language switcher in header/footer
- Global search bar, Notifications dropdown, Responsive sidebar

---

## i18n & Bengali Support

- Use `react-i18next` with JSON translation files for frontend.
- Laravel: use `resources/lang/en/*.php` and `resources/lang/bn/*.php`.
- Keep UI strings translatable; store user-chosen locale on profile.
- Right-to-left not required (Bengali is LTR) — ensure fonts support Unicode Bengali (e.g., Noto Sans Bengali).

---

## Certificate Designer specifics

- Template editor: upload background image or choose blank canvas at letter/A4 sizes.
- Provide text fields placeholders: `{{student_name}}`, `{{class}}`, `{{grade}}`, `{{date}}`, `{{signature}}`.
- Allow font selection, size, alignment, rotation, opacity.
- Save templates in DB as JSON; generate one or batch certificates as PDF using server-side renderer.

---

## Result publish workflow

1. Teacher enters marks (draft).
2. Admin reviews; validations run (total vs max marks).
3. Admin clicks **Publish** → sets `published_at`.
4. System generates mark sheets & transcripts, sends notifications to students/parents.
5. Allow rollback/unpublish with audit log reason.

---

## Theming & Branding

- Admin can define: primary color, accent color, logo, favicon, default theme.
- Theme toggles saved per-user (persist in DB/localStorage).
- Use CSS variables and Tailwind config to support runtime theme switching.

---

## Security & Compliance

- Use HTTPS in production, secure cookies, CSP headers.
- File upload validation and virus scanning hook optional.
- GDPR-like data export & delete (data subject requests).
- Limit access to sensitive endpoints by role & permission.

---

## Testing, CI/CD & Deployment

- Unit tests for critical backend logic (grading, publishing).
- E2E tests for core user flows (Playwright or Cypress).
- GitHub Actions CI: run linters, run tests, build frontend, build Docker images, push to registry.
- Docker Compose for local dev; Kubernetes manifests / Helm for production optional.
- Provide a `deploy.md` with steps for AWS/GCP/DigitalOcean.

---

## Deliverables

- Laravel repo (API + migrations + seeders + tests).
- React repo (components, pages, i18n, Tailwind config).
- Docker Compose for development, Dockerfile for each service.
- Swagger/OpenAPI spec.
- User & admin documentation.
- Example seed data (school, classes, 20 students, 5 teachers).
- CI workflow files and deployment docs.

---

## Acceptance Criteria

- Multi-role login works and each role sees correct dashboard.
- Admit a student via online form and approve in admin panel.
- Create an exam, enter marks as teacher, have admin publish results and generate PDFs.
- Design and generate a certificate PDF for a student.
- Build and export a timetable without conflicts.
- Language switch toggles UI strings in Bengali and English.
- Theme switch changes look persistently per-user.
- Tests pass in CI; Dockerized app can run locally via `docker-compose up`.

---

## Example prompt for scaffolding (CLI / AI)

> Scaffold a Laravel API project with Sanctum, Spatie permissions, migrations for users/students/teachers/classes/exams, seeders for sample data, and an OpenAPI doc. Also scaffold a React Vite app with Tailwind, react-i18next, React Router, and a basic layout with role-based routing. Include Dockerfiles and a docker-compose.yml that runs app, db, and redis. Add sample tests and GitHub Actions for CI.
>