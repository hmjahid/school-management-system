<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class DashboardWriteThrottle
{
    protected const LIMIT = 120;

    protected const DECAY_SECONDS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $key = 'dashboard_write:'.($request->user()?->id ?? 'guest').':'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::LIMIT)) {
            abort(429, 'Too many requests. Please try again later.');
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);

        return $next($request);
    }
}