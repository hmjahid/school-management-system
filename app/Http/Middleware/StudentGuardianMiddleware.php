<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudentGuardianMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', __('Please log in to continue.'));
        }

        if (!Auth::user()->hasAnyRole(['student', 'guardian'])) {
            Auth::logout();

            return redirect()->route('login')->with('error', __('You do not have permission to access this area.'));
        }

        return $next($request);
    }
}
