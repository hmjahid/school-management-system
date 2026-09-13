<?php
declare(strict_types=1);

use App\Core\Router;

$router = new Router();

// Public routes
$router->get('/', 'App\\Controllers\\HomeController', 'index');
$router->get('/login', 'App\\Controllers\\AuthController', 'showLogin');
$router->post('/login', 'App\\Controllers\\AuthController', 'login');
$router->post('/logout', 'App\\Controllers\\AuthController', 'logout');
$router->get('/register', 'App\\Controllers\\AuthController', 'showRegister');
$router->post('/register', 'App\\Controllers\\AuthController', 'register');

// Public site pages
$router->get('/news', 'App\\Controllers\\SiteController', 'news');
$router->get('/news/{slug}', 'App\\Controllers\\SiteController', 'newsShow');
$router->get('/notices', 'App\\Controllers\\SiteController', 'notices');
$router->get('/events', 'App\\Controllers\\SiteController', 'events');
$router->get('/gallery', 'App\\Controllers\\SiteController', 'gallery');
$router->get('/contact', 'App\\Controllers\\SiteController', 'contact');
$router->post('/contact', 'App\\Controllers\\SiteController', 'submitContact');
$router->get('/results', 'App\\Controllers\\SiteController', 'results');
$router->get('/routine', 'App\\Controllers\\SiteController', 'routine');
$router->get('/admission', 'App\\Controllers\\SiteController', 'admission');
$router->post('/admission', 'App\\Controllers\\SiteController', 'submitAdmission');
$router->get('/payments', 'App\\Controllers\\SiteController', 'payments');
$router->get('/payments/status/{id}', 'App\\Controllers\\SiteController', 'paymentStatus');
$router->get('/payments/receipts/{id}', 'App\\Controllers\\SiteController', 'feeReceipt');
$router->get('/about', 'App\\Controllers\\SiteController', 'about');
$router->get('/academics', 'App\\Controllers\\SiteController', 'academics');
$router->get('/students-life', 'App\\Controllers\\SiteController', 'studentsLife');
$router->get('/faculty', 'App\\Controllers\\SiteController', 'faculty');
$router->get('/transport', 'App\\Controllers\\SiteController', 'transport');
$router->get('/committee', 'App\\Controllers\\SiteController', 'committee');
$router->get('/terms', 'App\\Controllers\\SiteController', 'terms');
$router->get('/privacy', 'App\\Controllers\\SiteController', 'privacy');
$router->get('/portal', 'App\\Controllers\\SiteController', 'portal');
$router->get('/search', 'App\\Controllers\\SiteController', 'search');
$router->get('/careers', 'App\\Controllers\\SiteController', 'careers');
$router->post('/careers/apply', 'App\\Controllers\\SiteController', 'applyCareer');

// Student / Guardian portal auth
$router->get('/student/login', 'App\\Controllers\\Auth\\StudentGuardianAuthController', 'showStudentLogin');
$router->post('/student/login', 'App\\Controllers\\Auth\\StudentGuardianAuthController', 'studentLogin');
$router->get('/student/logout', 'App\\Controllers\\Auth\\StudentGuardianAuthController', 'studentLogout');
$router->get('/guardian/login', 'App\\Controllers\\Auth\\StudentGuardianAuthController', 'showGuardianLogin');
$router->post('/guardian/login', 'App\\Controllers\\Auth\\StudentGuardianAuthController', 'guardianLogin');
$router->get('/guardian/logout', 'App\\Controllers\\Auth\\StudentGuardianAuthController', 'guardianLogout');

// Static files (no auth)
$router->get('/robots.txt', function () {
    header('Content-Type: text/plain');
    echo "User-agent: *\nDisallow: /dashboard/\nDisallow: /admin/\nAllow: /\n";
    exit;
});
$router->get('/manifest.json', function () {
    header('Content-Type: application/json');
    echo json_encode([
        'name'             => config('school.name', 'Eskoofy'),
        'short_name'       => 'Eskoofy',
        'start_url'        => '/',
        'display'          => 'standalone',
        'background_color' => '#ffffff',
        'theme_color'      => '#1d4ed8',
        'icons'            => [],
    ]);
    exit;
});

// Password reset
$router->get('/forgot-password', 'App\\Controllers\\PasswordResetController', 'showForm');
$router->post('/forgot-password', 'App\\Controllers\\PasswordResetController', 'sendToken');
$router->get('/reset-password', 'App\\Controllers\\PasswordResetController', 'showReset');
$router->post('/reset-password', 'App\\Controllers\\PasswordResetController', 'reset');

// Admission payment
$router->post('/admission/pay', 'App\\Controllers\\PaymentController', 'admissionPay');
$router->post('/admission/callback/{gateway}', 'App\\Controllers\\PaymentController', 'admissionCallback');
$router->post('/admission/webhook/{gateway}', 'App\\Controllers\\PaymentController', 'admissionWebhook');

// Sitemap
$router->get('/sitemap.xml', 'App\\Controllers\\SiteController', 'sitemap');

