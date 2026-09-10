<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;

/**
 * Minimal translation service: loads lang/{locale}.php arrays, falls back to
 * the default locale, and reads the requested locale from the session-set
 * `locale` value. Used by the `__()` helper in views.
 */
class I18n
{
    private static ?array $messages = null;
    private static ?string $loadedLocale = null;

    public static function supported(): array
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';

        return $config['i18n']['locales'] ?? ['en' => 'English'];
    }

    public static function defaultLocale(): string
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';

        return (string) ($config['i18n']['default'] ?? 'en');
    }

    /**
     * The currently active locale: session (manual switch) -> session geo
     * locale -> env default.
     */
    public static function current(): string
    {
        $locales = self::supported();

        $session = Session::getInstance()->get('locale');
        if (is_string($session) && array_key_exists($session, $locales)) {
            return $session;
        }

        $geo = Session::getInstance()->get('geo_locale');
        if (is_string($geo) && array_key_exists($geo, $locales)) {
            return $geo;
        }

        return self::defaultLocale();
    }

    public static function switch(string $locale): bool
    {
        $locales = self::supported();
        if (!array_key_exists($locale, $locales)) {
            return false;
        }

        Session::getInstance()->set('locale', $locale);

        return true;
    }

    /**
     * Whether the page should ask the browser for its timezone to refine the
     * geo-language guess. True only while the current locale is not already the
     * BD locale, the hint is config-enabled, and the check has not run yet.
     */
    public static function wantsTimezoneHint(): bool
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $geo = $config['i18n']['geo'] ?? [];

        if (($geo['use_timezone_hint'] ?? true) !== true) {
            return false;
        }

        $session = Session::getInstance();
        if ($session->has('locale') || $session->get('geo_tz_checked') === true) {
            return false;
        }

        $bdLocale = $geo['bd_locale'] ?? 'bn';

        return self::current() !== $bdLocale;
    }

    public static function t(string $key, array $params = [], ?string $locale = null): string
    {
        $locale = $locale ?? self::current();
        $messages = self::messages($locale);

        $text = $messages[$key] ?? self::messages(self::defaultLocale())[$key] ?? $key;

        foreach ($params as $name => $value) {
            $text = str_replace(':' . $name, (string) $value, $text);
        }

        return $text;
    }

    /**
     * @return array<string, string>
     */
    private static function messages(string $locale): array
    {
        if (self::$messages !== null && self::$loadedLocale === $locale) {
            return self::$messages;
        }

        $file = dirname(__DIR__, 2) . '/lang/' . $locale . '.php';
        $messages = is_file($file) ? (array) require $file : [];

        self::$messages = $messages;
        self::$loadedLocale = $locale;

        return $messages;
    }
}