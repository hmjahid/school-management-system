<?php
declare(strict_types=1);

// Public API routes
$router->get('/api/v1', function () {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Eskoofy API v1']);
    exit;
});

$router->get('/api/v1/results/lookup', 'App\\Controllers\\Api\\ResultsController', 'lookup');
$router->get('/api/v1/news', 'App\\Controllers\\Api\\NewsController', 'index');
$router->get('/api/v1/notices', 'App\\Controllers\\Api\\NoticeController', 'index');
$router->get('/api/v1/events', 'App\\Controllers\\Api\\EventController', 'index');

// Auth (public issue/refresh, protected me/logout)
$router->post('/api/v1/auth/login', 'App\\Controllers\\Api\\AuthController', 'login');
$router->post('/api/v1/auth/register', 'App\\Controllers\\Api\\AuthController', 'register');
$router->post('/api/v1/auth/refresh-token', 'App\\Controllers\\Api\\AuthController', 'refreshToken');
$router->group('/api/v1/auth', function (App\Core\Router $r) {
    $r->post('/logout', 'App\\Controllers\\Api\\AuthController', 'logout');
}, ['ApiTokenMiddleware', 'ForceJsonMiddleware']);
$router->group('/api/v1', function (App\Core\Router $r) {
    $r->get('/user', 'App\\Controllers\\Api\\AuthController', 'me');
    $r->get('/me', 'App\\Controllers\\Api\\AuthController', 'me');
}, ['ApiTokenMiddleware', 'ForceJsonMiddleware']);

// Public academic & content routes (parity with app routes/api.php)
$router->get('/api/v1/academics/curriculum', 'App\\Controllers\\Api\\AcademicController', 'getCurriculum');
$router->get('/api/v1/academics/programs', 'App\\Controllers\\Api\\AcademicController', 'getPrograms');
$router->get('/api/v1/academics/faculty', 'App\\Controllers\\Api\\AcademicController', 'getFaculty');
$router->get('/api/v1/academics/results/filters', 'App\\Controllers\\Api\\AcademicController', 'resultFilters');
$router->get('/api/v1/academics/results/lookup', 'App\\Controllers\\Api\\ResultsController', 'lookup');

$router->get('/api/v1/news/categories', 'App\\Controllers\\Api\\NewsController', 'categories');
$router->get('/api/v1/news/upcoming-events', 'App\\Controllers\\Api\\NewsController', 'upcomingEvents');
$router->get('/api/v1/news/{id}', 'App\\Controllers\\Api\\NewsController', 'show');

$router->get('/api/v1/website-content/pages', 'App\\Controllers\\Api\\WebsiteContentController', 'getActivePages');
$router->get('/api/v1/website-content/{page}', 'App\\Controllers\\Api\\WebsiteContentController', 'getPageContent');
$router->get('/api/v1/website/gallery', 'App\\Controllers\\Api\\GalleryController', 'index');
$router->get('/api/v1/website/gallery/categories', 'App\\Controllers\\Api\\GalleryController', 'categories');

$router->get('/api/v1/careers', 'App\\Controllers\\Api\\CareerController', 'index');
$router->get('/api/v1/careers/{id}', 'App\\Controllers\\Api\\CareerController', 'show');
$router->post('/api/v1/careers/apply', 'App\\Controllers\\Api\\CareerController', 'apply');

$router->get('/api/v1/terms', 'App\\Controllers\\Api\\LegalController', 'getTerms');
$router->get('/api/v1/privacy', 'App\\Controllers\\Api\\LegalController', 'getPrivacy');
$router->get('/api/v1/sitemap', 'App\\Controllers\\Api\\LegalController', 'getSitemap');
$router->get('/api/v1/home', 'App\\Controllers\\Api\\LegalController', 'getHome');

$router->get('/api/v1/events/{id}', 'App\\Controllers\\Api\\EventController', 'show');