// Dashboard (authenticated)
$router->group('/dashboard', function (Router $r) {
    $r->get('', 'App\\Controllers\\Dashboard\\DashboardController', 'index');
    $r->get('/students', 'App\\Controllers\\Dashboard\\StudentController', 'index');
    $r->get('/students/create', 'App\\Controllers\\Dashboard\\StudentController', 'create');
    $r->post('/students', 'App\\Controllers\\Dashboard\\StudentController', 'store');
    $r->get('/students/promote', 'App\\Controllers\\Dashboard\\StudentController', 'promoteForm');
    $r->post('/students/promote', 'App\\Controllers\\Dashboard\\StudentController', 'promote');
    $r->get('/students/{id}', 'App\\Controllers\\Dashboard\\StudentController', 'show');
    $r->get('/students/{id}/edit', 'App\\Controllers\\Dashboard\\StudentController', 'edit');
    $r->put('/students/{id}', 'App\\Controllers\\Dashboard\\StudentController', 'update');
    $r->delete('/students/{id}', 'App\\Controllers\\Dashboard\\StudentController', 'destroy');
    $r->get('/students/{id}/attendance', 'App\\Controllers\\Dashboard\\StudentController', 'attendance');
    $r->get('/students/{id}/results', 'App\\Controllers\\Dashboard\\StudentController', 'results');
    $r->get('/students/{id}/fees', 'App\\Controllers\\Dashboard\\StudentController', 'fees');

    $r->get('/teachers', 'App\\Controllers\\Dashboard\\TeacherController', 'index');
    $r->get('/teachers/create', 'App\\Controllers\\Dashboard\\TeacherController', 'create');
    $r->post('/teachers', 'App\\Controllers\\Dashboard\\TeacherController', 'store');
    $r->get('/teachers/{id}', 'App\\Controllers\\Dashboard\\TeacherController', 'show');
    $r->get('/teachers/{id}/edit', 'App\\Controllers\\Dashboard\\TeacherController', 'edit');
    $r->put('/teachers/{id}', 'App\\Controllers\\Dashboard\\TeacherController', 'update');
    $r->delete('/teachers/{id}', 'App\\Controllers\\Dashboard\\TeacherController', 'destroy');

    $r->get('/classes', 'App\\Controllers\\Dashboard\\ClassController', 'index');
    $r->get('/classes/create', 'App\\Controllers\\Dashboard\\ClassController', 'create');
    $r->post('/classes', 'App\\Controllers\\Dashboard\\ClassController', 'store');
    $r->get('/classes/{id}', 'App\\Controllers\\Dashboard\\ClassController', 'show');
    $r->get('/classes/{id}/edit', 'App\\Controllers\\Dashboard\\ClassController', 'edit');
    $r->put('/classes/{id}', 'App\\Controllers\\Dashboard\\ClassController', 'update');
    $r->delete('/classes/{id}', 'App\\Controllers\\Dashboard\\ClassController', 'destroy');

    $r->get('/sections', 'App\\Controllers\\Dashboard\\SectionController', 'index');
    $r->post('/sections', 'App\\Controllers\\Dashboard\\SectionController', 'store');
    $r->put('/sections/{id}', 'App\\Controllers\\Dashboard\\SectionController', 'update');
    $r->delete('/sections/{id}', 'App\\Controllers\\Dashboard\\SectionController', 'destroy');

    $r->get('/subjects', 'App\\Controllers\\Dashboard\\SubjectController', 'index');
    $r->post('/subjects', 'App\\Controllers\\Dashboard\\SubjectController', 'store');
    $r->put('/subjects/{id}', 'App\\Controllers\\Dashboard\\SubjectController', 'update');
    $r->delete('/subjects/{id}', 'App\\Controllers\\Dashboard\\SubjectController', 'destroy');

    $r->get('/batches', 'App\\Controllers\\Dashboard\\BatchController', 'index');
    $r->post('/batches', 'App\\Controllers\\Dashboard\\BatchController', 'store');
    $r->put('/batches/{id}', 'App\\Controllers\\Dashboard\\BatchController', 'update');
    $r->delete('/batches/{id}', 'App\\Controllers\\Dashboard\\BatchController', 'destroy');

    $r->get('/guardians', 'App\\Controllers\\Dashboard\\GuardianController', 'index');
    $r->get('/guardians/create', 'App\\Controllers\\Dashboard\\GuardianController', 'create');
    $r->post('/guardians', 'App\\Controllers\\Dashboard\\GuardianController', 'store');
    $r->get('/guardians/{id}', 'App\\Controllers\\Dashboard\\GuardianController', 'show');
    $r->get('/guardians/{id}/edit', 'App\\Controllers\\Dashboard\\GuardianController', 'edit');
    $r->put('/guardians/{id}', 'App\\Controllers\\Dashboard\\GuardianController', 'update');
    $r->delete('/guardians/{id}', 'App\\Controllers\\Dashboard\\GuardianController', 'destroy');

    // Attendance
    $r->get('/attendance', 'App\\Controllers\\Dashboard\\AttendanceController', 'index');
    $r->post('/attendance', 'App\\Controllers\\Dashboard\\AttendanceController', 'store');
    $r->get('/attendance/mark', 'App\\Controllers\\Dashboard\\AttendanceController', 'mark');
    $r->get('/attendance/bulk', 'App\\Controllers\\Dashboard\\AttendanceController', 'bulk');
    $r->post('/attendance/bulk', 'App\\Controllers\\Dashboard\\AttendanceController', 'bulkStore');

    // Exams & Results
    $r->get('/exams', 'App\\Controllers\\Dashboard\\ExamController', 'index');
    $r->get('/exams/create', 'App\\Controllers\\Dashboard\\ExamController', 'create');
    $r->post('/exams', 'App\\Controllers\\Dashboard\\ExamController', 'store');
    $r->get('/exams/{id}', 'App\\Controllers\\Dashboard\\ExamController', 'show');
    $r->get('/exams/{id}/edit', 'App\\Controllers\\Dashboard\\ExamController', 'edit');
    $r->put('/exams/{id}', 'App\\Controllers\\Dashboard\\ExamController', 'update');
    $r->delete('/exams/{id}', 'App\\Controllers\\Dashboard\\ExamController', 'destroy');
    $r->post('/exams/{id}/publish', 'App\\Controllers\\Dashboard\\ExamController', 'publish');
    $r->get('/exams/{id}/results', 'App\\Controllers\\Dashboard\\ExamController', 'results');
    $r->post('/exams/{id}/results', 'App\\Controllers\\Dashboard\\ExamController', 'storeResults');
    $r->get('/exams/{id}/results/export', 'App\\Controllers\\Dashboard\\ExamController', 'exportResults');
    $r->get('/my-results', 'App\\Controllers\\Dashboard\\ExamController', 'myResults');
    $r->get('/students/{id}/results/export', 'App\\Controllers\\Dashboard\\ExamController', 'studentResultsExport');

    // Fees & Payments
    $r->get('/fees', 'App\\Controllers\\Dashboard\\FeeController', 'index');
    $r->get('/fees/create', 'App\\Controllers\\Dashboard\\FeeController', 'create');
    $r->post('/fees', 'App\\Controllers\\Dashboard\\FeeController', 'store');
    $r->put('/fees/{id}', 'App\\Controllers\\Dashboard\\FeeController', 'update');
    $r->delete('/fees/{id}', 'App\\Controllers\\Dashboard\\FeeController', 'destroy');
    $r->get('/fee-payments', 'App\\Controllers\\Dashboard\\FeePaymentController', 'index');
    $r->post('/fee-payments', 'App\\Controllers\\Dashboard\\FeePaymentController', 'store');
    $r->get('/fee-payments/{id}/receipt', 'App\\Controllers\\Dashboard\\FeePaymentController', 'receipt');

    // Payments
    $r->get('/payments', 'App\\Controllers\\Dashboard\\PaymentController', 'index');
    $r->get('/payments/{id}', 'App\\Controllers\\Dashboard\\PaymentController', 'show');
    $r->post('/payments/{id}/refund', 'App\\Controllers\\Dashboard\\PaymentController', 'refund');
    $r->get('/payment-gateways', 'App\\Controllers\\Dashboard\\PaymentGatewayController', 'index');
    $r->put('/payment-gateways/{id}', 'App\\Controllers\\Dashboard\\PaymentGatewayController', 'update');

    // Admissions
    $r->get('/admissions', 'App\\Controllers\\Dashboard\\AdmissionController', 'index');
    $r->post('/admissions/toggle', 'App\\Controllers\\Dashboard\\AdmissionController', 'toggleOpen');
    $r->get('/admissions/{id}', 'App\\Controllers\\Dashboard\\AdmissionController', 'show');
    $r->post('/admissions/{id}/approve', 'App\\Controllers\\Dashboard\\AdmissionController', 'approve');
    $r->post('/admissions/{id}/reject', 'App\\Controllers\\Dashboard\\AdmissionController', 'reject');
    $r->post('/admissions/{id}/enroll', 'App\\Controllers\\Dashboard\\AdmissionController', 'enroll');
    $r->post('/admissions/{id}/status', 'App\\Controllers\\Dashboard\\AdmissionController', 'updateStatus');
    $r->post('/admissions/{id}/verify-payment', 'App\\Controllers\\Dashboard\\AdmissionController', 'verifyPayment');

    // Routines
    $r->get('/routines', 'App\\Controllers\\Dashboard\\RoutineController', 'index');
    $r->post('/routines', 'App\\Controllers\\Dashboard\\RoutineController', 'store');
    $r->put('/routines/{id}', 'App\\Controllers\\Dashboard\\RoutineController', 'update');
    $r->delete('/routines/{id}', 'App\\Controllers\\Dashboard\\RoutineController', 'destroy');

    // Assignments
    $r->get('/assignments', 'App\\Controllers\\Dashboard\\AssignmentController', 'index');
    $r->get('/assignments/create', 'App\\Controllers\\Dashboard\\AssignmentController', 'create');
    $r->post('/assignments', 'App\\Controllers\\Dashboard\\AssignmentController', 'store');
    $r->get('/assignments/{id}', 'App\\Controllers\\Dashboard\\AssignmentController', 'show');
    $r->get('/assignments/{id}/edit', 'App\\Controllers\\Dashboard\\AssignmentController', 'edit');
    $r->put('/assignments/{id}', 'App\\Controllers\\Dashboard\\AssignmentController', 'update');
    $r->delete('/assignments/{id}', 'App\\Controllers\\Dashboard\\AssignmentController', 'destroy');
    $r->get('/assignments/{id}/submissions', 'App\\Controllers\\Dashboard\\AssignmentController', 'submissions');
    $r->post('/assignments/submissions/{submissionId}/grade', 'App\\Controllers\\Dashboard\\AssignmentController', 'grade');

    // Notices & Events
    $r->get('/notices', 'App\\Controllers\\Dashboard\\NoticeController', 'index');
    $r->post('/notices', 'App\\Controllers\\Dashboard\\NoticeController', 'store');
    $r->put('/notices/{id}', 'App\\Controllers\\Dashboard\\NoticeController', 'update');
    $r->delete('/notices/{id}', 'App\\Controllers\\Dashboard\\NoticeController', 'destroy');
    $r->post('/notices/bulk', 'App\\Controllers\\Dashboard\\NoticeController', 'bulk');
    $r->get('/news', 'App\\Controllers\\Dashboard\\NewsController', 'index');
    $r->get('/news/create', 'App\\Controllers\\Dashboard\\NewsController', 'create');
    $r->post('/news', 'App\\Controllers\\Dashboard\\NewsController', 'store');
    $r->get('/news/{id}/edit', 'App\\Controllers\\Dashboard\\NewsController', 'edit');
    $r->put('/news/{id}', 'App\\Controllers\\Dashboard\\NewsController', 'update');
    $r->delete('/news/{id}', 'App\\Controllers\\Dashboard\\NewsController', 'destroy');
    $r->post('/news/bulk', 'App\\Controllers\\Dashboard\\NewsController', 'bulk');
    $r->get('/events', 'App\\Controllers\\Dashboard\\EventController', 'index');
    $r->get('/events/calendar', 'App\\Controllers\\Dashboard\\EventController', 'calendar');
    $r->get('/events/create', 'App\\Controllers\\Dashboard\\EventController', 'create');
    $r->post('/events', 'App\\Controllers\\Dashboard\\EventController', 'store');
    $r->get('/events/{id}/edit', 'App\\Controllers\\Dashboard\\EventController', 'edit');
    $r->put('/events/{id}', 'App\\Controllers\\Dashboard\\EventController', 'update');
    $r->delete('/events/{id}', 'App\\Controllers\\Dashboard\\EventController', 'destroy');
    $r->get('/announcements', 'App\\Controllers\\Dashboard\\AnnouncementController', 'index');
    $r->post('/announcements', 'App\\Controllers\\Dashboard\\AnnouncementController', 'store');
    $r->put('/announcements/{id}', 'App\\Controllers\\Dashboard\\AnnouncementController', 'update');
    $r->delete('/announcements/{id}', 'App\\Controllers\\Dashboard\\AnnouncementController', 'destroy');
    $r->post('/announcements/bulk', 'App\\Controllers\\Dashboard\\AnnouncementController', 'bulk');
    $r->get('/gallery', 'App\\Controllers\\Dashboard\\GalleryController', 'index');
    $r->get('/gallery/create', 'App\\Controllers\\Dashboard\\GalleryController', 'create');
    $r->post('/gallery', 'App\\Controllers\\Dashboard\\GalleryController', 'store');
    $r->get('/gallery/{id}/edit', 'App\\Controllers\\Dashboard\\GalleryController', 'edit');
    $r->put('/gallery/{id}', 'App\\Controllers\\Dashboard\\GalleryController', 'update');
    $r->delete('/gallery/{id}', 'App\\Controllers\\Dashboard\\GalleryController', 'destroy');

    // Expenses
    $r->get('/expenses', 'App\\Controllers\\Dashboard\\ExpenseController', 'index');
    $r->post('/expenses', 'App\\Controllers\\Dashboard\\ExpenseController', 'store');
    $r->put('/expenses/{id}', 'App\\Controllers\\Dashboard\\ExpenseController', 'update');
    $r->delete('/expenses/{id}', 'App\\Controllers\\Dashboard\\ExpenseController', 'destroy');
    $r->get('/expense-categories', 'App\\Controllers\\Dashboard\\ExpenseCategoryController', 'index');
    $r->post('/expense-categories', 'App\\Controllers\\Dashboard\\ExpenseCategoryController', 'store');

    // Transport
    $r->get('/vehicles', 'App\\Controllers\\Dashboard\\VehicleController', 'index');
    $r->get('/vehicles/create', 'App\\Controllers\\Dashboard\\VehicleController', 'create');
    $r->post('/vehicles', 'App\\Controllers\\Dashboard\\VehicleController', 'store');
    $r->get('/vehicles/{id}/edit', 'App\\Controllers\\Dashboard\\VehicleController', 'edit');
    $r->put('/vehicles/{id}', 'App\\Controllers\\Dashboard\\VehicleController', 'update');
    $r->delete('/vehicles/{id}', 'App\\Controllers\\Dashboard\\VehicleController', 'destroy');
    $r->get('/transport-routes', 'App\\Controllers\\Dashboard\\TransportController', 'routes');
    $r->post('/transport-routes', 'App\\Controllers\\Dashboard\\TransportController', 'storeRoute');
    $r->get('/transport-routes/{id}/edit', 'App\\Controllers\\Dashboard\\TransportController', 'editRoute');
    $r->put('/transport-routes/{id}', 'App\\Controllers\\Dashboard\\TransportController', 'updateRoute');
    $r->delete('/transport-routes/{id}', 'App\\Controllers\\Dashboard\\TransportController', 'destroyRoute');
    $r->get('/transport-assignments', 'App\\Controllers\\Dashboard\\TransportController', 'assignments');
    $r->post('/transport-assignments', 'App\\Controllers\\Dashboard\\TransportController', 'storeAssignment');
    $r->delete('/transport-assignments/{id}', 'App\\Controllers\\Dashboard\\TransportController', 'destroyAssignment');

    // Hostel
    $r->get('/hostels', 'App\\Controllers\\Dashboard\\HostelController', 'index');
    $r->post('/hostels', 'App\\Controllers\\Dashboard\\HostelController', 'store');
    $r->get('/hostel-rooms', 'App\\Controllers\\Dashboard\\HostelController', 'rooms');
    $r->post('/hostel-rooms', 'App\\Controllers\\Dashboard\\HostelController', 'storeRoom');
    $r->post('/hostel-assignments', 'App\\Controllers\\Dashboard\\HostelController', 'storeAssignment');

    // Library
    $r->get('/books', 'App\\Controllers\\Dashboard\\LibraryController', 'index');
    $r->post('/books', 'App\\Controllers\\Dashboard\\LibraryController', 'store');
    $r->put('/books/{id}', 'App\\Controllers\\Dashboard\\LibraryController', 'update');
    $r->delete('/books/{id}', 'App\\Controllers\\Dashboard\\LibraryController', 'destroy');
    $r->get('/book-issues', 'App\\Controllers\\Dashboard\\LibraryController', 'issues');
    $r->post('/book-issues', 'App\\Controllers\\Dashboard\\LibraryController', 'issueBook');
    $r->get('/book-issues/{id}', 'App\\Controllers\\Dashboard\\LibraryController', 'showIssue');
    $r->post('/book-issues/{id}/return', 'App\\Controllers\\Dashboard\\LibraryController', 'returnBook');
    $r->post('/book-issues/{id}/fine', 'App\\Controllers\\Dashboard\\LibraryController', 'collectFine');
    $r->post('/book-issues/{id}/lost', 'App\\Controllers\\Dashboard\\LibraryController', 'markLost');

    // SMS
    $r->get('/sms', 'App\\Controllers\\Dashboard\\SmsController', 'index');
    $r->get('/sms/compose', 'App\\Controllers\\Dashboard\\SmsController', 'compose');
    $r->post('/sms/preview', 'App\\Controllers\\Dashboard\\SmsController', 'preview');
    $r->post('/sms/send', 'App\\Controllers\\Dashboard\\SmsController', 'send');
    $r->post('/sms/campaign', 'App\\Controllers\\Dashboard\\SmsController', 'sendCampaign');
    $r->get('/sms/templates', 'App\\Controllers\\Dashboard\\SmsController', 'templates');
    $r->get('/sms/due-reminder', 'App\\Controllers\\Dashboard\\SmsController', 'dueReminder');
    $r->post('/sms/due-reminder', 'App\\Controllers\\Dashboard\\SmsController', 'dueReminder');

    // Reports
    $r->get('/reports', 'App\\Controllers\\Dashboard\\ReportController', 'index');
    $r->get('/reports/students', 'App\\Controllers\\Dashboard\\ReportController', 'students');
    $r->get('/reports/fees', 'App\\Controllers\\Dashboard\\ReportController', 'fees');
    $r->get('/reports/attendance', 'App\\Controllers\\Dashboard\\ReportController', 'attendance');
    $r->get('/reports/exams', 'App\\Controllers\\Dashboard\\ReportController', 'exams');
    $r->get('/reports/builder', 'App\\Controllers\\Dashboard\\ReportBuilderController', 'index');
    $r->post('/reports/builder/export', 'App\\Controllers\\Dashboard\\ReportBuilderController', 'export');
    $r->get('/reports/export/{type}', 'App\\Controllers\\Dashboard\\ReportController', 'exportCsv');
    $r->get('/analytics', 'App\\Controllers\\Dashboard\\ReportController', 'analytics');

    // Documents (certificates, admit cards, ID cards)
    $r->get('/certificates', 'App\\Controllers\\Dashboard\\CertificateController', 'index');
    $r->get('/certificates/create', 'App\\Controllers\\Dashboard\\CertificateController', 'create');
    $r->post('/certificates', 'App\\Controllers\\Dashboard\\CertificateController', 'store');
    $r->post('/certificates/generate', 'App\\Controllers\\Dashboard\\CertificateController', 'generate');
    $r->get('/certificates/{id}', 'App\\Controllers\\Dashboard\\CertificateController', 'show');
    $r->get('/certificates/{id}/edit', 'App\\Controllers\\Dashboard\\CertificateController', 'edit');
    $r->put('/certificates/{id}', 'App\\Controllers\\Dashboard\\CertificateController', 'update');
    $r->delete('/certificates/{id}', 'App\\Controllers\\Dashboard\\CertificateController', 'destroy');
    $r->get('/certificates/{id}/print', 'App\\Controllers\\Dashboard\\CertificateController', 'print');
    $r->get('/admit-cards', 'App\\Controllers\\Dashboard\\AdmitCardController', 'index');
    $r->get('/admit-cards/create', 'App\\Controllers\\Dashboard\\AdmitCardController', 'create');
    $r->post('/admit-cards', 'App\\Controllers\\Dashboard\\AdmitCardController', 'store');
    $r->post('/admit-cards/generate', 'App\\Controllers\\Dashboard\\AdmitCardController', 'generate');
    $r->get('/admit-cards/batch/create', 'App\\Controllers\\Dashboard\\AdmitCardController', 'batchCreate');
    $r->post('/admit-cards/batch', 'App\\Controllers\\Dashboard\\AdmitCardController', 'batchStore');
    $r->get('/admit-cards/{id}', 'App\\Controllers\\Dashboard\\AdmitCardController', 'show');
    $r->get('/admit-cards/{id}/edit', 'App\\Controllers\\Dashboard\\AdmitCardController', 'edit');
    $r->put('/admit-cards/{id}', 'App\\Controllers\\Dashboard\\AdmitCardController', 'update');
    $r->delete('/admit-cards/{id}', 'App\\Controllers\\Dashboard\\AdmitCardController', 'destroy');
    $r->get('/admit-cards/{id}/print', 'App\\Controllers\\Dashboard\\AdmitCardController', 'print');
    $r->get('/admit-cards/{id}/preview', 'App\\Controllers\\Dashboard\\AdmitCardController', 'preview');
    $r->get('/id-cards', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'index');
    $r->get('/id-cards/create', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'create');
    $r->post('/id-cards', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'store');
    $r->post('/id-cards/generate', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'generate');
    $r->get('/id-cards/batch/create', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'batchCreate');
    $r->post('/id-cards/batch', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'batchStore');
    $r->get('/id-cards/{id}', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'show');
    $r->get('/id-cards/{id}/edit', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'edit');
    $r->put('/id-cards/{id}', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'update');
    $r->delete('/id-cards/{id}', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'destroy');
    $r->get('/id-cards/{id}/print', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'print');
    $r->get('/id-cards/{id}/preview', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'preview');

    // Settings
    $r->get('/settings', 'App\\Controllers\\Dashboard\\SettingController', 'index');
    $r->put('/settings/website', 'App\\Controllers\\Dashboard\\SettingController', 'updateWebsite');
    $r->put('/settings/school', 'App\\Controllers\\Dashboard\\SettingController', 'updateSchool');
    $r->put('/settings/locale', 'App\\Controllers\\Dashboard\\SettingController', 'updateLocale');
    $r->get('/users', 'App\\Controllers\\Dashboard\\UserController', 'index');
    $r->get('/users/create', 'App\\Controllers\\Dashboard\\UserController', 'create');
    $r->post('/users', 'App\\Controllers\\Dashboard\\UserController', 'store');
    $r->put('/users/{id}', 'App\\Controllers\\Dashboard\\UserController', 'update');
    $r->delete('/users/{id}', 'App\\Controllers\\Dashboard\\UserController', 'destroy');
    $r->get('/roles', 'App\\Controllers\\Dashboard\\RoleController', 'index');
    $r->post('/roles', 'App\\Controllers\\Dashboard\\RoleController', 'store');
    $r->put('/roles/{id}', 'App\\Controllers\\Dashboard\\RoleController', 'update');
    $r->delete('/roles/{id}', 'App\\Controllers\\Dashboard\\RoleController', 'destroy');
    $r->get('/profile', 'App\\Controllers\\Dashboard\\ProfileController', 'index');
    $r->put('/profile', 'App\\Controllers\\Dashboard\\ProfileController', 'update');
    $r->put('/profile/password', 'App\\Controllers\\Dashboard\\ProfileController', 'updatePassword');

    // Academic sessions
    $r->get('/academic-sessions', 'App\\Controllers\\Dashboard\\AcademicSessionController', 'index');
    $r->post('/academic-sessions', 'App\\Controllers\\Dashboard\\AcademicSessionController', 'store');
    $r->put('/academic-sessions/{id}', 'App\\Controllers\\Dashboard\\AcademicSessionController', 'update');
    $r->delete('/academic-sessions/{id}', 'App\\Controllers\\Dashboard\\AcademicSessionController', 'destroy');

    // Payroll
    $r->get('/salary-structures', 'App\\Controllers\\Dashboard\\PayrollController', 'salaryStructures');
    $r->post('/salary-structures', 'App\\Controllers\\Dashboard\\PayrollController', 'storeSalaryStructure');
    $r->get('/payslips', 'App\\Controllers\\Dashboard\\PayrollController', 'payslips');
    $r->post('/payslips', 'App\\Controllers\\Dashboard\\PayrollController', 'storePayslip');
    $r->get('/payroll/generate', 'App\\Controllers\\Dashboard\\PayrollController', 'generate');
    $r->post('/payroll/generate', 'App\\Controllers\\Dashboard\\PayrollController', 'generateStore');
    $r->get('/payslips/{id}', 'App\\Controllers\\Dashboard\\PayrollController', 'showPayslip');
    $r->post('/payslips/{id}/paid', 'App\\Controllers\\Dashboard\\PayrollController', 'markPaid');
    $r->get('/leave-requests', 'App\\Controllers\\Dashboard\\PayrollController', 'leaveRequests');
    $r->put('/leave-requests/{id}', 'App\\Controllers\\Dashboard\\PayrollController', 'updateLeaveRequest');
    $r->get('/leaves', 'App\\Controllers\\Dashboard\\LeaveController', 'index');
    $r->get('/leaves/create', 'App\\Controllers\\Dashboard\\LeaveController', 'create');
    $r->post('/leaves', 'App\\Controllers\\Dashboard\\LeaveController', 'store');
    $r->get('/leaves/{id}', 'App\\Controllers\\Dashboard\\LeaveController', 'show');
    $r->post('/leaves/{id}/approve', 'App\\Controllers\\Dashboard\\LeaveController', 'approve');
    $r->post('/leaves/{id}/reject', 'App\\Controllers\\Dashboard\\LeaveController', 'reject');
    $r->post('/leaves/{id}/cancel', 'App\\Controllers\\Dashboard\\LeaveController', 'cancel');
    $r->get('/staff-attendance', 'App\\Controllers\\Dashboard\\PayrollController', 'staffAttendance');
    $r->post('/staff-attendance', 'App\\Controllers\\Dashboard\\PayrollController', 'storeStaffAttendance');

    // Testimonials & Committee
    $r->get('/testimonials', 'App\\Controllers\\Dashboard\\TestimonialController', 'index');
    $r->get('/testimonials/create', 'App\\Controllers\\Dashboard\\TestimonialController', 'create');
    $r->post('/testimonials', 'App\\Controllers\\Dashboard\\TestimonialController', 'store');
    $r->get('/testimonials/{id}', 'App\\Controllers\\Dashboard\\TestimonialController', 'show');
    $r->get('/testimonials/{id}/edit', 'App\\Controllers\\Dashboard\\TestimonialController', 'edit');
    $r->put('/testimonials/{id}', 'App\\Controllers\\Dashboard\\TestimonialController', 'update');
    $r->delete('/testimonials/{id}', 'App\\Controllers\\Dashboard\\TestimonialController', 'destroy');
    $r->get('/testimonials/{id}/print', 'App\\Controllers\\Dashboard\\TestimonialController', 'print');
    $r->get('/committee', 'App\\Controllers\\Dashboard\\CommitteeController', 'index');
    $r->post('/committee', 'App\\Controllers\\Dashboard\\CommitteeController', 'store');
    $r->put('/committee/{id}', 'App\\Controllers\\Dashboard\\CommitteeController', 'update');
    $r->delete('/committee/{id}', 'App\\Controllers\\Dashboard\\CommitteeController', 'destroy');

    // Backup
    $r->post('/backup', 'App\\Controllers\\Dashboard\\BackupController', 'create');
    $r->get('/backups', 'App\\Controllers\\Dashboard\\BackupController', 'index');
    $r->get('/backups/download/{file}', 'App\\Controllers\\Dashboard\\BackupController', 'download');
    $r->delete('/backups/{file}', 'App\\Controllers\\Dashboard\\BackupController', 'destroy');

    // Messages
    $r->get('/messages', 'App\\Controllers\\Dashboard\\MessageController', 'index');
    $r->get('/messages/sent', 'App\\Controllers\\Dashboard\\MessageController', 'sent');
    $r->post('/messages', 'App\\Controllers\\Dashboard\\MessageController', 'store');
    $r->get('/messages/{id}', 'App\\Controllers\\Dashboard\\MessageController', 'show');
    $r->delete('/messages/{id}', 'App\\Controllers\\Dashboard\\MessageController', 'destroy');

    // Budgets
    $r->get('/budgets', 'App\\Controllers\\Dashboard\\BudgetController', 'index');
    $r->get('/budgets/create', 'App\\Controllers\\Dashboard\\BudgetController', 'create');
    $r->post('/budgets', 'App\\Controllers\\Dashboard\\BudgetController', 'store');
    $r->put('/budgets/{id}', 'App\\Controllers\\Dashboard\\BudgetController', 'update');
    $r->delete('/budgets/{id}', 'App\\Controllers\\Dashboard\\BudgetController', 'destroy');

    // Ledger
    $r->get('/ledger', 'App\\Controllers\\Dashboard\\LedgerController', 'index');
    $r->post('/ledger', 'App\\Controllers\\Dashboard\\LedgerController', 'store');
    $r->get('/ledger/journal', 'App\\Controllers\\Dashboard\\LedgerController', 'journal');
    $r->get('/ledger/cashbook', 'App\\Controllers\\Dashboard\\LedgerController', 'cashbook');
    $r->get('/ledger/bankbook', 'App\\Controllers\\Dashboard\\LedgerController', 'bankbook');
    $r->get('/ledger/income-statement', 'App\\Controllers\\Dashboard\\LedgerController', 'incomeStatement');
    $r->get('/ledger/balance-sheet', 'App\\Controllers\\Dashboard\\LedgerController', 'balanceSheet');

    // Leave types
    $r->get('/leave-types', 'App\\Controllers\\Dashboard\\PayrollController', 'leaveTypes');
    $r->post('/leave-types', 'App\\Controllers\\Dashboard\\PayrollController', 'storeLeaveType');
    $r->put('/leave-types/{id}', 'App\\Controllers\\Dashboard\\PayrollController', 'updateLeaveType');
    $r->delete('/leave-types/{id}', 'App\\Controllers\\Dashboard\\PayrollController', 'destroyLeaveType');

    // Visitor logs
    $r->get('/visitor-logs', 'App\\Controllers\\Dashboard\\VisitorLogController', 'index');

    // Activity log
    $r->get('/activity', 'App\\Controllers\\Dashboard\\ActivityController', 'index');

    // Refunds
    $r->get('/refunds', 'App\\Controllers\\Dashboard\\RefundController', 'index');
    $r->get('/refunds/{id}', 'App\\Controllers\\Dashboard\\RefundController', 'show');
    $r->post('/refunds/{id}/process', 'App\\Controllers\\Dashboard\\RefundController', 'process');
    $r->post('/refunds/{id}/cancel', 'App\\Controllers\\Dashboard\\RefundController', 'cancel');

    // Notifications
    $r->get('/notifications', 'App\\Controllers\\Dashboard\\NotificationController', 'index');
    $r->get('/notifications/preferences', 'App\\Controllers\\Dashboard\\NotificationController', 'preferences');
    $r->post('/notifications/preferences', 'App\\Controllers\\Dashboard\\NotificationController', 'updatePreferences');
    $r->post('/notifications/{id}/read', 'App\\Controllers\\Dashboard\\NotificationController', 'markRead');
    $r->post('/notifications/mark-all-read', 'App\\Controllers\\Dashboard\\NotificationController', 'markAllRead');

    // CMS
    $r->get('/cms', 'App\\Controllers\\Dashboard\\CmsController', 'index');
    $r->get('/cms/{id}/edit', 'App\\Controllers\\Dashboard\\CmsController', 'edit');
    $r->put('/cms/{id}', 'App\\Controllers\\Dashboard\\CmsController', 'update');

    // Global Search
    $r->get('/search', 'App\\Controllers\\Dashboard\\SearchController', 'search');

    // Contact Submissions
    $r->get('/contact-submissions', 'App\\Controllers\\Dashboard\\ContactSubmissionController', 'index');
    $r->post('/contact-submissions/{id}/read', 'App\\Controllers\\Dashboard\\ContactSubmissionController', 'markRead');
    $r->delete('/contact-submissions/{id}', 'App\\Controllers\\Dashboard\\ContactSubmissionController', 'destroy');
    $r->get('/contact-submissions/export', 'App\\Controllers\\Dashboard\\ContactSubmissionController', 'export');

    // Onboarding
    $r->get('/onboarding', 'App\\Controllers\\Dashboard\\OnboardingController', 'index');

    // Bulk Import/Export
    $r->get('/bulk', 'App\\Controllers\\Dashboard\\BulkController', 'index');
    $r->get('/bulk/export/{resource}', 'App\\Controllers\\Dashboard\\BulkController', 'export');
    $r->get('/bulk/import/{resource}', 'App\\Controllers\\Dashboard\\BulkController', 'import');
    $r->post('/bulk/import/{resource}', 'App\\Controllers\\Dashboard\\BulkController', 'importStore');

    // Documents
    $r->get('/documents', 'App\\Controllers\\Dashboard\\DocumentController', 'index');
    $r->get('/documents/create', 'App\\Controllers\\Dashboard\\DocumentController', 'create');
    $r->post('/documents', 'App\\Controllers\\Dashboard\\DocumentController', 'store');
    $r->delete('/documents/{id}', 'App\\Controllers\\Dashboard\\DocumentController', 'destroy');

    // Media
    $r->get('/media', 'App\\Controllers\\Dashboard\\MediaController', 'index');
    $r->post('/media', 'App\\Controllers\\Dashboard\\MediaController', 'store');
    $r->get('/media/{id}/download', 'App\\Controllers\\Dashboard\\MediaController', 'download');
    $r->delete('/media/{id}', 'App\\Controllers\\Dashboard\\MediaController', 'destroy');

    // Communications
    $r->get('/communications', 'App\\Controllers\\Dashboard\\CommunicationController', 'index');

    // Help
    $r->get('/help', 'App\\Controllers\\Dashboard\\HelpController', 'index');

    // Permissions
    $r->get('/permissions', 'App\\Controllers\\Dashboard\\PermissionController', 'index');

    // Bank Reconciliation
    $r->get('/bank-reconciliation', 'App\\Controllers\\Dashboard\\BankReconciliationController', 'index');
    $r->post('/bank-reconciliation/reconcile', 'App\\Controllers\\Dashboard\\BankReconciliationController', 'reconcile');

    // Progress Reports
    $r->get('/progress-reports', 'App\\Controllers\\Dashboard\\ProgressReportController', 'index');
    $r->get('/progress-reports/{studentId}/generate', 'App\\Controllers\\Dashboard\\ProgressReportController', 'generate');

    // Seat Plans
    $r->get('/seat-plans', 'App\\Controllers\\Dashboard\\SeatPlanController', 'index');
    $r->get('/seat-plans/{examId}/generate', 'App\\Controllers\\Dashboard\\SeatPlanController', 'generate');

    // Library Reports
    $r->get('/library-reports', 'App\\Controllers\\Dashboard\\LibraryReportController', 'index');
    $r->get('/library-reports/currently-issued', 'App\\Controllers\\Dashboard\\LibraryReportController', 'currentlyIssued');
    $r->get('/library-reports/overdue', 'App\\Controllers\\Dashboard\\LibraryReportController', 'overdue');
    $r->get('/library-reports/history', 'App\\Controllers\\Dashboard\\LibraryReportController', 'history');

    // Mark paid fee payments
    $r->post('/fee-payments/{id}/mark-paid', 'App\\Controllers\\Dashboard\\PaymentController', 'markPaid');

    // Cash Flow
    $r->get('/ledger/cash-flow', 'App\\Controllers\\Dashboard\\LedgerController', 'cashFlow');

    // Settings tabs
    $r->get('/settings/theme', 'App\\Controllers\\Dashboard\\SettingController', 'theme');
    $r->put('/settings/theme', 'App\\Controllers\\Dashboard\\SettingController', 'updateTheme');
    $r->get('/settings/payment', 'App\\Controllers\\Dashboard\\SettingController', 'payment');
    $r->put('/settings/payment', 'App\\Controllers\\Dashboard\\SettingController', 'updatePayment');
    $r->get('/settings/mail', 'App\\Controllers\\Dashboard\\SettingController', 'mail');
    $r->post('/settings/mail/test', 'App\\Controllers\\Dashboard\\SettingController', 'testMail');
    $r->get('/settings/library', 'App\\Controllers\\Dashboard\\SettingController', 'library');
    $r->put('/settings/library', 'App\\Controllers\\Dashboard\\SettingController', 'updateLibrary');
    $r->get('/settings/global-labels', 'App\\Controllers\\Dashboard\\SettingController', 'globalLabels');
    $r->put('/settings/global-labels', 'App\\Controllers\\Dashboard\\SettingController', 'updateGlobalLabels');
    $r->get('/settings/about', 'App\\Controllers\\Dashboard\\SettingController', 'about');
    $r->put('/settings/about', 'App\\Controllers\\Dashboard\\SettingController', 'updateAbout');

    // Careers admin
    $r->get('/careers', 'App\\Controllers\\Dashboard\\CareerController', 'index');
    $r->get('/careers/applications', 'App\\Controllers\\Dashboard\\CareerController', 'applications');
    $r->post('/careers/applications/{id}/status', 'App\\Controllers\\Dashboard\\CareerController', 'updateApplicationStatus');
    $r->delete('/careers/applications/{id}', 'App\\Controllers\\Dashboard\\CareerController', 'destroyApplication');

    // Staff Attendance
    $r->get('/staff-attendance', 'App\\Controllers\\Dashboard\\StaffAttendanceController', 'index');
    $r->post('/staff-attendance', 'App\\Controllers\\Dashboard\\StaffAttendanceController', 'store');
    $r->get('/staff-attendance/report', 'App\\Controllers\\Dashboard\\StaffAttendanceController', 'report');

    // About page
    $r->get('/about', 'App\\Controllers\\Dashboard\\DashboardController', 'about');

    // Staff list
    $r->get('/staff', 'App\\Controllers\\Dashboard\\TeacherController', 'staff');

    // Announcements (create/edit)
    $r->get('/announcements/create', 'App\\Controllers\\Dashboard\\AnnouncementController', 'create');
    $r->get('/announcements/{id}/edit', 'App\\Controllers\\Dashboard\\AnnouncementController', 'edit');

    // Attendance create
    $r->get('/attendance/create', 'App\\Controllers\\Dashboard\\AttendanceController', 'create');

    // Backup (singular path)
    $r->post('/backup/create', 'App\\Controllers\\Dashboard\\BackupController', 'create');
    $r->get('/backup/download/{file}', 'App\\Controllers\\Dashboard\\BackupController', 'download');
    $r->post('/backup/restore/{file}', 'App\\Controllers\\Dashboard\\BackupController', 'restore');
    $r->delete('/backup/{file}', 'App\\Controllers\\Dashboard\\BackupController', 'destroy');

    // Budgets edit
    $r->get('/budgets/{id}/edit', 'App\\Controllers\\Dashboard\\BudgetController', 'edit');

    // Careers show + status
    $r->get('/careers/{id}', 'App\\Controllers\\Dashboard\\CareerController', 'show');
    $r->post('/careers/{id}/status', 'App\\Controllers\\Dashboard\\CareerController', 'updateApplicationStatus');

    // CMS pages/edit
    $r->get('/cms/pages', 'App\\Controllers\\Dashboard\\CmsController', 'pages');
    $r->get('/cms/edit/{page}', 'App\\Controllers\\Dashboard\\CmsController', 'edit');
    $r->put('/cms/edit/{page}', 'App\\Controllers\\Dashboard\\CmsController', 'update');

    // Committee create/edit
    $r->get('/committee/create', 'App\\Controllers\\Dashboard\\CommitteeController', 'create');
    $r->get('/committee/{id}/edit', 'App\\Controllers\\Dashboard\\CommitteeController', 'edit');

    // Documents edit/update
    $r->get('/documents/{id}/edit', 'App\\Controllers\\Dashboard\\DocumentController', 'edit');
    $r->put('/documents/{id}', 'App\\Controllers\\Dashboard\\DocumentController', 'update');

    // Favorites toggle (no module)
    $r->post('/favorites/toggle', 'App\\Controllers\\Dashboard\\FavoriteController', 'toggle');

    // Notices create/edit
    $r->get('/notices/create', 'App\\Controllers\\Dashboard\\NoticeController', 'create');
    $r->get('/notices/{id}/edit', 'App\\Controllers\\Dashboard\\NoticeController', 'edit');

    // Notifications list/mark-all
    $r->get('/notifications/list', 'App\\Controllers\\Dashboard\\NotificationController', 'list');
    $r->post('/notifications/mark-all', 'App\\Controllers\\Dashboard\\NotificationController', 'markAllRead');

    // Roles create/edit
    $r->get('/roles/create', 'App\\Controllers\\Dashboard\\RoleController', 'create');
    $r->get('/roles/{id}/edit', 'App\\Controllers\\Dashboard\\RoleController', 'edit');

    // Users edit
    $r->get('/users/{id}/edit', 'App\\Controllers\\Dashboard\\UserController', 'edit');

    // Settings tabs (cms/general/localization)
    $r->get('/settings/cms', 'App\\Controllers\\Dashboard\\SettingController', 'cms');
    $r->put('/settings/cms', 'App\\Controllers\\Dashboard\\SettingController', 'updateCms');
    $r->get('/settings/general', 'App\\Controllers\\Dashboard\\SettingController', 'general');
    $r->put('/settings/general', 'App\\Controllers\\Dashboard\\SettingController', 'updateGeneral');
    $r->get('/settings/localization', 'App\\Controllers\\Dashboard\\SettingController', 'localization');
    $r->put('/settings/localization', 'App\\Controllers\\Dashboard\\SettingController', 'updateLocalization');

    // Locale switch (dashboard)
    $r->get('/locale/{locale}', 'App\\Controllers\\SiteController', 'dashboardSwitchLocale');

    // Media download/destroy
    $r->get('/media/{id}/download', 'App\\Controllers\\Dashboard\\MediaController', 'download');

    // Library module
    $r->get('/library/books', 'App\\Controllers\\Dashboard\\LibraryController', 'bookIndex');
    $r->post('/library/books', 'App\\Controllers\\Dashboard\\LibraryController', 'bookStore');
    $r->get('/library/books/create', 'App\\Controllers\\Dashboard\\LibraryController', 'bookCreate');
    $r->get('/library/books/{id}', 'App\\Controllers\\Dashboard\\LibraryController', 'bookShow');
    $r->get('/library/books/{id}/edit', 'App\\Controllers\\Dashboard\\LibraryController', 'bookEdit');
    $r->put('/library/books/{id}', 'App\\Controllers\\Dashboard\\LibraryController', 'bookUpdate');
    $r->delete('/library/books/{id}', 'App\\Controllers\\Dashboard\\LibraryController', 'bookDestroy');
    $r->get('/library/categories', 'App\\Controllers\\Dashboard\\LibraryController', 'categories');
    $r->post('/library/categories', 'App\\Controllers\\Dashboard\\LibraryController', 'categoryStore');
    $r->put('/library/categories/{id}', 'App\\Controllers\\Dashboard\\LibraryController', 'categoryUpdate');
    $r->delete('/library/categories/{id}', 'App\\Controllers\\Dashboard\\LibraryController', 'categoryDestroy');
    $r->get('/library/issues', 'App\\Controllers\\Dashboard\\LibraryController', 'issueIndex');
    $r->post('/library/issues', 'App\\Controllers\\Dashboard\\LibraryController', 'issueStore');
    $r->get('/library/issues/create', 'App\\Controllers\\Dashboard\\LibraryController', 'issueCreate');
    $r->get('/library/issues/{id}', 'App\\Controllers\\Dashboard\\LibraryController', 'issueShow');
    $r->delete('/library/issues/{id}', 'App\\Controllers\\Dashboard\\LibraryController', 'issueDestroy');
    $r->post('/library/issues/{id}/return', 'App\\Controllers\\Dashboard\\LibraryController', 'issueReturn');
    $r->post('/library/issues/{id}/fine', 'App\\Controllers\\Dashboard\\LibraryController', 'issueFine');
    $r->post('/library/issues/{id}/lost', 'App\\Controllers\\Dashboard\\LibraryController', 'issueLost');
    $r->get('/library/reports', 'App\\Controllers\\Dashboard\\LibraryReportController', 'index');
    $r->get('/library/reports/issued', 'App\\Controllers\\Dashboard\\LibraryReportController', 'issued');
    $r->get('/library/reports/overdue', 'App\\Controllers\\Dashboard\\LibraryReportController', 'overdue');
    $r->get('/library/reports/history', 'App\\Controllers\\Dashboard\\LibraryReportController', 'history');

    // Transport module
    $r->get('/transport/vehicles', 'App\\Controllers\\Dashboard\\TransportController', 'vehicles');
    $r->post('/transport/vehicles', 'App\\Controllers\\Dashboard\\TransportController', 'vehiclesStore');
    $r->get('/transport/vehicles/create', 'App\\Controllers\\Dashboard\\TransportController', 'vehiclesCreate');
    $r->get('/transport/vehicles/{id}/edit', 'App\\Controllers\\Dashboard\\TransportController', 'vehiclesEdit');
    $r->put('/transport/vehicles/{id}', 'App\\Controllers\\Dashboard\\TransportController', 'vehiclesUpdate');
    $r->delete('/transport/vehicles/{id}', 'App\\Controllers\\Dashboard\\TransportController', 'vehiclesDestroy');
    $r->get('/transport/routes', 'App\\Controllers\\Dashboard\\TransportController', 'transportRoutes');
    $r->post('/transport/routes', 'App\\Controllers\\Dashboard\\TransportController', 'transportRoutesStore');
    $r->get('/transport/routes/create', 'App\\Controllers\\Dashboard\\TransportController', 'transportRoutesCreate');
    $r->get('/transport/routes/{id}/edit', 'App\\Controllers\\Dashboard\\TransportController', 'transportRoutesEdit');
    $r->put('/transport/routes/{id}', 'App\\Controllers\\Dashboard\\TransportController', 'transportRoutesUpdate');
    $r->delete('/transport/routes/{id}', 'App\\Controllers\\Dashboard\\TransportController', 'transportRoutesDestroy');
    $r->get('/transport/assignments', 'App\\Controllers\\Dashboard\\TransportController', 'transportAssignments');
    $r->post('/transport/assignments', 'App\\Controllers\\Dashboard\\TransportController', 'transportAssignmentsStore');
    $r->delete('/transport/assignments/{id}', 'App\\Controllers\\Dashboard\\TransportController', 'transportAssignmentsDestroy');

    // Hostels
    $r->get('/hostels/create', 'App\\Controllers\\Dashboard\\HostelController', 'create');
    $r->get('/hostels/{id}', 'App\\Controllers\\Dashboard\\HostelController', 'show');
    $r->get('/hostels/{id}/edit', 'App\\Controllers\\Dashboard\\HostelController', 'edit');
    $r->put('/hostels/{id}', 'App\\Controllers\\Dashboard\\HostelController', 'update');
    $r->delete('/hostels/{id}', 'App\\Controllers\\Dashboard\\HostelController', 'destroy');
    $r->post('/hostels/{id}/rooms', 'App\\Controllers\\Dashboard\\HostelController', 'storeRoomForHostel');
    $r->put('/hostels/rooms/{id}', 'App\\Controllers\\Dashboard\\HostelController', 'updateRoom');
    $r->delete('/hostels/rooms/{id}', 'App\\Controllers\\Dashboard\\HostelController', 'destroyRoom');
    $r->post('/hostels/{id}/assignments', 'App\\Controllers\\Dashboard\\HostelController', 'storeAssignmentForHostel');
    $r->delete('/hostels/assignments/{id}', 'App\\Controllers\\Dashboard\\HostelController', 'destroyAssignment');

    // Student ID cards
    $r->get('/student-id-cards', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'idCardIndex');
    $r->post('/student-id-cards', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'idCardStore');
    $r->get('/student-id-cards/create', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'idCardCreate');
    $r->get('/student-id-cards/batch/create', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'idCardBatchCreate');
    $r->post('/student-id-cards/batch', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'idCardBatchStore');
    $r->get('/student-id-cards/{id}', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'idCardShow');
    $r->get('/student-id-cards/{id}/edit', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'idCardEdit');
    $r->put('/student-id-cards/{id}', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'idCardUpdate');
    $r->delete('/student-id-cards/{id}', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'idCardDestroy');
    $r->get('/student-id-cards/{id}/print', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'idCardPrint');
    $r->get('/student-id-cards/{id}/preview', 'App\\Controllers\\Dashboard\\StudentIdCardController', 'idCardPreview');

    // Parents (guardians)
    $r->get('/parents', 'App\\Controllers\\Dashboard\\GuardianController', 'parents');
    $r->post('/parents', 'App\\Controllers\\Dashboard\\GuardianController', 'parentsStore');
    $r->get('/parents/create', 'App\\Controllers\\Dashboard\\GuardianController', 'parentsCreate');
    $r->get('/parents/{id}', 'App\\Controllers\\Dashboard\\GuardianController', 'parentsShow');
    $r->get('/parents/{id}/edit', 'App\\Controllers\\Dashboard\\GuardianController', 'parentsEdit');
    $r->put('/parents/{id}', 'App\\Controllers\\Dashboard\\GuardianController', 'parentsUpdate');
    $r->delete('/parents/{id}', 'App\\Controllers\\Dashboard\\GuardianController', 'parentsDestroy');

    // Exams
    $r->post('/exams/{id}/unpublish', 'App\\Controllers\\Dashboard\\ExamController', 'unpublish');
    $r->post('/exams/{id}/visibility', 'App\\Controllers\\Dashboard\\ExamController', 'visibility');
    $r->get('/exams/{id}/results/{resultId}/marksheet', 'App\\Controllers\\Dashboard\\ExamController', 'marksheet');

    // Fees edit
    $r->get('/fees/{id}/edit', 'App\\Controllers\\Dashboard\\FeeController', 'edit');

    // Fee payments
    $r->get('/fee-payments/{id}', 'App\\Controllers\\Dashboard\\FeePaymentController', 'show');
    $r->post('/fee-payments/{id}/approve', 'App\\Controllers\\Dashboard\\FeePaymentController', 'approve');
    $r->post('/fee-payments/{id}/cancel', 'App\\Controllers\\Dashboard\\FeePaymentController', 'cancel');

    // Expenses
    $r->get('/expenses/create', 'App\\Controllers\\Dashboard\\ExpenseController', 'create');
    $r->get('/expenses/{id}/edit', 'App\\Controllers\\Dashboard\\ExpenseController', 'edit');
    $r->get('/expenses-export', 'App\\Controllers\\Dashboard\\ExpenseController', 'export');

    // Expense categories
    $r->get('/expense-categories/create', 'App\\Controllers\\Dashboard\\ExpenseCategoryController', 'create');
    $r->get('/expense-categories/{id}', 'App\\Controllers\\Dashboard\\ExpenseCategoryController', 'show');
    $r->get('/expense-categories/{id}/edit', 'App\\Controllers\\Dashboard\\ExpenseCategoryController', 'edit');
    $r->put('/expense-categories/{id}', 'App\\Controllers\\Dashboard\\ExpenseCategoryController', 'update');
    $r->delete('/expense-categories/{id}', 'App\\Controllers\\Dashboard\\ExpenseCategoryController', 'destroy');

    // Financial reports
    $r->get('/reports/balance-sheet', 'App\\Controllers\\Dashboard\\LedgerController', 'balanceSheet');
    $r->get('/reports/cash-flow', 'App\\Controllers\\Dashboard\\LedgerController', 'cashFlow');
    $r->get('/reports/income-statement', 'App\\Controllers\\Dashboard\\LedgerController', 'incomeStatement');

    // Payroll
    $r->get('/payroll/payslips', 'App\\Controllers\\Dashboard\\PayrollController', 'payslips');
    $r->get('/payroll/payslips/{id}', 'App\\Controllers\\Dashboard\\PayrollController', 'showPayslip');
    $r->post('/payroll/payslips/{id}/paid', 'App\\Controllers\\Dashboard\\PayrollController', 'markPaid');
    $r->get('/payroll/structures', 'App\\Controllers\\Dashboard\\PayrollController', 'salaryStructures');
    $r->post('/payroll/structures', 'App\\Controllers\\Dashboard\\PayrollController', 'storeSalaryStructure');

    // Certificates
    $r->get('/certificates/{id}', 'App\\Controllers\\Dashboard\\CertificateController', 'show');
    $r->get('/certificates/{id}/edit', 'App\\Controllers\\Dashboard\\CertificateController', 'edit');
    $r->put('/certificates/{id}', 'App\\Controllers\\Dashboard\\CertificateController', 'update');
    $r->delete('/certificates/{id}', 'App\\Controllers\\Dashboard\\CertificateController', 'destroy');
    $r->get('/certificates/{id}/print', 'App\\Controllers\\Dashboard\\CertificateController', 'print');

    // Admit cards
    $r->get('/admit-cards/{id}', 'App\\Controllers\\Dashboard\\AdmitCardController', 'show');
    $r->get('/admit-cards/{id}/edit', 'App\\Controllers\\Dashboard\\AdmitCardController', 'edit');
    $r->put('/admit-cards/{id}', 'App\\Controllers\\Dashboard\\AdmitCardController', 'update');
    $r->delete('/admit-cards/{id}', 'App\\Controllers\\Dashboard\\AdmitCardController', 'destroy');
    $r->get('/admit-cards/{id}/print', 'App\\Controllers\\Dashboard\\AdmitCardController', 'print');
    $r->get('/admit-cards/{id}/preview', 'App\\Controllers\\Dashboard\\AdmitCardController', 'preview');

    // Testimonials
    $r->get('/testimonials/{id}', 'App\\Controllers\\Dashboard\\TestimonialController', 'show');
    $r->get('/testimonials/{id}/edit', 'App\\Controllers\\Dashboard\\TestimonialController', 'edit');
    $r->put('/testimonials/{id}', 'App\\Controllers\\Dashboard\\TestimonialController', 'update');
    $r->delete('/testimonials/{id}', 'App\\Controllers\\Dashboard\\TestimonialController', 'destroy');
    $r->get('/testimonials/{id}/print', 'App\\Controllers\\Dashboard\\TestimonialController', 'print');

    // Routines
    $r->get('/routines/create', 'App\\Controllers\\Dashboard\\RoutineController', 'create');
    $r->get('/routines/{id}/edit', 'App\\Controllers\\Dashboard\\RoutineController', 'edit');

    // Seat plans
    $r->get('/seat-plans/{examId}/generate', 'App\\Controllers\\Dashboard\\SeatPlanController', 'generate');

    // Progress reports
    $r->get('/progress-reports/{studentId}/generate', 'App\\Controllers\\Dashboard\\ProgressReportController', 'generate');

    // Leaves
    $r->get('/leaves/{id}', 'App\\Controllers\\Dashboard\\LeaveController', 'show');
    $r->post('/leaves/{id}/approve', 'App\\Controllers\\Dashboard\\LeaveController', 'approve');
    $r->post('/leaves/{id}/reject', 'App\\Controllers\\Dashboard\\LeaveController', 'reject');
    $r->post('/leaves/{id}/cancel', 'App\\Controllers\\Dashboard\\LeaveController', 'cancel');

    // Admissions tests
    // URL params are named to match the controller signatures (dispatch maps
    // named groups -> method arguments).
    $r->post('/admissions/{admissionId}/tests', 'App\\Controllers\\Dashboard\\AdmissionController', 'scheduleTest');
    $r->put('/admissions/{admissionId}/tests/{testId}', 'App\\Controllers\\Dashboard\\AdmissionController', 'updateTest');
    $r->delete('/admissions/{admissionId}/tests/{testId}', 'App\\Controllers\\Dashboard\\AdmissionController', 'deleteTest');
}, ['AuthMiddleware']);

