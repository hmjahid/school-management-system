<?php
declare(strict_types=1);

use App\Controllers\Api\LicenseApiController;

$router->group('/api/v1', function (App\Core\Router $router): void {
    $router->get('/ping', LicenseApiController::class, 'ping');
    $router->post('/licenses/activate', LicenseApiController::class, 'activate', ['Throttle:10,1']);
    $router->post('/licenses/validate', LicenseApiController::class, 'checkLicense', ['Throttle:30,1']);
    $router->post('/licenses/deactivate', LicenseApiController::class, 'deactivate', ['Throttle:10,1']);
    $router->get('/licenses/status', LicenseApiController::class, 'status', ['Throttle:30,1']);
}, ['ForceJsonMiddleware']);