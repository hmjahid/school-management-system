<?php
declare(strict_types=1);

namespace App\Core\Middleware;

use App\Services\GeoLocale;
use App\Services\I18n;

/**
 * Decides the default UI language from the visitor's location, once per session.
 *
 * Precedence: manual session `locale` (language switcher) > session `geo_locale`
 * (computed on first request) > config default. Performs no output, so API
 * requests are unaffected.
 */
class LocaleMiddleware
{
    public function handle(): void
    {
        $session = \App\Core\Session::getInstance();

        if ($session->has('locale') || $session->has('geo_locale')) {
            return;
        }

        $config = require dirname(__DIR__, 3) . '/config/app.php';
        $geo = $config['i18n']['geo'] ?? [];
        $default = (string) ($config['i18n']['default'] ?? 'en');

        try {
            $locales = I18n::supported();
            $detected = GeoLocale::detect($_SERVER, $geo);
            if (!isset($locales[$detected])) {
                $detected = $default;
            }
        } catch (\Throwable $e) {
            $detected = $default;
        }

        $session->set('geo_locale', $detected);
    }
}