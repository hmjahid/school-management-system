<?php
declare(strict_types=1);

require __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Router;
use App\Core\CorsMiddleware;

$router = new Router();

// Apply CORS globally
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
