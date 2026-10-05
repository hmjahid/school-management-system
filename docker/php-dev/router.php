<?php

declare(strict_types=1);

// Router for PHP's built-in dev server (docker/php-dev).
//
// Mirrors the app's public/.htaccess: serve existing static files directly,
// route everything else through public/index.php.
//
// Static files go through esk_dev_serve_static() (no-store) rather than
// `return false`, which would leave the response without a freshness lifetime
// or a validator and let the browser heuristically cache your edit. See
// docker/static-dev.php.

require '/docker/static-dev.php';

$uri  = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';
$docroot = __DIR__ . '/public';

if (esk_dev_serve_static($docroot, $path)) {
    return true;
}

if ($path !== '/' && $path !== '') {
    $candidate = $docroot . $path;
    if (is_file($candidate)) {
        return false; // unknown MIME type -> let the dev server serve the file
    }
}

require $docroot . '/index.php';

return true;
