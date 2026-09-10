<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Session;
use App\Services\GeoLocale;
use App\Services\I18n;

class LanguageController extends Controller
{
    public function switch(string $locale): void
    {
        if (!I18n::switch($locale)) {
            $this->withError('Unsupported language.');
        }

        // A manual switch wins over geo-detection for the rest of the session.
        Session::getInstance()->remove('geo_locale');

        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    /**
     * Client-side timezone backstop (GET /language/geo?tz=Asia/Dhaka).
     * Refines geo_locale (never overrides a manual switch) and runs at most once
     * per session. Returns JSON understood by the inline hint script.
     */
    public function geo(): void
    {
        $session = Session::getInstance();
        $before = I18n::current();

        $locales = I18n::supported();
        $config = require dirname(__DIR__, 3) . '/config/app.php';
        $geo = $config['i18n']['geo'] ?? [];
        $default = (string) ($config['i18n']['default'] ?? 'en');

        $session->set('geo_tz_checked', true);

        $tz = (string) ($_GET['tz'] ?? '');

        if (!$session->has('locale')) {
            try {
                $detected = GeoLocale::detect($_SERVER, $geo, $tz !== '' ? $tz : null);
                if (!isset($locales[$detected])) {
                    $detected = $default;
                }
                $session->set('geo_locale', $detected);
            } catch (\Throwable $e) {
                // Ignore — keep the existing geo_locale.
            }
        }

        $after = I18n::current();
        $applied = $after !== $before;

        $this->json([
            'locale'  => $after,
            'applied' => $applied,
            'reload'  => $applied,
        ]);
    }
}