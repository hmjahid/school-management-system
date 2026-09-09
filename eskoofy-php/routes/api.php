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