<?php

declare(strict_types=1);

/**
 * Dev-only static-file server shared by the three `php -S` routers
 * (laravel-dev, php-dev, website-dev).
 *
 * WHY THIS EXISTS
 * ---------------
 * A router script that does `return false` for an existing file hands the
 * response to PHP's built-in server, which sends ONLY:
 *
 *     HTTP/1.1 200 OK
 *     Content-Type: text/css; charset=UTF-8
 *     Content-Length: 15
 *
 * No `Cache-Control`, no `Expires`, no `ETag`, no `Last-Modified`. Headers set
 * with `header()` before `return false` are discarded (verified against
 * php:8.3-cli) — the built-in server writes its own response head.
 *
 * A response with no explicit freshness lifetime and no validator is exactly
 * the case where browsers and proxies fall back to *heuristic* caching
 * (RFC 9111 §4.2.2: 10% of the time since Last-Modified). So a CSS or JS edit
 * can look like it "did nothing" even though the container re-read the file
 * correctly — the stale copy is coming from the client. That is the single
 * most common reason a live-reload harness appears broken.
 *
 * The fix is to serve the asset ourselves and say `no-store`. Anything with a
 * MIME type not in the table below returns false, so the built-in server
 * handles it exactly as it does today — this never becomes a new failure mode.
 */

if (!function_exists('esk_dev_serve_static')) {
    /**
     * Serve a static asset from $docroot with caching disabled.
     *
     * @return bool true when the response was emitted and the caller must
     *              `return true`; false when the caller should carry on
     *              (fall through to the app, or `return false` to let the
     *              built-in server try).
     */
    function esk_dev_serve_static(string $docroot, string $path): bool
    {
        if ($docroot === '' || $path === '' || $path === '/') {
            return false;
        }

        // rawurldecode before traversal checks, so `%2e%2e` is caught too.
        $relative = ltrim(rawurldecode($path), '/');
        if ($relative === '') {
            return false;
        }

        $root = realpath($docroot);
        $file = realpath(rtrim($docroot, '/') . '/' . $relative);

        // A request must not escape the docroot, and a symlinked candidate has
        // to resolve *inside* the docroot too.
        if ($root === false || $file === false) {
            return false;
        }
        if (!str_starts_with($file, $root . DIRECTORY_SEPARATOR)) {
            return false;
        }
        if (!is_file($file)) {
            return false;
        }

        $types = [
            'css'   => 'text/css; charset=UTF-8',
            'js'    => 'application/javascript; charset=UTF-8',
            'mjs'   => 'application/javascript; charset=UTF-8',
            'json'  => 'application/json; charset=UTF-8',
            'map'   => 'application/json; charset=UTF-8',
            'svg'   => 'image/svg+xml',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'webp'  => 'image/webp',
            'avif'  => 'image/avif',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'otf'   => 'font/otf',
            'eot'   => 'application/vnd.ms-fontobject',
            'txt'   => 'text/plain; charset=UTF-8',
            'xml'   => 'application/xml; charset=UTF-8',
            'webmanifest' => 'application/manifest+json',
            'pdf'   => 'application/pdf',
        ];

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $type = $types[$extension] ?? null;
        if ($type === null) {
            return false; // unknown type -> built-in server, as before
        }

        $size = filesize($file);
        if ($size === false) {
            return false;
        }

        // Dev only: never let a client or an intermediary reuse this body.
        header('Content-Type: ' . $type);
        header('Content-Length: ' . $size);
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        // A validator invites a 304, and a 304 can be served from a cache the
        // user cannot clear from DevTools. Omit ETag/Last-Modified entirely.

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
            readfile($file);
        }

        return true;
    }
}