// Teacher portal (token auth)
$router->group('/api/v1/teacher', function (App\Core\Router $r) {
    $r->get('/classes', 'App\\Controllers\\Api\\TeacherController', 'getTeacherClasses');
    $r->get('/classes/{classId}/students', 'App\\Controllers\\Api\\TeacherController', 'getClassStudents');
    $r->get('/classes/{classId}/grades', 'App\\Controllers\\Api\\TeacherController', 'getClassGrades');
}, ['ApiTokenMiddleware', 'ForceJsonMiddleware']);

// Search (token auth)
$router->group('/api/v1/search', function (App\Core\Router $r) {
    $r->get('/', 'App\\Controllers\\Api\\SearchController', 'search');
    $r->get('/{resource}', 'App\\Controllers\\Api\\SearchController', 'searchResource');
}, ['ApiTokenMiddleware', 'ForceJsonMiddleware']);

// Fee + fee-payments sub-routes (token auth; admin writes gated in controller)
$router->group('/api/v1/fees', function (App\Core\Router $r) {
    $r->get('/types', 'App\\Controllers\\Api\\FeeController', 'getFeeTypes');
    $r->get('/statistics', 'App\\Controllers\\Api\\FeeController', 'getStatistics');
    $r->get('/{fee}/payments', 'App\\Controllers\\Api\\FeeController', 'feePayments');
    $r->post('/{fee}/payments', 'App\\Controllers\\Api\\FeePaymentController', 'store');
}, ['ApiTokenMiddleware', 'ForceJsonMiddleware']);
$router->group('/api/v1/fees/payments', function (App\Core\Router $r) {
    $r->get('/statuses', 'App\\Controllers\\Api\\FeePaymentController', 'getStatuses');
    $r->get('/methods', 'App\\Controllers\\Api\\FeePaymentController', 'getPaymentMethods');
    $r->get('/{payment}', 'App\\Controllers\\Api\\FeePaymentController', 'show');
    $r->put('/{payment}', 'App\\Controllers\\Api\\FeePaymentController', 'update');
    $r->post('/{payment}/approve', 'App\\Controllers\\Api\\FeePaymentController', 'approve');
    $r->post('/{payment}/cancel', 'App\\Controllers\\Api\\FeePaymentController', 'cancel');
}, ['ApiTokenMiddleware', 'ForceJsonMiddleware']);

// Website settings (public)
$router->get('/api/v1/website-settings', 'App\\Controllers\\Api\\WebsiteSettingController', 'publicSettings');

