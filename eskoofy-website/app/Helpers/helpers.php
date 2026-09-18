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