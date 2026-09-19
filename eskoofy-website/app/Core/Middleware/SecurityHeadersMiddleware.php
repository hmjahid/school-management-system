<?php

declare(strict_types=1);

namespace App\Core\Middleware;

/**
 * Emits standard security response headers (parity with the Laravel app's
 * SecurityHeaders middleware). Kept conservative so inline styles/scripts in
 * the shared Blade templates keep working.
 */
class SecurityHeadersMiddleware
{
    public function handle(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header('Cross-Origin-Opener-Policy: same-origin');

        if (!headers_sent()) {
            header_remove('X-Powered-By');
        }

        $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        if ($isSecure || ($_ENV['APP_ENV'] ?? '') === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://fonts.bunny.net; style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com; img-src 'self' data: https:; font-src 'self' data: https://fonts.bunny.net https://fonts.googleapis.com https://fonts.gstatic.com; connect-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com; frame-ancestors 'self'");
    }
}