// Admin API (token auth + role gate in controller)
$router->group('/api/v1/admin', function (App\Core\Router $r) {
    $r->get('/dashboard', 'App\\Controllers\\Api\\AdminController', 'dashboard');
    $r->get('/analytics/overview', 'App\\Controllers\\Api\\AdminController', 'analyticsOverview');
    $r->get('/activity', 'App\\Controllers\\Api\\AdminController', 'activity');
    $r->post('/quick-actions', 'App\\Controllers\\Api\\AdminController', 'quickAction');

    $r->get('/cms/pages', 'App\\Controllers\\Api\\CmsController', 'pages');
    $r->post('/cms/pages', 'App\\Controllers\\Api\\CmsController', 'storePage');
    $r->get('/cms/pages/{page}', 'App\\Controllers\\Api\\CmsController', 'showPage');
    $r->put('/cms/pages/{page}', 'App\\Controllers\\Api\\CmsController', 'updatePage');
    $r->delete('/cms/pages/{page}', 'App\\Controllers\\Api\\CmsController', 'destroyPage');

    $r->get('/cms/media', 'App\\Controllers\\Api\\CmsController', 'media');
    $r->post('/cms/media', 'App\\Controllers\\Api\\CmsController', 'uploadMedia');
    $r->delete('/cms/media/{media}', 'App\\Controllers\\Api\\CmsController', 'destroyMedia');

    $r->get('/cms/menus', 'App\\Controllers\\Api\\CmsController', 'menus');
    $r->put('/cms/menus', 'App\\Controllers\\Api\\CmsController', 'updateMenus');

    $r->get('/cms/settings', 'App\\Controllers\\Api\\CmsController', 'settings');
    $r->put('/cms/settings', 'App\\Controllers\\Api\\CmsController', 'updateSettings');

    $r->get('/cms/header', 'App\\Controllers\\Api\\CmsController', 'header');
    $r->put('/cms/header', 'App\\Controllers\\Api\\CmsController', 'updateHeader');
    $r->get('/cms/footer', 'App\\Controllers\\Api\\CmsController', 'footer');
    $r->put('/cms/footer', 'App\\Controllers\\Api\\CmsController', 'updateFooter');

    $r->get('/cms/blocks', 'App\\Controllers\\Api\\CmsController', 'contentBlocks');
    $r->post('/cms/blocks', 'App\\Controllers\\Api\\CmsController', 'storeContentBlock');
    $r->get('/cms/blocks/{block}', 'App\\Controllers\\Api\\CmsController', 'showContentBlock');
    $r->put('/cms/blocks/{block}', 'App\\Controllers\\Api\\CmsController', 'updateContentBlock');
    $r->delete('/cms/blocks/{block}', 'App\\Controllers\\Api\\CmsController', 'destroyContentBlock');

    $r->put('/website-content/{page}', 'App\\Controllers\\Api\\WebsiteContentController', 'updatePageContent');
    $r->post('/website-content/{page}/upload-image', 'App\\Controllers\\Api\\WebsiteContentController', 'uploadImage');

    $r->get('/widgets', 'App\\Controllers\\Api\\AdminController', 'getWidgetConfig');
    $r->post('/widgets', 'App\\Controllers\\Api\\AdminController', 'saveWidgetConfig');
    $r->post('/widgets/reset', 'App\\Controllers\\Api\\AdminController', 'resetWidgetConfig');

    $r->get('/website-settings', 'App\\Controllers\\Api\\WebsiteSettingController', 'index');
    $r->post('/website-settings', 'App\\Controllers\\Api\\WebsiteSettingController', 'update');
}, ['ApiTokenMiddleware', 'ForceJsonMiddleware']);

// Public refund webhook (outside v1 — gateways post here directly)
$router->post('/api/webhooks/{gateway}/refund', 'App\\Controllers\\Api\\PaymentController', 'refundWebhook');

