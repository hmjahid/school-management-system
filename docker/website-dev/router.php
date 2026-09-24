<?php

declare(strict_types=1);

// Router for PHP's built-in dev server (docker/website-dev).
//
// Mounted at /router.php (outside the bind-mounted product tree) and run with
// `php -S ... -t /var/www/public /router.php`. Serve existing static files
// from the docroot directly, route everything else through the front
// controller — mirrors production.

$uri  = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';
$docroot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');

if ($path !== '/' && $path !== '' && $docroot !== '') {
    $candidate = $docroot . $path;
    if (is_file($candidate)) {
        return false; // let the dev server serve the static file
    }
}

require $docroot . '/index.php';

return true;