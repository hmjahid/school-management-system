<?php
declare(strict_types=1);

require __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Router;
use App\Core\Middleware\CorsMiddleware;

// Apply CORS globally
(new CorsMiddleware())->handle();

$router = new Router();

require __DIR__ . '/../routes/web.php';
if (file_exists(__DIR__ . '/../routes/api.php')) {
    require __DIR__ . '/../routes/api.php';
}

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
