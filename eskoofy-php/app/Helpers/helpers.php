<?php
declare(strict_types=1);

use App\Core\Session;
use App\Core\Database;

function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/app.php';
    }
    $keys = explode('.', $key);
    $value = $config;
    foreach ($keys as $k) {
        if (!isset($value[$k])) return $default;
        $value = $value[$k];
    }
    return $value;
}

function site_ui(string $key, ?string $locale = null): string
{
    static $strings = null;
    if ($strings === null) {
        $locale = $locale ?? ($_SESSION['locale'] ?? config('app.locale', 'en'));
        $file = __DIR__ . '/../lang/' . $locale . '/site_frontend.php';
        if (!file_exists($file)) {
            $file = __DIR__ . '/../lang/en/site_frontend.php';
        }
        $strings = require $file;
    }
    $keys = explode('.', $key);
    $value = $strings;
    foreach ($keys as $k) {
        if (!isset($value[$k])) return $key;
        $value = $value[$k];
    }
    return (string) $value;
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function old(string $key, ?string $default = null): ?string
{
    return Session::getInstance()->get('_old_' . $key) ?? $default;
}

function csrf_field(): string
{
    $token = Session::getInstance()->get('csrf_token', '');
    return '<input type="hidden" name="_token" value="' . e($token) . '">';
}

function csrf_token(): string
{
    return Session::getInstance()->get('csrf_token', '');
}

function url(string $path = ''): string
{
    $base = rtrim(config('app.url', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

function back(): void
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '/';
    redirect($referer);
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function today(): string
{
    return date('Y-m-d');
}

function format_currency(float $amount, ?string $currency = null): string
    {
    $currency = $currency ?? config('payment.currency', 'BDT');
    $symbol = match($currency) {
        'BDT' => '৳',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        default => $currency . ' ',
    };
    return $symbol . number_format($amount, 2);
}

function generate_invoice_number(string $prefix = 'INV'): string
{
    return $prefix . '-' . date('Ymd') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
}

function generate_admission_number(): string
{
    return 'ADM-' . date('Y') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
}

function generate_application_number(): string
{
    return 'APP-' . date('Y') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
}

function ms_unit_label(int $ms): string
{
    if ($ms < 1000) return $ms . 'ms';
    if ($ms < 60000) return round($ms / 1000, 1) . 's';
    if ($ms < 3600000) return round($ms / 60000, 1) . 'm';
    return round($ms / 3600000, 1) . 'h';
}

function dashboard_help_section_for_route(string $route): ?string
{
    $map = [
        'dashboard'           => 'overview',
        'students'            => 'students',
        'teachers'            => 'teachers',
        'classes'             => 'academics',
        'exams'               => 'exams',
        'fees'                => 'fees',
        'attendance'          => 'attendance',
        'admissions'          => 'admissions',
        'payments'            => 'payments',
        'reports'             => 'reports',
        'settings'            => 'settings',
    ];
    foreach ($map as $key => $section) {
        if (str_contains($route, $key)) return $section;
    }
    return null;
}

function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text);
}

function flash(string $key): ?string
{
    return Session::getInstance()->getFlash($key);
}

function flash_all(): array
{
    return Session::getInstance()->flashAll();
}

function upload_file(array $file, string $directory, array $allowedTypes = []): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) return null;

    if (!empty($allowedTypes)) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedTypes)) return null;
    }

    $uploadDir = __DIR__ . '/../public/uploads/' . $directory;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
    $destination = $uploadDir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return 'uploads/' . $directory . '/' . $filename;
    }
    return null;
}

function truncate(string $text, int $length = 100): string
{
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

function time_ago(string $datetime): string
{
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $time);
}

function dashboard_ui(string $key, ?string $locale = null): string
{
    static $strings = null;
    if ($strings === null) {
        $locale = $locale ?? ($_SESSION['locale'] ?? config('app.locale', 'en'));
        $file = __DIR__ . '/../lang/' . $locale . '/dashboard.php';
        if (!file_exists($file)) {
            $file = __DIR__ . '/../lang/en/dashboard.php';
        }
        $strings = require $file;
    }
    $keys = explode('.', $key);
    $value = $strings;
    foreach ($keys as $k) {
        if (!isset($value[$k])) return $key;
        $value = $value[$k];
    }
    return (string) $value;
}
