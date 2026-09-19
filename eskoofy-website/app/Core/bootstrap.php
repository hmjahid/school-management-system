<?php
declare(strict_types=1);

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
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if ($key === '') continue;
        // Strip inline comments (respect quoted values).
        $value = trim($value);
        if ($value !== '') {
            $quote = $value[0];
            if ($quote === '"' || $quote === "'") {
                $end = strpos($value, $quote, 1);
                if ($end !== false) {
                    $value = substr($value, 1, $end - 1);
                }
            } else {
                $hash = strpos($value, ' #');
                if ($hash !== false) {
                    $value = substr($value, 0, $hash);
                }
                $value = trim($value);
            }
        }
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}

// Hardened session cookie flags (HttpOnly, SameSite=Lax, Secure in production).
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => filter_var($_ENV['SESSION_SECURE_COOKIE'] ?? false, FILTER_VALIDATE_BOOL),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
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
// The license-server API and gateway webhooks are exempt: they are called
// programmatically (products / gateways) and carry their own signatures.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (!str_starts_with($requestPath, '/api/')
    && !str_starts_with($requestPath, '/webhooks/')
    && in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'PATCH', 'DELETE'])) {
    $token = (string) ($_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
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