// Protected API routes
$router->group('/api/v1', function (App\Core\Router $r) {
    $r->get('/dashboard', 'App\\Controllers\\Api\\DashboardController', 'index');

    $r->get('/students', 'App\\Controllers\\Api\\StudentController', 'index');
    $r->post('/students', 'App\\Controllers\\Api\\StudentController', 'store');
    $r->get('/students/{id}', 'App\\Controllers\\Api\\StudentController', 'show');
    $r->put('/students/{id}', 'App\\Controllers\\Api\\StudentController', 'update');
    $r->delete('/students/{id}', 'App\\Controllers\\Api\\StudentController', 'destroy');

    $r->get('/teachers', 'App\\Controllers\\Api\\TeacherController', 'index');
    $r->post('/teachers', 'App\\Controllers\\Api\\TeacherController', 'store');
    $r->get('/teachers/{id}', 'App\\Controllers\\Api\\TeacherController', 'show');
    $r->put('/teachers/{id}', 'App\\Controllers\\Api\\TeacherController', 'update');
    $r->delete('/teachers/{id}', 'App\\Controllers\\Api\\TeacherController', 'destroy');

    $r->get('/classes', 'App\\Controllers\\Api\\ClassController', 'index');
    $r->post('/classes', 'App\\Controllers\\Api\\ClassController', 'store');
    $r->get('/classes/{id}', 'App\\Controllers\\Api\\ClassController', 'show');
    $r->put('/classes/{id}', 'App\\Controllers\\Api\\ClassController', 'update');
    $r->delete('/classes/{id}', 'App\\Controllers\\Api\\ClassController', 'destroy');

    $r->get('/exams', 'App\\Controllers\\Api\\ExamController', 'index');
    $r->post('/exams', 'App\\Controllers\\Api\\ExamController', 'store');
    $r->get('/exams/{id}', 'App\\Controllers\\Api\\ExamController', 'show');
    $r->put('/exams/{id}', 'App\\Controllers\\Api\\ExamController', 'update');
    $r->delete('/exams/{id}', 'App\\Controllers\\Api\\ExamController', 'destroy');

    $r->get('/fees', 'App\\Controllers\\Api\\FeeController', 'index');
    $r->post('/fees', 'App\\Controllers\\Api\\FeeController', 'store');
    $r->get('/fees/{id}', 'App\\Controllers\\Api\\FeeController', 'show');
    $r->put('/fees/{id}', 'App\\Controllers\\Api\\FeeController', 'update');
    $r->delete('/fees/{id}', 'App\\Controllers\\Api\\FeeController', 'destroy');

    $r->get('/admissions', 'App\\Controllers\\Api\\AdmissionController', 'index');
    $r->post('/admissions', 'App\\Controllers\\Api\\AdmissionController', 'store');
    $r->get('/admissions/{id}', 'App\\Controllers\\Api\\AdmissionController', 'show');
    $r->put('/admissions/{id}', 'App\\Controllers\\Api\\AdmissionController', 'update');
    $r->delete('/admissions/{id}', 'App\\Controllers\\Api\\AdmissionController', 'destroy');
}, ['AuthMiddleware', 'ForceJsonMiddleware']);

// ---------------------------------------------------------------------------
// API v1 additions (parity with eskoofy-app)
// ---------------------------------------------------------------------------

$router->get('/api/v1/admission-filters', 'App\\Controllers\\Api\\AdmissionController', 'filterOptions');
$router->get('/api/v1/export/admissions', 'App\\Controllers\\Api\\AdmissionController', 'export');
$router->post('/api/v1/import/admissions', 'App\\Controllers\\Api\\AdmissionController', 'import');
$router->get('/api/v1/admissions/status/{reference}', 'App\\Controllers\\Api\\AdmissionController', 'status');

$router->group('/api/v1', function (App\Core\Router $r) {
    $r->post('/admissions/{id}/approve', 'App\\Controllers\\Api\\AdmissionController', 'approve');
    $r->post('/admissions/{id}/reject', 'App\\Controllers\\Api\\AdmissionController', 'reject');
    $r->post('/admissions/{id}/enroll', 'App\\Controllers\\Api\\AdmissionController', 'enroll');
    $r->post('/admissions/{id}/submit', 'App\\Controllers\\Api\\AdmissionController', 'submit');
    $r->post('/admissions/{id}/documents', 'App\\Controllers\\Api\\AdmissionController', 'uploadDocument');
    $r->get('/admissions/{id}/documents/{documentId}', 'App\\Controllers\\Api\\AdmissionController', 'viewDocument');
    $r->delete('/admissions/{id}/documents/{documentId}', 'App\\Controllers\\Api\\AdmissionController', 'deleteDocument');
}, ['AuthMiddleware', 'ForceJsonMiddleware']);

// Payments (public + protected)
$router->get('/api/v1/payments/gateways', 'App\\Controllers\\Api\\PaymentController', 'gateways');
$router->post('/api/v1/payments/webhook/{gateway}', 'App\\Controllers\\Api\\PaymentController', 'webhook');
$router->post('/api/v1/payments/callback/{gateway}', 'App\\Controllers\\Api\\PaymentController', 'callback');

