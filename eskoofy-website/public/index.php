<?php
declare(strict_types=1);

require __DIR__ . '/../app/Core/bootstrap.php';

(new App\Core\Middleware\CorsMiddleware())->handle();

$router = new App\Core\Router();
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);