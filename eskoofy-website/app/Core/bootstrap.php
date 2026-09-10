<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$appRoot = dirname(__DIR__, 2);

if (file_exists($appRoot . '/vendor/autoload.php')) {
    require_once $appRoot . '/vendor/autoload.php';
}
require_once __DIR__ . '/../Helpers/helpers.php';

// Load .env if vlucas/dotenv is not available (shared hosting)
$envFile = $appRoot . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}

// Set timezone (website is UTC; mirrors config fallback).
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Load config
$config = require $appRoot . '/config/app.php';

// Register autoloader for app/ classes
spl_autoload_register(function (string $class) use ($appRoot) {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = $appRoot . '/app/' . $relative . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// CSRF verification for state-changing non-API requests.
// The license-server API is exempt: products call it programmatically.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (!str_starts_with($requestPath, '/api/')
    && in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'PATCH', 'DELETE'])) {
    $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($token !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<h1>403 — CSRF token mismatch</h1>';
        exit;
    }
}

// Set shared view data
\App\Core\View::share('appName', $config['name'] ?? 'Eskoofy');
\App\Core\View::share('schoolName', $config['site']['name'] ?? 'Eskoofy');
\App\Core\View::share('siteTagline', $config['site']['tagline'] ?? 'School management software & WordPress theme');
\App\Core\View::share('variant', $config['variant'] ?? 'int');
\App\Core\View::share('locale', $config['locale'] ?? 'en');
