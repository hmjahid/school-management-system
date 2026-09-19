<?php
declare(strict_types=1);

require __DIR__ . '/../app/Core/bootstrap.php';

(new App\Core\Middleware\SecurityHeadersMiddleware())->handle();
(new App\Core\Middleware\CorsMiddleware())->handle();
(new App\Core\Middleware\LocaleMiddleware())->handle();

// Record public-site page views for the admin visitor log (best-effort).
App\Services\VisitorLogger::handle(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/'
);

$router = new App\Core\Router();
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);