$router->group('/api/v1', function (App\Core\Router $r) {
    $r->get('/payments', 'App\\Controllers\\Api\\PaymentController', 'index');
    $r->post('/payments/initiate', 'App\\Controllers\\Api\\PaymentController', 'initiate');
    $r->post('/payments/record-offline', 'App\\Controllers\\Api\\PaymentController', 'recordOffline');
    $r->get('/payments/export', 'App\\Controllers\\Api\\PaymentController', 'export');
    $r->get('/payments/{id}', 'App\\Controllers\\Api\\PaymentController', 'show');
    $r->get('/payments/status/{id}', 'App\\Controllers\\Api\\PaymentController', 'status');
    $r->put('/payments/{id}/status', 'App\\Controllers\\Api\\PaymentController', 'updateStatus');
    $r->post('/payments/{id}/refunds', 'App\\Controllers\\Api\\RefundController', 'store');
}, ['AuthMiddleware', 'ForceJsonMiddleware']);

// Refunds
$router->group('/api/v1', function (App\Core\Router $r) {
    $r->get('/refunds', 'App\\Controllers\\Api\\RefundController', 'index');
    $r->get('/refunds/statistics', 'App\\Controllers\\Api\\RefundController', 'statistics');
    $r->get('/refunds/{id}', 'App\\Controllers\\Api\\RefundController', 'show');
    $r->post('/refunds/{id}/process', 'App\\Controllers\\Api\\RefundController', 'process');
    $r->post('/refunds/{id}/cancel', 'App\\Controllers\\Api\\RefundController', 'cancel');
}, ['AuthMiddleware', 'ForceJsonMiddleware']);

// Payment gateways
$router->group('/api/v1', function (App\Core\Router $r) {
    $r->get('/payment-gateways', 'App\\Controllers\\Api\\PaymentGatewayController', 'index');
    $r->post('/payment-gateways', 'App\\Controllers\\Api\\PaymentGatewayController', 'store');
    $r->get('/payment-gateways/{id}', 'App\\Controllers\\Api\\PaymentGatewayController', 'show');
    $r->put('/payment-gateways/{id}', 'App\\Controllers\\Api\\PaymentGatewayController', 'update');
    $r->delete('/payment-gateways/{id}', 'App\\Controllers\\Api\\PaymentGatewayController', 'destroy');
}, ['AuthMiddleware', 'ForceJsonMiddleware']);

// Students API (extended)
$router->group('/api/v1', function (App\Core\Router $r) {
    $r->get('/students/{id}/edit', 'App\\Controllers\\Api\\StudentController', 'edit');
    $r->get('/students/{id}/attendance', 'App\\Controllers\\Api\\StudentController', 'attendance');
    $r->get('/students/{id}/fees', 'App\\Controllers\\Api\\StudentController', 'fees');
    $r->get('/students/{id}/results', 'App\\Controllers\\Api\\StudentController', 'results');
}, ['AuthMiddleware', 'ForceJsonMiddleware']);

// Notifications API (parity with eskoofy-app routes/notifications.php)
$router->group('/api/v1', function (App\Core\Router $r) {
    $r->get('/notifications', 'App\\Controllers\\Api\\NotificationApiController', 'index');
    $r->get('/notifications/unread-count', 'App\\Controllers\\Api\\NotificationApiController', 'unreadCount');
    $r->post('/notifications/{id}/read', 'App\\Controllers\\Api\\NotificationApiController', 'markAsRead');
    $r->post('/notifications/read-all', 'App\\Controllers\\Api\\NotificationApiController', 'markAllAsRead');
    $r->delete('/notifications/{id}', 'App\\Controllers\\Api\\NotificationApiController', 'destroy');
    $r->delete('/notifications', 'App\\Controllers\\Api\\NotificationApiController', 'clearAll');
    $r->get('/notification-preferences', 'App\\Controllers\\Api\\NotificationApiController', 'getPreferences');
    $r->put('/notification-preferences', 'App\\Controllers\\Api\\NotificationApiController', 'updatePreferences');
    $r->get('/notifications/stream', 'App\\Controllers\\Api\\NotificationApiController', 'stream');
}, ['AuthMiddleware', 'ForceJsonMiddleware']);