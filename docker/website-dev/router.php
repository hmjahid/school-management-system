<?php

declare(strict_types=1);

// Router for PHP's built-in dev server (docker/website-dev).
//
// Mounted at /router.php (outside the bind-mounted product tree) and run with
// `php -S ... -t /var/www/public /router.php`. Serve existing static files
// from the docroot directly, route everything else through the front
// controller — mirrors production.
//
// Static files are served by esk_dev_serve_static() rather than `return false`
// so they carry `Cache-Control: no-store`. `return false` makes the built-in
// server emit a response with no freshness lifetime and no validator, which is
// the case where browsers and proxies guess and serve you a stale stylesheet
// after you edit it. See docker/static-dev.php for the full reasoning.

require '/docker/static-dev.php';

$uri  = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';
$docroot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');

if (esk_dev_serve_static($docroot, $path)) {
    return true;
}

if ($path !== '/' && $path !== '' && $docroot !== '') {
    $candidate = $docroot . $path;
    if (is_file($candidate)) {
        return false; // unknown MIME type -> let the dev server serve the file
    }
}

require $docroot . '/index.php';

return true;