// ---------------------------------------------------------------------------
// Public site additions (parity with eskoofy-app)
// ---------------------------------------------------------------------------

// Public admissions flow
$router->get('/admissions', 'App\\Controllers\\SiteController', 'admissions');
$router->get('/admissions/apply', 'App\\Controllers\\SiteController', 'apply');
$router->post('/admissions/apply', 'App\\Controllers\\SiteController', 'applyStore');
$router->post('/admissions/scholarship', 'App\\Controllers\\SiteController', 'submitScholarship');
$router->get('/admissions/status', 'App\\Controllers\\SiteController', 'admissionStatus');
$router->get('/admissions/{id}/approval-letter', 'App\\Controllers\\SiteController', 'admissionApprovalLetter');
$router->get('/admissions/{id}/receipt', 'App\\Controllers\\SiteController', 'admissionReceipt');
$router->post('/admissions/{id}/submit-payment', 'App\\Controllers\\SiteController', 'submitPayment');

// Contact forms
$router->post('/contact/complaint', 'App\\Controllers\\SiteController', 'submitComplaint');
$router->post('/contact/feedback', 'App\\Controllers\\SiteController', 'submitFeedback');
$router->post('/newsletter', 'App\\Controllers\\SiteController', 'newsletterStore');

// Payments
$router->get('/payments/initiate', 'App\\Controllers\\PaymentController', 'initiate');

