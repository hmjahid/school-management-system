<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldApply($request)) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure() || config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $existing = $response->headers->get('Content-Security-Policy', '');

        $vite = $this->viteDevOrigin();

        $script = "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://fonts.bunny.net";
        $style = "'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com";
        $img = "'self' data: https:";
        $connect = "'self' https://fonts.googleapis.com https://fonts.gstatic.com";

        // In development the page is served by `php artisan serve` while Vite's
        // dev server (localhost:5173) streamed the assets on a *different*
        // origin. Without these allowlisted the strict CSP below refuses every
        // script/style/image the browser pulls from the Vite dev server and the
        // site renders unstyled. Production CSP stays exactly as before.
        if ($vite !== null) {
            $script = $script.' '.$vite;
            $style = $style.' '.$vite;
            $img = $img.' '.$vite;
            $connect = $connect.' '.$vite.' '.str_replace('http', 'ws', (string) $vite);
        }

        $csp = "default-src 'self'; script-src {$script}; style-src {$style}; img-src {$img}; font-src 'self' data: https://fonts.bunny.net https://fonts.googleapis.com https://fonts.gstatic.com; connect-src {$connect}; frame-ancestors 'self'";
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }

    protected function shouldApply(Request $request): bool
    {
        return ! $request->is('up') && ! $request->expectsJson() && ! $request->ajax();
    }

    /**
     * Origin of the Vite dev server in local development, if it is running.
     *
     * Laravel writes the dev server URL into public/hot (fallback manifest) and
     *
     * @vite reads it from there, so we reuse the same authoritative source and
     * keep a strict CSP in production (no hot file → null).
     */
    protected function viteDevOrigin(): ?string
    {
        if (app()->environment('production')) {
            return null;
        }

        $hot = public_path('hot');

        if (! file_exists($hot)) {
            return null;
        }

        $url = trim((string) file_get_contents($hot));

        if ($url === '' || ! str_starts_with($url, 'http')) {
            return null;
        }

        return $url;
    }
}
