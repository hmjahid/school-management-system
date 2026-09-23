#!/usr/bin/env python3
"""Generate docs/feature-tracking/products-feature-matrix.xlsx.

Tracks every feature across the 4 Eskoofy products (Laravel app / raw-PHP app /
WP theme / Node.js variant) with Implemented + Working columns per product.
Statuses are derived from repo evidence (docs/parity, PORTING-STATUS,
NOT-IMPLEMENTED, READMEs, AGENTS, CI status) — see the Legend sheet.

Run from the monorepo root:
    python3 docs/feature-tracking/build-product-matrix.py
"""
import os
import sys
from openpyxl import Workbook
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

ROOT = os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", ".."))
OUT_DIR = os.path.join(ROOT, "docs", "feature-tracking")
OUT_PATH = os.path.join(OUT_DIR, "products-feature-matrix.xlsx")

# ---- palettes ---------------------------------------------------------------
IMPL = {"Yes": "green", "Partial": "amber", "No": "red"}
WORK = {"Yes": "green", "No": "red", "Not tested": "grey", "N/A": "grey"}
FILL = {
    "green": PatternFill("solid", fgColor="C6EFCE"),
    "red": PatternFill("solid", fgColor="FFC7CE"),
    "amber": PatternFill("solid", fgColor="FFEB9C"),
    "grey": PatternFill("solid", fgColor="D9D9D9"),
    "header": PatternFill("solid", fgColor="1F4E78"),
    "module": PatternFill("solid", fgColor="DDEBF7"),
}
FONT_HEADER = Font(color="FFFFFF", bold=True, size=11)
FONT_MODULE = Font(bold=True, size=10)
BORDER = Border(*[Side(style="thin", color="BFBFBF")] * 4)


def S(impl, working=None):
    if working is None:
        working = "N/A" if impl == "No" else "Yes"
    return (impl, working)


