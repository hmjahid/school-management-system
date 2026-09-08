<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = config('api.cors_origins', []);
        $origin = $request->headers->get('Origin');
        $allowedOrigin = in_array($origin, $allowedOrigins, true) ? $origin : ($allowedOrigins[0] ?? null);

        if ($request->isMethod('OPTIONS')) {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }

        if ($allowedOrigin) {
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
            $response->headers->set('Vary', 'Origin');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, X-Request-ID, Accept');
        $response->headers->set('Access-Control-Expose-Headers', 'X-Request-ID');
        $response->headers->set('Access-Control-Max-Age', '86400');

        return $response;
    }
}
