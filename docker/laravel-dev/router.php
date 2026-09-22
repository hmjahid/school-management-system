<?php
// Dev-only router for `php -S` (docker/laravel-dev harness).
//
// `php artisan serve` strips every env var except a tiny allowlist
// (ServeCommand::$passthroughVariables), so compose's DB_*/APP_URL/... never
// reach the app and it silently falls back to the bind-mounted .env (sqlite,
// 127.0.0.1:8000). A plain `php -S` passes the full environment through, which
// is what the MySQL dev stack needs. This mirrors Laravel's built-in
// server.php but is served from the harness mount (read-only, outside the app).
$publicPath = '/var/www/public';
$requested = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($requested !== '/' && file_exists($publicPath.$requested) && ! is_dir($publicPath.$requested)) {
    return false;
}

require $publicPath.'/index.php';