# (module, feature, description, laravel, php, theme, node, notes)
FEATURES = [
    # ── Dashboard & UX ─────────────────────────────────────────────────────
    ("Dashboard & UX", "Dashboard home (stat cards, revenue-vs-expense chart, attendance trend, quick actions, workbench)",
     "The main /dashboard landing with KPIs and charts.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "L/P byte-identical shell (parity doc); T dashboard.php template (workbench always shown); N bespoke dashboard home (PORTING-STATUS)."),
    ("Dashboard & UX", "Onboarding / setup-completion banner",
     "6-item setup checklist banner on dashboard home.",
     S("Yes"), S("Yes"), S("No"), S("No"),
     "T missing ('Setup completion banner MISSING → port', docs/parity). N: not among bespoke screens (PORTING-STATUS)."),
    ("Dashboard & UX", "Command palette + global dashboard search",
     "Ctrl/Cmd+K search across records; /dashboard/search endpoint.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: dashboard search route + command palette wired (NOT-IMPLEMENTED §1)."),
    ("Dashboard & UX", "Dark mode + theme presets",
     "Class-based dark mode and theme-{default,modern,classic,minimal} styles.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "L/P shared Blade shell; T dark mode in assets/js/main.js; N dark mode + theme-* styles (PORTING-STATUS)."),
    ("Dashboard & UX", "Pinned favourites / pin-page",
     "Star a sidebar item; favourites footer + toggle endpoint.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: /dashboard/favorites/toggle wired to topbar star (NOT-IMPLEMENTED §1). T: favorites via admin-ajax (README)."),
    ("Dashboard & UX", "Topbar (mobile drawer, live clock, notifications dropdown, user menu)",
     "Dashboard chrome topbar with timezone clock and notifications.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: 'Topbar: … live timezone clock … notifications with unread badge' (PORTING-STATUS)."),
    ("Dashboard & UX", "Dashboard locale switch",
     "Switch UI language from the topbar.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: /dashboard/locale/{locale} wired end-to-end (NOT-IMPLEMENTED §8). T: en/bn via build-time .po profiles."),
    ("Dashboard & UX", "Toast + confirm-modal infrastructure",
     "Global UI primitives for feedback and destructive confirmations.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: 'toast + confirm-modal roots' (PORTING-STATUS). T: window.eskToast/eskConfirm (AGENTS)."),
    ("Dashboard & UX", "Print stylesheets / print views",
     "Print CSS for results, receipts, ID cards, certificates.",
     S("Yes"), S("Yes"), S("Yes", "Not tested"), S("Yes"),
     "Blade print views shared L/P; T partial (print media styles not confirmed); N 'print CSS' present, no PDF (NOT-IMPLEMENTED §3)."),

    # ── Auth & Access ──────────────────────────────────────────────────────
    ("Auth & Access", "Staff/admin email+password login",
     "/login with role-aware redirect to /dashboard.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: template-login.php + /login fallback (AGENTS gotcha). N: verified e2e (PORTING-STATUS)."),
    ("Auth & Access", "Student login",
     "Separate student login entry point.",
     S("Yes"), S("Yes"), S("Partial", "Not tested"), S("Yes"),
     "L/P: /student/login. T: not separately documented; N: single /login entry for all roles (simplified)."),
    ("Auth & Access", "Guardian login",
     "Parent/guardian login entry point.",
     S("Yes"), S("Yes"), S("Partial", "Not tested"), S("Yes"),
     "L/P: /guardian/login. T: not separately documented; N: single /login entry for all roles (simplified)."),
    ("Auth & Access", "Password reset (forgot-password flow)",
     "Forgot/reset password pages + token flow.",
     S("Yes"), S("Yes"), S("Partial", "Not tested"), S("Yes"),
     "T relies on WP core resets (WP-specific); N: forgot/reset password added (PORTING-STATUS)."),
    ("Auth & Access", "Roles & permissions RBAC",
     "Role-based access control over dashboard modules.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "L: spatie/laravel-permission; P: config/access.php + Gate; T: esk_role_caps map; N: cookie role→permission map (AGENTS/PORTING)."),
    ("Auth & Access", "Permission matrix admin screen",
     "Grouped permission→role matrix UI.",
     S("Yes"), S("Yes"), S("Yes", "Not tested"), S("Yes"),
     "T: Dashboard → Roles & Permissions edits esk_role_caps (SETUP-GUIDE §5). N: bespoke permissions matrix (NOT-IMPLEMENTED §2)."),
    ("Auth & Access", "Per-model policies / route gates (spatie parity)",
     "Fine-grained ability checks beyond the role map.",
     S("Yes"), S("Yes"), S("Yes", "Not tested"), S("Partial", "Yes"),
     "N: 'per-model policies … approximated with can() role map' (NOT-IMPLEMENTED §6)."),
    ("Auth & Access", "Dashboard write rate-limiting",
     "Throttle dashboard POST/PUT/PATCH/DELETE (120/min).",
     S("Yes"), S("No"), S("No"), S("No"),
     "L: throttle.dashboard middleware (AGENTS). No equivalent found for P/T/N."),
    ("Auth & Access", "API token / session auth",
     "Bearer tokens (Laravel Sanctum) or cookie-session JWT.",
     S("Yes"), S("Yes"), S("Yes", "Not tested"), S("Yes"),
     "L: Sanctum; P: API auth mirroring app; T: nonce/REST auth; N: cookie session JWT (README)."),

    # ── People ─────────────────────────────────────────────────────────────
    ("People", "Students CRUD + detail page",
     "Create/view/edit/delete students (class, batch, roll).",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "L/P shared views; T students.php/student-form.php; N generic CRUD engine (verified e2e)."),
    ("People", "Student bulk import/export",
     "CSV import/export of student records.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "L/P /dashboard/bulk; T bulk.php; N bulk/export+import route.ts (NOT-IMPLEMENTED §1)."),
    ("People", "Year-end student promotion",
     "Bulk-promote a batch to the next class/session.",
     S("Yes"), S("Yes"), S("No"), S("Yes"),
     "N: PromoteStudents implemented (NOT-IMPLEMENTED §4). T: no promote screen in views/admin."),
    ("People", "Teachers CRUD",
     "Teacher records with employee identifiers and subjects.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: teacher-form.php; N: engine CRUD."),
    ("People", "Staff directory",
     "Directory of all staff with roles.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: staff-directory.php; N: engine CRUD."),
    ("People", "Parents/guardians CRUD",
     "Guardian records linked to students.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: guardians.php; N: engine CRUD."),
    ("People", "All users admin",
     "User management list (admins/staff/student/guardian users).",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: users.php; N: engine CRUD."),

    # ── Academics ──────────────────────────────────────────────────────────
    ("Academics", "Classes (school_classes) CRUD",
     "Class levels/rolls.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: classes.php; N: engine CRUD."),
    ("Academics", "Sections CRUD",
     "Class sections.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: sections.php; N: engine CRUD."),
    ("Academics", "Subjects CRUD",
     "Subjects per academic setup.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: subjects.php; N: engine CRUD."),
    ("Academics", "Batches CRUD",
     "Academic batches (exam/roll tie-in).",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: batches.php; N: engine CRUD."),
    ("Academics", "Academic sessions CRUD",
     "Session/year records.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: academic-sessions.php; N: engine CRUD."),
    ("Academics", "Class routine / timetable",
     "Weekly timetable builder per class/section.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: routines.php; N: engine CRUD."),
    ("Academics", "Assignments",
     "Assignment records per class/subject.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: assignments.php; N: engine CRUD."),
    ("Academics", "Exams + mark entry + publish/visibility",
     "Exam creation, obtained-marks entry, publish + student visibility.",
     S("Yes"), S("Yes"), S("Yes"), S("Partial", "Yes"),
     "N: CRUD works but full isFullyPublished() semantics not ported (NOT-IMPLEMENTED §4)."),
    ("Academics", "My results (student view)",
     "Student-facing results page.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: my-results bespoke screen (NOT-IMPLEMENTED known-good)."),
    ("Academics", "Daily attendance (per class)",
     "Mark daily attendance per class/section.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: attendance.php; N: engine CRUD + list."),
    ("Academics", "Bulk attendance marking",
     "Mark a whole class at once.",
     S("Yes"), S("Yes"), S("Yes"), S("Partial", "Yes"),
     "N: 'basic bulk screen' (NOT-IMPLEMENTED §4)."),
    ("Academics", "Staff attendance",
     "HR staff attendance marking + reports.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: staff-attendance.php; N: 'daily staff-attendance sheet' bespoke (NOT-IMPLEMENTED §2)."),
    ("Academics", "Progress reports & seat plans",
     "Per-student progress reports and exam seat plans.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: progress-reports/seat-plans bespoke screens (NOT-IMPLEMENTED known-good)."),

    # ── Admissions ─────────────────────────────────────────────────────────
    ("Admissions", "Public admission application form",
     "Public apply flow at /admissions/apply.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: template-admission(s).php; N: /admissions/apply real page."),
    ("Admissions", "Admission review workflow",
     "Review applications, mark documents, schedule tests, change status.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: /dashboard/admissions/{id} bespoke review (NOT-IMPLEMENTED §2). T: admission-detail.php."),
    ("Admissions", "Admission payment verification",
     "Verify/approve admission fees during review.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: payment verify in review workflow."),
    ("Admissions", "Public admission status lookup",
     "Applicants check application status at /admissions/status.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: /admissions/status page."),

    # ── Finance ────────────────────────────────────────────────────────────
    ("Finance", "Fee structure per class/type",
     "Define fees per class and fee type.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: fees.php; N: engine CRUD."),
    ("Finance", "Fee collection + receipts/invoices",
     "Collect payments, generate receipts/invoices.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: fee-payments.php; N: engine CRUD + receipts print."),
    ("Finance", "Refunds",
     "Raise and track refunds against payments.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: refunds.php (theme extra token, parity §1)."),
    ("Finance", "Expenses + expense categories",
     "Expense records and categories.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: expenses.php / expense-categories.php."),
    ("Finance", "Budgets",
     "Budget planning per category/period.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: budgets.php (extra in theme sidebar, parity §1)."),
    ("Finance", "Ledger + chart of accounts (double-entry)",
     "Journal/cashbook + auto-postings on payments/expenses.",
     S("Yes"), S("Yes"), S("Yes"), S("Partial", "Yes"),
     "N: generic CRUD only, auto-posting not ported (NOT-IMPLEMENTED §4)."),
    ("Finance", "Bank reconciliation",
     "Reconcile bank activity to ledger.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: bank-reconciliation.php."),
    ("Finance", "Income statement",
     "Financial statement report.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: financial reports resolve to real tables (PORTING-STATUS)."),
    ("Finance", "Balance sheet",
     "Financial statement report.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "As income statement."),
    ("Finance", "Cash flow statement",
     "Financial statement report.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "As income statement."),
    ("Finance", "Reports builder",
     "Custom report builder screen.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: reports/builder bespoke (NOT-IMPLEMENTED known-good)."),
    ("Finance", "Analytics dashboard",
     "Live-stat analytics overview.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: analytics.php; N: analytics bespoke."),
    ("Finance", "Payment gateways — BD (bKash/Rocket/Nagad) initiation + callback",
     "Live gateway checkout flows.",
     S("Yes", "Not tested"), S("Yes", "Not tested"), S("Yes", "Not tested"), S("No"),
     "L/P/T: adapters ship, need live creds to verify. N: 'no real initiate/callback/webhook handlers' (NOT-IMPLEMENTED §5)."),
    ("Finance", "Payment gateways — INT (Stripe/PayPal/Paddle) initiation + callback",
     "Live gateway checkout flows.",
     S("Yes", "Not tested"), S("Yes", "Not tested"), S("Yes", "Not tested"), S("No"),
     "Same as above (INT)."),
    ("Finance", "Offline / bank-transfer payments",
     "Manual offline payment approvals.",
     S("Yes"), S("Yes"), S("Yes"), S("Partial", "Yes"),
     "N: config + settings exist; full offline flow not confirmed."),
    ("Finance", "Recurring fees / auto-invoice scheduler",
     "Automatic recurring payment + invoice generation.",
     S("Yes"), S("Yes"), S("No"), S("No"),
     "L: RecurringPaymentService in scheduler; P: public/cron.php daily 01:00; T/N: no cron equivalent."),

    # ── HR ─────────────────────────────────────────────────────────────────
    ("HR", "Leaves + leave types",
     "Leave types, requests, approval workflow.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: leave-types.php / leave-requests.php; N: engine CRUD."),
    ("HR", "Payroll: salary structures + payslips",
     "Define salary structures, generate + mark payslips paid.",
     S("Yes"), S("Yes"), S("Yes"), S("Partial", "Yes"),
     "N: 'generic CRUD + generate screen' (NOT-IMPLEMENTED §4)."),
    ("HR", "Staff attendance (HR view)",
     "HR staff-attendance marking (shared screen, see Academics).",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: staff-attendance.php; N: daily sheet."),

    # ── Documents ──────────────────────────────────────────────────────────
    ("Documents", "Admit cards",
     "Printable (L: PDF) admit cards per exam.",
     S("Yes"), S("Partial", "Not tested"), S("Partial", "Not tested"), S("Partial", "Yes"),
     "L: dompdf PDF. P: print Blade only, no composer runtime/dompdf. T: print page. N: print view, no PDF file (NOT-IMPLEMENTED §3)."),
    ("Documents", "Student ID cards",
     "Printable (L: PDF) ID cards.",
     S("Yes"), S("Partial", "Not tested"), S("Partial", "Not tested"), S("Partial", "Yes"),
     "Same PDF story as admit cards."),
    ("Documents", "Certificates",
     "Printable (L: PDF) certificates.",
     S("Yes"), S("Partial", "Not tested"), S("Partial", "Not tested"), S("Partial", "Yes"),
     "T: certificates.php; same PDF story."),
    ("Documents", "Testimonials (admin + public)",
     "Testimonial records, front-page display.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: testimonials-admin.php."),
    ("Documents", "Committee members",
     "Committee roster (admin + public page).",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: committee.php."),
    ("Documents", "Media library (upload + picker)",
     "Upload files, browse, embed via media picker.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: media library upload + ?select=1 picker bespoke (NOT-IMPLEMENTED §2). P: MediaController."),

    # ── Library ────────────────────────────────────────────────────────────
    ("Library", "Books + book categories",
     "Catalog + categories.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: library.php; N: engine CRUD."),
    ("Library", "Book issues (issue/return/fines)",
     "Issue/return tracking with overdue/fine handling.",
     S("Yes"), S("Yes"), S("Yes", "Not tested"), S("Yes"),
     "N: engine CRUD over book_issues."),
    ("Library", "Library reports",
     "Issued/overdue/history reports.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: library-reports.php."),

    # ── Facilities ─────────────────────────────────────────────────────────
    ("Facilities", "Events + calendar",
     "Events with calendar view.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: events.php / events-calendar.php."),
    ("Facilities", "Transport (vehicles/routes/assignments)",
     "Vehicles, routes with stops, student assignments.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: wrong-table resolutions fixed → vehicles (NOT-IMPLEMENTED known-good)."),
    ("Facilities", "Hostel (rooms/assignments)",
     "Hostels, rooms, occupant assignments.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: hostels.php."),

    # ── Communications ─────────────────────────────────────────────────────
    ("Communications", "Messages (internal)",
     "Internal messaging between users.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: messages.php."),
    ("Communications", "Bulk SMS campaign (compose/preview/send)",
     "SMS campaign lifecycle.",
     S("Yes", "Not tested"), S("Yes", "Not tested"), S("Yes", "Not tested"), S("Partial", "Yes"),
     "N: campaign lifecycle ported but no carrier delivery (NOT-IMPLEMENTED §4/§5)."),
    ("Communications", "SMS carrier delivery (Twilio/Vonage/TextLocal…)",
     "Actual SMS send via a provider.",
     S("Yes", "Not tested"), S("Yes", "Not tested"), S("Yes", "Not tested"), S("No"),
     "L: SMS_DRIVER drivers; P: config/sms.php 6 drivers; T: sms-gateway.php; N: not wired."),
    ("Communications", "Notification templates",
     "Reusable notification content templates.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: notification-templates.php."),
    ("Communications", "Notification preferences",
     "Per-user notification preference toggles.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: notification-preferences.php."),
    ("Communications", "In-app notifications inbox",
     "Notification stream with read/unread.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: bespoke inbox (NOT-IMPLEMENTED §2); T: notifications.php + REST notifications."),

    # ── Website CMS ────────────────────────────────────────────────────────
    ("Website CMS", "CMS pages + content editing",
     "Page content management with CMS editor.",
     S("Yes"), S("Yes"), S("Yes"), S("Partial", "Yes"),
     "N: CmsEdit typed editors done; live preview iframe + openMediaBrowser() missing (NOT-IMPLEMENTED §2)."),
    ("Website CMS", "CMS settings tab",
     "Website settings editor tab.",
     S("Yes"), S("Yes"), S("No"), S("No"),
     "T: 'CMS Settings absent in theme sidebar' (parity §1). N: 'about/cms/global-labels tabs remain' (NOT-IMPLEMENTED §2)."),
    ("Website CMS", "Global labels",
     "UI label overrides.",
     S("Yes"), S("Yes"), S("No"), S("No"),
     "T: 'Global Labels absent in theme sidebar' (parity §1). N: global-labels tab remains."),
    ("Website CMS", "News + article detail",
     "News CRUD + public article pages.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: news.php + single-esk_news.php; N: /news + /news/{slug}."),
    ("Website CMS", "Events (public + admin)",
     "Events CRUD + public events listing.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: events.php + archive-esk_events.php."),
    ("Website CMS", "Gallery + lightbox",
     "Gallery CRUD + public gallery with lightbox.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: gallery.php + archive-esk_galleries.php."),
    ("Website CMS", "Announcements",
     "Announcement posts.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: announcements.php."),
    ("Website CMS", "Notices",
     "Notice board posts (admin + public, paginated).",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: notices.php + archive-esk_notices.php."),
    ("Website CMS", "Documents (website module)",
     "CMS-managed downloadable documents.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: documents.php."),
    ("Website CMS", "Contact / form submissions",
     "Contact form + submission inbox + export.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: contact-submissions.php."),
    ("Website CMS", "Careers + job applications",
     "Job postings + application tracking.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: careers.php / career-applications.php + archive-esk_careers.php."),

    # ── Public website ─────────────────────────────────────────────────────
    ("Public website", "Home page (hero/slider/CMS)",
     "CMS-driven homepage.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: home real page (PORTING-STATUS)."),
    ("Public website", "About / academics / students / faculty / transport / committee pages",
     "Static + CMS info pages.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: template-*.php; N: real pages."),
    ("Public website", "News / notices / events / gallery listing pages",
     "Public listings with detail pages.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: 28 public pages wired to app tables (PORTING-STATUS)."),
    ("Public website", "Public results lookup",
     "/results lookup by roll/batch.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: results lookup shortcode (README); N: /results real query."),
    ("Public website", "Routine page",
     "Public timetable page.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: template via routines; N: /routine."),
    ("Public website", "Contact page + form",
     "Contact form + submissions (see CMS row).",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     ""),
    ("Public website", "Site-wide search",
     "Search public content.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: search.php + template-search.php."),
    ("Public website", "Payments page + status/receipts (public)",
     "Public fee/invoice payment page.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: fee payment shortcode; N: payments family pages."),
    ("Public website", "Student/guardian portal",
     "/portal with admission + progress views.",
     S("Yes"), S("Yes"), S("Yes"), S("Partial", "Yes"),
     "N: auth-gated portal; 'staff-role redirect + per-object 403 use simplified role match' (NOT-IMPLEMENTED §6)."),
    ("Public website", "sitemap.xml / robots.txt",
     "SEO outputs.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "N: sitemap.xml route present."),

    # ── API ────────────────────────────────────────────────────────────────
    ("API", "JSON API {success,message,data,meta} envelope",
     "Standardized API response envelope.",
     S("Yes"), S("Yes"), S("Yes", "Not tested"), S("Yes"),
     "L: StandardizeApiResponse; N: lib/api-response.ts (parity contract)."),
    ("API", "Results lookup API endpoint",
     "GET /api/v1/academics/results/lookup.",
     S("Yes"), S("Yes"), S("Yes", "Not tested"), S("Yes"),
     "N: verified e2e (PORTING-STATUS)."),
    ("API", "Generic REST over tables",
     "CRUD JSON endpoints for records.",
     S("Yes"), S("Partial", "Yes"), S("Partial", "Not tested"), S("Yes"),
     "L: typed api groups; P: 11 typed API controllers (README) — not fully generic; T: esk/v1 subset; N: generic engine over every table."),
    ("API", "Gateway webhooks / callbacks",
     "*/webhook/* + */callback/* endpoints.",
     S("Yes", "Not tested"), S("Yes", "Not tested"), S("Partial", "Not tested"), S("No"),
     "L/P: gateway webhook surfaces; T: partial; N: not ported (NOT-IMPLEMENTED §5)."),
    ("API", "Notifications API",
     "List/mark-read notification endpoints.",
     S("Yes"), S("Yes"), S("Yes", "Not tested"), S("Yes"),
     "T: notifications in esk/v1 REST (README)."),

    # ── Cross-cutting ──────────────────────────────────────────────────────
    ("Cross-cutting", "i18n (en + bn) string coverage",
     "UI strings in both languages.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "L/P: lang/{en,bn}; T: .pot + bn_BD/en_GB .po; N: 742 keys x2 (parity-gated)."),
    ("Cross-cutting", "Public-site locale switching",
     "Per-request ?lang / session locale switch.",
     S("Yes"), S("Yes"), S("Partial", "Not tested"), S("Partial", "Yes"),
     "L/P: /locale/{locale}; T: build-time .po profiles; N: public ?lang partially wired (NOT-IMPLEMENTED §8)."),
    ("Cross-cutting", "PWA (manifest + service worker + offline)",
     "Installable/offline app shell.",
     S("Yes"), S("Yes"), S("Yes"), S("Partial", "Yes"),
     "L: site.manifest route + sw.js + icons; P: /manifest.json route + sw.js + icons; T: pwa/ folder served; N: sw.js/offline/icons in public but not wired into layout."),
    ("Cross-cutting", "PDF file generation",
     "Downloadable PDF (dompdf-equivalent).",
     S("Yes"), S("No"), S("No"), S("No"),
     "L: barryvdh/laravel-dompdf. P: no composer runtime → print views only. T: no PDF lib. N: printable HTML only."),
    ("Cross-cutting", "Scheduled jobs / cron",
     "Recurring payments, scheduled notifications, backups.",
     S("Yes"), S("Yes"), S("No"), S("No"),
     "L: artisan scheduler (backup 02:00, queue monitor). P: public/cron.php (recurring + notifications). T/N: none."),
    ("Cross-cutting", "Queue worker / async jobs",
     "Async job processing.",
     S("Yes"), S("No"), S("No"), S("No"),
     "L: database/redis queue; P/T/N: no queue runtime."),
    ("Cross-cutting", "Backups (manual + scheduled download)",
     "Database backup + download.",
     S("Yes"), S("Yes"), S("Yes"), S("Partial", "Yes"),
     "N: 'manual action only' per NOT-IMPLEMENTED §4; T: backup.php; P: BackupController."),
    ("Cross-cutting", "Activity log (audit trail)",
     "Record user actions.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: activity.php; N: 'activity log audit screen' bespoke (NOT-IMPLEMENTED §2)."),
    ("Cross-cutting", "Visitor logs",
     "Track visitor paths/locations.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: visitor-logs.php; N: engine CRUD."),
    ("Cross-cutting", "Cache / tools admin",
     "Clear caches + maintenance tools.",
     S("Yes"), S("Yes"), S("Yes"), S("Yes"),
     "T: cache.php / tools.php (theme tokens, parity §1); N: settings/tools surface."),
    ("Cross-cutting", "Mail settings + templates",
     "SMTP config + email templates.",
     S("Yes", "Not tested"), S("Yes", "Not tested"), S("Partial", "Not tested"), S("Partial", "Yes"),
     "N: mail_enabled + SMTP settings form, no queue/mailer (NOT-IMPLEMENTED §5)."),
    ("Cross-cutting", "Push notifications (FCM)",
     "Firebase push delivery.",
     S("Yes", "Not tested"), S("Yes", "Not tested"), S("No"), S("No"),
     "L/P: PUSH_DRIVER firebase in .env; T/N: not present."),
]

PRODUCTS = ["Laravel App", "PHP App", "WP Theme", "Node.js"]


def style_ok(cell, kind):
    pool = IMPL if kind == "impl" else WORK
    v = cell.value
    if v in pool:
        cell.fill = FILL[pool[v]]
    cell.font = Font(bold=(kind == "impl" and v == "Yes"), size=10 if kind == "impl" else 9)


def header_row(ws, headers, first=True):
    ws.append(headers)
    for c in range(1, len(headers) + 1):
        cell = ws.cell(row=ws.max_row, column=c)
        cell.fill = FILL["header"]
        cell.font = FONT_HEADER
        cell.alignment = Alignment(vertical="center", horizontal="center", wrap_text=True)
        cell.border = BORDER
    if first:
        ws.freeze_panes = "A2"
        ws.auto_filter.ref = ws.dimensions


wb = Workbook()

# ── Sheet 1: Feature Matrix ─────────────────────────────────────────────────
ws = wb.active
ws.title = "Feature Matrix"
HEADERS = [
    "ID", "Module", "Feature", "Description",
    "Laravel App — Implemented", "Laravel App — Working",
    "PHP App — Implemented", "PHP App — Working",
    "WP Theme — Implemented", "WP Theme — Working",
    "Node.js — Implemented", "Node.js — Working",
    "Notes",
]
for i, w in enumerate([6, 16, 38, 42, 11, 11, 11, 11, 11, 11, 11, 11, 46], start=1):
    ws.column_dimensions[get_column_letter(i)].width = w
ws.append(HEADERS)
for c in range(1, len(HEADERS) + 1):
    cell = ws.cell(row=1, column=c)
    cell.fill = FILL["header"]
    cell.font = FONT_HEADER
    cell.alignment = Alignment(vertical="center", horizontal="center", wrap_text=True)
    cell.border = BORDER
ws.freeze_panes = "E2"
ws.auto_filter.ref = "A1:M1"

for idx, (module, feature, desc, lar, php, theme, node, notes) in enumerate(FEATURES, start=1):
    row = [idx, module, feature, desc,
           lar[0], lar[1], php[0], php[1], theme[0], theme[1], node[0], node[1], notes]
    ws.append(row)
    r = ws.max_row
    for c in range(1, len(HEADERS) + 1):
        cell = ws.cell(row=r, column=c)
        cell.value = row[c - 1]
        cell.alignment = Alignment(vertical="top", wrap_text=True)
        cell.border = BORDER
        cell.font = Font(size=9)
        if c in (5, 7, 9, 11):
            style_ok(cell, "impl")
        elif c in (6, 8, 10, 12):
            style_ok(cell, "work")
        elif c == 2:
            cell.fill = FILL["module"]
            cell.font = FONT_MODULE
        elif c == 1:
            cell.font = Font(size=9, color="808080")

# ── Sheet 2: Summary ────────────────────────────────────────────────────────
s = wb.create_sheet("Summary")
s["A1"] = "Eskoofy products — feature tracking summary"
s["A1"].font = Font(bold=True, size=13)

implemented = {p: {"Yes": 0, "Partial": 0, "No": 0} for p in PRODUCTS}
working = {p: {"Yes": 0, "Not tested": 0, "No": 0, "N/A": 0} for p in PRODUCTS}
total = {p: 0 for p in PRODUCTS}
for (_, _, _, lar, php, theme, node, _) in FEATURES:
    pairs = {"Laravel App": lar, "PHP App": php, "WP Theme": theme, "Node.js": node}
    for p, (impl, work) in pairs.items():
        total[p] += 1
        implemented[p][impl if impl in implemented[p] else "No"] = implemented[p].get(impl, 0) + 1
        working[p][work] = working[p].get(work, 0) + 1

r = 3
s.cell(row=r, column=1, value="Implemented status (feature rows)").font = Font(bold=True)
r += 1
hdr = ["Implemented", *PRODUCTS, *(f"{p} (%)" for p in PRODUCTS)]
header_row(s, hdr, first=False)
r = s.max_row + 1
for status in ("Yes", "Partial", "No"):
    counts = [implemented[p][status] for p in PRODUCTS]
    pcts = [round(c / total[p] * 100, 1) for c, p in zip(counts, PRODUCTS)]
    s.append([status, *counts, *pcts])
    for c in range(1, len(hdr) + 1):
        cell = s.cell(row=s.max_row, column=c)
        cell.border = BORDER
        cell.alignment = Alignment(horizontal="center")
        if status in FILL:
            cell.fill = FILL[status]
s.append(["Total", *[total[p] for p in PRODUCTS], *[100.0] * len(PRODUCTS)])
for c in range(1, len(hdr) + 1):
    s.cell(row=s.max_row, column=c).font = Font(bold=True)
    s.cell(row=s.max_row, column=c).border = BORDER

r = s.max_row + 2
s.cell(row=r, column=1, value="Working status (of all rows; N/A only where not implemented)").font = Font(bold=True)
r += 1
hdr = ["Working", *PRODUCTS]
header_row(s, hdr, first=False)
r = s.max_row + 1
for status in ("Yes", "Not tested", "No", "N/A"):
    s.append([status, *[working[p][status] for p in PRODUCTS]])
    for c in range(1, len(hdr) + 1):
        cell = s.cell(row=s.max_row, column=c)
        cell.border = BORDER
        cell.alignment = Alignment(horizontal="center")
        if status in FILL:
            cell.fill = FILL[status]

gap_start = s.max_row + 2
s.cell(row=gap_start, column=1, value="Gaps — features NOT implemented in at least one product").font = Font(bold=True)
gap_start += 1
hdr = ["ID", "Feature", "Laravel", "PHP", "Theme", "Node", "Gap note"]
header_row(s, hdr, first=False)
gap_start = s.max_row + 1
for idx, (module, feature, desc, lar, php, theme, node, notes) in enumerate(FEATURES, start=1):
    if any(v[0] == "No" for v in (lar, php, theme, node)):
        row = [idx, feature, lar[0], php[0], theme[0], node[0], notes]
        s.append(row)
        rr = s.max_row
        for c in range(1, 8):
            cell = s.cell(row=rr, column=c)
            cell.value = row[c - 1]
            cell.border = BORDER
            cell.alignment = Alignment(vertical="top", wrap_text=True)
            cell.font = Font(size=9)
            if c in (3, 4, 5, 6) and row[c - 1] in FILL:
                cell.fill = FILL[row[c - 1]]
for col, w in zip("ABCDEFG", [6, 46, 12, 12, 12, 12, 46]):
    s.column_dimensions[col].width = w

# ── Sheet 3: Legend ─────────────────────────────────────────────────────────
lg = wb.create_sheet("Legend")
lg["A1"] = "Eskoofy — products feature matrix: legend"
lg["A1"].font = Font(bold=True, size=13)


def add_key(text, mapping):
    lg.append([text])
    lg.cell(lg.max_row, 1).font = Font(bold=True)
    header_row(lg, ["Value", "Meaning"], first=False)
    for k, v in mapping.items():
        lg.append([k, v])
        lg.cell(lg.max_row, 1).fill = FILL[k if k in FILL else ("amber" if k == "Partial" else "grey")]
        lg.cell(lg.max_row, 2).alignment = Alignment(wrap_text=True)
        lg.cell(lg.max_row, 1).font = Font(size=10, bold=True)
        lg.cell(lg.max_row, 2).font = Font(size=10)
    lg.append([])


add_key("Implemented (per product)", IMPL)
add_key("Working (per product)", WORK)

lg.append(["How this sheet was built"])
lg.cell(lg.max_row, 1).font = Font(bold=True)
lg.append(["• Implemented = does the feature exist in that product's source (route/view/controller/service)?"])
lg.append(["• Working = is there repo evidence it was verified working (tests, smoke runs, e2e checks, parity gates)?"])
lg.append(["• 'Not tested' = implemented but no automated/live verification evidence (many integrations require live credentials)."])
lg.append(["• 'N/A' = not applicable — feature not implemented, so 'working' cannot apply."])
lg.append(["• Primary evidence: docs/parity/product-parity.md, eskoofy-nodejs-app/docs/PORTING-STATUS.md"])
lg.append(["  and NOT-IMPLEMENTED.md, per-product README/AGENTS/USER-MANUAL/SETUP-GUIDE, CI (.github/workflows/ci.yml)."])
lg.append(["• Regenerate with: python3 docs/feature-tracking/build-product-matrix.py"])
lg.append([])
lg.append(["Interpretation notes"])
lg.cell(lg.max_row, 1).font = Font(bold=True)
lg.append(["• 'No' means no implementation/evidence found in the repo — NOT the same as 'broken'."])
lg.append(["• 'Partial' = present in reduced form (e.g. generic CRUD without the business workflow)."])
lg.append(["• Laravel App is the reference implementation; other products are measured against it."])
for c in "ABCDEFG":
    lg.column_dimensions[c].width = 18
lg.column_dimensions["B"].width = 120

os.makedirs(OUT_DIR, exist_ok=True)
wb.save(OUT_PATH)
print("wrote", OUT_PATH)
print("feature rows:", len(FEATURES))

# sanity re-open
from openpyxl import load_workbook
wb2 = load_workbook(OUT_PATH)
print("sheets:", wb2.sheetnames)
print("matrix rows:", wb2["Feature Matrix"].max_row - 1)