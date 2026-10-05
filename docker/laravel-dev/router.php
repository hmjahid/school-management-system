<?php

// Dev-only router for `php -S` (docker/laravel-dev harness).
//
// `php artisan serve` strips every env var except a tiny allowlist
// (ServeCommand::$passthroughVariables), so compose's DB_*/APP_URL/... never
// reach the app and it silently falls back to the bind-mounted .env (sqlite,
// 127.0.0.1:8000). A plain `php -S` passes the full environment through, which
// is what the MySQL dev stack needs. This mirrors Laravel's built-in
// server.php but is served from the harness mount (read-only, outside the app).
//
// Static files go through esk_dev_serve_static() (no-store) rather than
// `return false`, which would leave the response without a freshness lifetime
// or a validator and let the browser heuristically cache your edit. See
// docker/static-dev.php.
require '/docker/static-dev.php';

$publicPath = '/var/www/public';
$requested = urldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (esk_dev_serve_static($publicPath, $requested)) {
    return true;
}

if ($requested !== '/' && file_exists($publicPath.$requested) && ! is_dir($publicPath.$requested)) {
    return false; // unknown MIME type -> let the dev server serve the file
}

require $publicPath.'/index.php';

return true;
