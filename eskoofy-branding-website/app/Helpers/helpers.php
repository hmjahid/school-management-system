<?php
declare(strict_types=1);

use App\Services\I18n;

if (!function_exists('__')) {
    /**
     * Translate a key using the active locale.
     */
    function __(string $key, array $params = [], ?string $locale = null): string
    {
        return I18n::t($key, $params, $locale);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return $_SESSION['csrf_token'] ?? '';
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
    }
}

if (!function_exists('old')) {
    /**
     * Re-populate a form field from the previous POST payload.
     */
    function old(string $key, string $default = ''): string
    {
        return isset($_POST[$key])
            ? htmlspecialchars((string) $_POST[$key], ENT_QUOTES)
            : $default;
    }
}

if (!function_exists('esc')) {
    /**
     * HTML-escape a value (short alias used by admin views).
     */
    function esc(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('num_short')) {
    /**
     * Compact number for dense surfaces: 12400 → "12.4k", 1200000 → "1.2M".
     *
     * Use on KPI feet, chart axes and table summaries; tooltips and detail
     * rows keep the full tabular value — this is the at-a-glance form.
     */
    function num_short(int|float $value): string
    {
        $n = (float) $value;
        $sign = $n < 0 ? '-' : '';
        $abs = abs($n);

        if ($abs >= 1_000_000_000) {
            return $sign . round($abs / 1_000_000_000, $abs >= 10_000_000_000 ? 0 : 1) . 'B';
        }
        if ($abs >= 1_000_000) {
            return $sign . round($abs / 1_000_000, $abs >= 10_000_000 ? 0 : 1) . 'M';
        }
        if ($abs >= 1_000) {
            return $sign . round($abs / 1_000, $abs >= 10_000 ? 0 : 1) . 'k';
        }

        return $sign . number_format($abs, $abs >= 100 || floor($abs) === $abs ? 0 : 1);
    }
}

if (!function_exists('money_short')) {
    /**
     * Compact USD form of num_short(): 12400 → "$12.4k".
     */
    function money_short(int|float $value): string
    {
        return '$' . num_short($value);
    }
}