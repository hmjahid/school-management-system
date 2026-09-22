<?php

declare(strict_types=1);

// Router for PHP's built-in dev server (docker/php-dev).
//
// Mirrors the app's public/.htaccess: serve existing static files directly,
// route everything else through public/index.php.

$uri  = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';

if ($path !== '/' && $path !== '') {
    $candidate = __DIR__ . '/public' . $path;
    if (is_file($candidate)) {
        return false; // let the dev server serve the static file
    }
}

require __DIR__ . '/public/index.php';

return true;