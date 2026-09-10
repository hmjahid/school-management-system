<?php
declare(strict_types=1);

use App\Controllers\Api\LicenseApiController;

$router->group('/api/v1', function (App\Core\Router $router): void {
    $router->get('/ping', LicenseApiController::class, 'ping');
    $router->post('/licenses/activate', LicenseApiController::class, 'activate');
    $router->post('/licenses/validate', LicenseApiController::class, 'checkLicense');
    $router->post('/licenses/deactivate', LicenseApiController::class, 'deactivate');
    $router->get('/licenses/status', LicenseApiController::class, 'status');
}, ['ForceJsonMiddleware']);