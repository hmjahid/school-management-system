<?php

namespace App\Http\Middleware;

use App\Models\WebsiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $locales = config('school.supported_locales', ['en']);

        $locale = session('locale');
        if (! is_string($locale) || ! in_array($locale, $locales, true)) {
            $locale = $this->defaultLocale();
        }

        if (in_array($locale, $locales, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    /**
     * Resolve the language to use when the visitor has not picked one yet:
     * the admin-configured default (Dashboard → Settings → Default site
     * language), falling back to the framework's app.locale config.
     */
    protected function defaultLocale(): string
    {
        try {
            if (Schema::hasTable('website_settings')) {
                $settings = WebsiteSetting::first();
                if ($settings) {
                    return $settings->resolvedDefaultLocale();
                }
            }
        } catch (\Throwable $e) {
            // Settings table unavailable (e.g. mid-migration) — fall through.
        }

        return (string) config('app.locale', 'en');
    }
}
