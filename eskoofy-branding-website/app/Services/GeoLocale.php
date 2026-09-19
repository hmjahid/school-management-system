<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Decides the default UI locale from the visitor's location.
 *
 * Business rule: a Bangladeshi IP/location gets `bd_locale` (bn) by default;
 * everyone else gets `other_locale` (en). This is pure runtime configuration —
 * the website itself remains a single variant (English/USD/UTC).
 */
class GeoLocale
{
    /**
     * Detect the locale to default to for the given request.
     *
     * @param array  $server   $_SERVER-style data (CF_IPCountry, X_IPCountry,
     *                         IPCountry, HTTP_ACCEPT_LANGUAGE, REMOTE_ADDR,
     *                         HTTP_X_FORWARDED_FOR)
     * @param array  $config   the `i18n.geo` config block
     * @param string|null $timezone optional client-side timezone hint
     */
    public static function detect(array $server, array $config = [], ?string $timezone = null): string
    {
        $bd = $config['bd_locale'] ?? 'bn';
        $en = $config['other_locale'] ?? 'en';

        if (($config['enabled'] ?? 'true') !== 'true') {
            return $en;
        }

        $cdnHeaders = (array) ($config['cdn_headers'] ?? ['CF-IPCountry', 'X-IPCountry', 'IPCountry']);
        foreach ($cdnHeaders as $header) {
            $value = self::serverHeader($server, $header);
            if (self::isBdHeader($value)) {
                return $bd;
            }
        }

        if (($config['accept_language'] ?? true) === true
            && self::isBdAcceptLanguage($server['HTTP_ACCEPT_LANGUAGE'] ?? null)) {
            return $bd;
        }

        $remoteUrl = (string) ($config['remote_api_url'] ?? '');
        if ($remoteUrl !== '' && self::remoteIsBd($server, $remoteUrl, (int) ($config['remote_api_timeout'] ?? 2))) {
            return $bd;
        }

        if (($config['use_timezone_hint'] ?? true) === true && self::isBdTimezone($timezone)) {
            return $bd;
        }

        return $en;
    }

    public static function isBdHeader(?string $value): bool
    {
        return is_string($value) && strtoupper(trim($value)) === 'BD';
    }

    public static function isBdAcceptLanguage(?string $value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        foreach (explode(',', $value) as $range) {
            $part = strtolower(trim(explode(';', $range)[0]));
            if ($part === 'bn' || $part === 'bn-bd') {
                return true;
            }
        }

        return false;
    }

    public static function isBdTimezone(?string $value): bool
    {
        return is_string($value) && trim($value) === 'Asia/Dhaka';
    }

    private static function remoteIsBd(array $server, string $url, int $timeout): bool
    {
        $ip = trim((string) ($server['HTTP_X_FORWARDED_FOR'] ?? $server['X_IPCountry'] ?? ''));
        if ($ip === '' || str_contains($ip, ',')) {
            $ip = trim((string) ($server['REMOTE_ADDR'] ?? ''));
        }
        if ($ip === '') {
            return false;
        }

        $endpoint = str_replace('{ip}', rawurlencode($ip), $url);
        $context = stream_context_create([
            'http' => [
                'timeout'       => max(1, $timeout),
                'ignore_errors' => true,
                'user_agent'    => 'Eskoofy/1.0',
            ],
        ]);

        $body = @file_get_contents($endpoint, false, $context);
        if ($body === false) {
            return false;
        }

        $data = json_decode($body, true);

        return is_array($data) && self::isBdHeader((string) ($data['country'] ?? ''));
    }

    /**
     * Look up a server header by the standard name, accepting all the
     * common PHP key variants for an HTTP header.
     */
    private static function serverHeader(array $server, string $header): ?string
    {
        $underscored = str_replace('-', '_', $header);
        $candidates = [
            $header,
            $underscored,
            'HTTP_' . strtoupper($underscored),
        ];

        foreach ($candidates as $key) {
            if (isset($server[$key]) && $server[$key] !== '') {
                return (string) $server[$key];
            }
        }

        return null;
    }
}