<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $locales = config('school.supported_locales', ['en']);
        $locale = session('locale');
        if (is_string($locale) && in_array($locale, $locales, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
