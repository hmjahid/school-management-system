<?php

namespace App\Http\Middleware;

use App\Models\VisitorLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogVisitor
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && !$request->is('api/*', 'build/*', 'storage/*', 'favicon.ico')) {
            try {
                VisitorLog::create([
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user_agent' => $request->userAgent(),
                    'referer' => $request->headers->get('referer'),
                    'user_id' => auth()->id(),
                ]);
            } catch (\Throwable $e) {
                // Silently fail — never block the request
            }
        }

        return $response;
    }
}
