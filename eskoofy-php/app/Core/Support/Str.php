<?php
declare(strict_types=1);

namespace App\Core\Support;

/**
 * Small subset of Laravel's Str:: helper used by Blade views.
 */
class Str
{
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }
        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    public static function words(string $value, int $words = 100, string $end = '...'): string
    {
        $parts = preg_split('/\s+/u', trim($value));
        if (count($parts) <= $words) {
            return $value;
        }
        return implode(' ', array_slice($parts, 0, $words)) . $end;
    }

    public static function slug(string $title, string $separator = '-', ?string $language = 'en'): string
    {
        $title = (string) preg_replace('~[^\pL\d]+~u', $separator, $title);
        $title = (string) preg_replace('~[^-\w]+~', '', $title);
        $title = trim($title, $separator);
        return mb_strtolower($title, 'UTF-8');
    }

    public static function ucfirst(string $string): string
    {
        return self::upper(self::substr($string, 0, 1)) . self::substr($string, 1);
    }

    public static function upper(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    public static function lower(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }

    public static function substr(?string $string, int $start, ?int $length = null): string
    {
        return mb_substr((string) $string, $start, $length, 'UTF-8');
    }

    public static function startsWith(string $haystack, array|string $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    public static function endsWith(string $haystack, array|string $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_ends_with($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    public static function contains(string $haystack, array|string $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    public static function replace(array|string $search, array|string $replace, string $subject): string
    {
        return str_replace($search, $replace, $subject);
    }

    public static function random(int $length = 16): string
    {
        $string = '';
        while (($len = strlen($string)) < $length) {
            $size = $length - $len;
            $bytes = random_bytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }
        return $string;
    }

    public static function title(string $value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    public static function trim(string $value, string $charMask = " \t\n\r\0\x0B"): string
    {
        return trim($value, $charMask);
    }

    public static function singular(string $value): string
    {
        if (str_ends_with($value, 'ies') && strlen($value) > 3) {
            return substr($value, 0, -3) . 'y';
        }
        if (str_ends_with($value, 'ses') || str_ends_with($value, 'xes') || str_ends_with($value, 'zes') || str_ends_with($value, 'ches') || str_ends_with($value, 'shes')) {
            return substr($value, 0, -2);
        }
        if (str_ends_with($value, 's') && !str_ends_with($value, 'ss')) {
            return substr($value, 0, -1);
        }
        return $value;
    }

    public static function plural(string $value, int $count = 2): string
    {
        if ($count === 1) {
            return $value;
        }
        if (str_ends_with($value, 'y') && !str_ends_with($value, 'ay') && !str_ends_with($value, 'ey') && !str_ends_with($value, 'oy') && !str_ends_with($value, 'uy')) {
            return substr($value, 0, -1) . 'ies';
        }
        if (str_ends_with($value, 's') || str_ends_with($value, 'x') || str_ends_with($value, 'z') || str_ends_with($value, 'ch') || str_ends_with($value, 'sh')) {
            return $value . 'es';
        }
        return $value . 's';
    }

    public static function mask(string $subject, string $replace = '*', int $index = 0): string
    {
        $segments = explode(' ', $subject);
        foreach ($segments as $i => &$segment) {
            if ($i !== $index) {
                continue;
            }
            $segment = str_repeat($replace, max(1, mb_strlen($segment)));
        }
        return implode(' ', $segments);
    }
}