// Results download
$router->get('/results/download', 'App\\Controllers\\SiteController', 'resultsDownload');

// Students page
$router->get('/students', 'App\\Controllers\\SiteController', 'students');

// Portal
$router->get('/portal/admission', 'App\\Controllers\\SiteController', 'portalAdmission');
$router->get('/portal/progress', 'App\\Controllers\\SiteController', 'portalProgress');
$router->post('/portal/message', 'App\\Controllers\\SiteController', 'messageTeacher');
$router->get('/portal/register', 'App\\Controllers\\SiteController', 'portalRegister');

// Student / Guardian dashboards
$router->get('/student/dashboard', 'App\\Controllers\\Auth\\StudentGuardianAuthController', 'studentDashboard');
$router->get('/guardian/dashboard', 'App\\Controllers\\Auth\\StudentGuardianAuthController', 'guardianDashboard');
$router->post('/guardian/assignments/{submission}/notes', 'App\\Controllers\\Auth\\StudentGuardianAuthController', 'guardianNotes');

// Profile
$router->get('/profile', 'App\\Controllers\\SiteController', 'profileEdit');
$router->post('/profile', 'App\\Controllers\\SiteController', 'profileUpdate');

// Locale switch (site)
$router->get('/locale/{locale}', 'App\\Controllers\\SiteController', 'switchLocale');

// Messages (public inbox)
$router->get('/messages', 'App\\Controllers\\Dashboard\\MessageController', 'index');
$router->get('/messages/create', 'App\\Controllers\\Dashboard\\MessageController', 'create');
$router->post('/messages', 'App\\Controllers\\Dashboard\\MessageController', 'store');
$router->get('/messages/sent', 'App\\Controllers\\Dashboard\\MessageController', 'sent');
$router->get('/messages/{id}', 'App\\Controllers\\Dashboard\\MessageController', 'show');
$router->delete('/messages/{id}', 'App\\Controllers\\Dashboard\\MessageController', 'destroy');

// Password reset with token in URL
$router->get('/reset-password/{token}', 'App\\Controllers\\PasswordResetController', 'showReset');

// Sanctum/storage stubs (raw-PHP equivalents; Laravel-only endpoints)
$router->get('/sanctum/csrf-cookie', function () {
    http_response_code(204);
    exit;
});
$router->get('/storage/{path}', function () {
    http_response_code(404);
    exit;
});
