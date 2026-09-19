<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Models\Settings;
use App\Models\Visitor;

/**
 * Records public-site page views for the admin visitor log.
 *
 * Deliberately conservative so it never slows down or breaks a request:
 *  - only GET requests,
 *  - only public marketing pages (admin/account/api/auth/checkout excluded),
 *  - skips static assets,
 *  - honours the `DNT` header and the `visitors.logging_enabled` setting,
 *  - flags known bots (stored, but excluded from the UI by default),
 *  - de-duplicates repeat views of the same path in one session.
 */
class VisitorLogger
{
    private const DEDUPE_WINDOW = 1800; // seconds

    private const SKIP_PREFIXES = [
        '/admin', '/account', '/api', '/webhooks', '/login', '/register',
        '/logout', '/checkout', '/language', '/brand', '/icons',
    ];

    private const SKIP_EXTENSIONS = [
        'css', 'js', 'mjs', 'map', 'json', 'xml', 'txt', 'pdf', 'zip',
        'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'avif',
        'woff', 'woff2', 'ttf', 'otf', 'eot', 'mp4', 'webm',
    ];

    public static function handle(string $method, string $path): void
    {
        try {
            if (!self::shouldRecord($method, $path)) {
                return;
            }

            $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
            $isBot = self::isBot($ua);

            // De-duplicate the same path within a session (bot checks are cheap,
            // so bots bypass the window and are logged for an accurate picture).
            if (!$isBot && self::recentlySeen($path)) {
                return;
            }

            Visitor::record([
                'ip_address' => self::clientIp(),
                'user_agent' => $ua !== '' ? mb_substr($ua, 0, 255) : null,
                'path'       => mb_substr($path, 0, 255),
                'referrer'   => self::referrer(),
                'country'    => self::country(),
                'locale'     => I18n::current(),
                'is_bot'     => $isBot,
                'visited_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // Analytics must never break a page render.
        }
    }

    public static function shouldRecord(string $method, string $path): bool
    {
        if (!Settings::bool('visitors.logging_enabled', true)) {
            return false;
        }
        if (strtoupper($method) !== 'GET') {
            return false;
        }
        if (self::wantsNoTrack()) {
            return false;
        }
        if (!self::isTrackable($path)) {
            return false;
        }

        return true;
    }

    public static function isTrackable(string $path): bool
    {
        $path = '/' . ltrim(parse_url($path, PHP_URL_PATH) ?: '/', '/');

        foreach (self::SKIP_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return false;
            }
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if ($extension !== '' && in_array($extension, self::SKIP_EXTENSIONS, true)) {
            return false;
        }

        return true;
    }

    public static function isBot(string $userAgent): bool
    {
        if ($userAgent === '') {
            return true;
        }

        return (bool) preg_match(
            '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|whatsapp|telegram|'
            . 'uptimerobot|pingdom|monitor|headless|lighthouse|python-requests|curl|wget|'
            . 'axios|go-http-client|okhttp|java\/|libwww|scrapy/i',
            $userAgent
        );
    }

    public static function wantsNoTrack(): bool
    {
        return in_array(
            strtolower((string) ($_SERVER['HTTP_DNT'] ?? '')),
            ['1', 'yes', 'true'],
            true
        );
    }

    private static function recentlySeen(string $path): bool
    {
        $seen = Session::getInstance()->get('visitor_seen', []);
        if (!is_array($seen)) {
            $seen = [];
        }

        $now = time();
        $last = (int) ($seen[$path] ?? 0);
        $seen = array_filter($seen, fn ($ts) => ($now - (int) $ts) < self::DEDUPE_WINDOW);
        $seen[$path] = $now;
        Session::getInstance()->set('visitor_seen', $seen);

        return $last > 0 && ($now - $last) < self::DEDUPE_WINDOW;
    }

    private static function clientIp(): ?string
    {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return mb_substr($candidate, 0, 64);
            }
        }

        return null;
    }

    private static function referrer(): ?string
    {
        $referrer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($referrer === '') {
            return null;
        }

        return mb_substr($referrer, 0, 255);
    }

    private static function country(): ?string
    {
        foreach (['HTTP_CF_IPCOUNTRY', 'HTTP_X_IPCOUNTRY', 'HTTP_IPCOUNTRY'] as $header) {
            $value = trim((string) ($_SERVER[$header] ?? ''));
            if ($value !== '' && strtoupper($value) !== 'XX' && strtoupper($value) !== 'T1') {
                return mb_substr($value, 0, 64);
            }
        }

        return null;
    }
}
