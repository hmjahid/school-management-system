<?php
declare(strict_types=1);

use App\Core\Session;
use App\Core\Database;

function config(string $key, mixed $default = null): mixed
{
    static $merged = null;
    if ($merged === null) {
        $base = require __DIR__ . '/../../config/app.php';

        $school = array_replace(
            $base['school'] ?? [],
            is_file(__DIR__ . '/../../config/school.php') ? require __DIR__ . '/../../config/school.php' : []
        );
        if (isset($school['google_maps_embed_url'])) {
            $school['map_embed_url'] = $school['google_maps_embed_url'];
        }
        $school['description'] = $school['description'] ?? $school['tagline'] ?? '';
        $school['motto'] = $school['motto'] ?? $school['tagline'] ?? '';

        $currency = 'BDT';
        if (is_file(__DIR__ . '/../../config/payment.php')) {
            $paymentConfig = require __DIR__ . '/../../config/payment.php';
            $currency = $paymentConfig['currency'] ?? $currency;
        }
        $symbol = match ($currency) {
            'BDT' => '৳',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            default => $currency . ' ',
        };

        $merged = array_replace($base, [
            'app'          => $base,
            'school'       => $school,
            'eskoolfy'     => is_file(__DIR__ . '/../../config/eskoolfy.php') ? require __DIR__ . '/../../config/eskoolfy.php' : [],
            'payment'      => $paymentConfig ?? [],
            'sms'          => is_file(__DIR__ . '/../../config/sms.php') ? require __DIR__ . '/../../config/sms.php' : [],
            'currency'     => ['symbol' => $symbol, 'currency' => $currency],
            'database'     => ['default' => $_ENV['DB_CONNECTION'] ?? 'mysql'],
            'queue'        => ['default' => $_ENV['QUEUE_CONNECTION'] ?? 'database'],
            'mail'         => ['default' => $_ENV['MAIL_MAILER'] ?? 'log'],
            'cache'        => ['default' => $_ENV['CACHE_STORE'] ?? 'file'],
            'filesystems'  => ['default' => 'public'],
        ]);
    }

    $keys = explode('.', $key);
    $value = $merged;
    foreach ($keys as $k) {
        if (!is_array($value) || !array_key_exists($k, $value)) {
            return $default;
        }
        $value = $value[$k];
    }
    return $value;
}

function site_ui(string $key, mixed $default = null): mixed
{
    static $strings = [];
    $locale = $_SESSION['locale'] ?? config('app.locale', 'en');
    if (!isset($strings[$locale])) {
        $file = __DIR__ . '/../../lang/' . $locale . '/site_frontend.php';
        if (!file_exists($file)) {
            $file = __DIR__ . '/../../lang/en/site_frontend.php';
        }
        $strings[$locale] = require $file;
    }
    $value = $strings[$locale];
    foreach (explode('.', $key) as $k) {
        if (!is_array($value) || !array_key_exists($k, $value)) {
            return $default ?? $key;
        }
        $value = $value[$k];
    }
    return $value;
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

function url(?string $path = null): mixed
{
    static $generator = null;
    if ($path === null) {
        return $generator ??= new \App\Core\UrlGenerator();
    }
    $base = rtrim(config('app.url', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url($path);
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

function now(): \App\Core\Support\Carbon
{
    return new \App\Core\Support\Carbon();
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

function dashboard_ui(string $key, mixed $default = null): mixed
{
    static $strings = [];
    $locale = $_SESSION['locale'] ?? config('app.locale', 'en');
    if (!isset($strings[$locale])) {
        $file = __DIR__ . '/../../lang/' . $locale . '/dashboard.php';
        if (!file_exists($file)) {
            $file = __DIR__ . '/../../lang/en/dashboard.php';
        }
        $strings[$locale] = require $file;
    }
    $value = $strings[$locale];
    foreach (explode('.', $key) as $k) {
        if (!is_array($value) || !array_key_exists($k, $value)) {
            return $default ?? $key;
        }
        $value = $value[$k];
    }
    return $value;
}

if (!function_exists('auth')) {
    function auth(): object
    {
        return new class {
            public function user(): ?\App\Models\User
            {
                return \App\Core\Auth::user();
            }
            public function id(): ?int
            {
                return \App\Core\Auth::id();
            }
            public function check(): bool
            {
                return \App\Core\Auth::check();
            }
            public function role(): ?string
            {
                return \App\Core\Auth::role();
            }
            public function guest(): bool
            {
                return !\App\Core\Auth::check();
            }
        };
    }
}

if (!function_exists('route')) {
    /**
     * Named route -> URL, mirroring Laravel's route() so Blade views link the
     * same way as eskoofy-app. Unknown names fall back to the raw string.
     */
    function route(string $name, mixed $parameters = []): string
    {
        static $map = null;
        if ($map === null) {
            $map = require __DIR__ . '/../../config/routes.php';
        }
        $uri = $map[$name] ?? null;
        if ($uri === null) {
            return $name;
        }
        if (!is_array($parameters)) {
            $parameters = is_iterable($parameters) ? (array) $parameters : [$parameters];
        }
        $unused = [];
        foreach ($parameters as $key => $value) {
            if (is_int($key)) {
                $uri = preg_replace('/\{[^}]+\}/', (string) $value, $uri, 1) ?? $uri;
            } elseif (str_contains($uri, '{' . $key . '}')) {
                $uri = str_replace('{' . $key . '}', (string) $value, $uri);
            } else {
                $unused[$key] = $value;
            }
        }
        if ($unused !== []) {
            $uri .= (str_contains($uri, '?') ? '&' : '?') . http_build_query($unused);
        }
        return $uri;
    }
}

if (!function_exists('__')) {
    function __(string $key, array $replace = []): mixed
    {
        static $files = null;
        if ($files === null) {
            $locale = $_SESSION['locale'] ?? config('app.locale', 'en');
            $files = [];
            foreach (['messages', 'dashboard', 'site_frontend'] as $file) {
                $path = __DIR__ . '/../../lang/' . $locale . '/' . $file . '.php';
                if (is_file($path)) {
                    $files[$file] = require $path;
                }
            }
        }
        $parts = explode('.', $key);
        $namespace = array_shift($parts);
        $value = $files[$namespace] ?? null;
        if (!is_array($value)) {
            return $key;
        }
        foreach ($parts as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $key;
            }
            $value = $value[$segment];
        }
        if (is_string($value)) {
            foreach ($replace as $k => $v) {
                $value = str_replace(':' . $k, (string) $v, $value);
            }
        }
        return $value;
    }
}

if (!function_exists('trans')) {
    function trans(string $key, array $replace = []): mixed
    {
        return __($key, $replace);
    }
}

if (!function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (!function_exists('optional')) {
    function optional(mixed $value = null): \App\Core\Support\Optional
    {
        return new \App\Core\Support\Optional($value);
    }
}

if (!function_exists('collect')) {
    function collect(mixed $items = []): \App\Core\Support\Collection
    {
        return new \App\Core\Support\Collection($items);
    }
}

if (!function_exists('session')) {
    function session(?string $key = null, mixed $default = null): mixed
    {
        $session = \App\Core\Session::getInstance();
        if ($key === null) {
            return $session;
        }
        $value = $session->getFlash($key);
        if ($value === null) {
            $value = $session->get($key, $default);
        }
        return $value ?? $default;
    }
}

if (!function_exists('request')) {
    function request(?string $key = null, mixed $default = null): mixed
    {
        static $request = null;
        if ($request === null) {
            $request = new \App\Core\Request();
        }
        if ($key === null) {
            return $request;
        }
        return $request->get($key, $default);
    }
}

if (!function_exists('app')) {
    function app(?string $abstract = null): mixed
    {
        static $container = null;
        if ($container === null) {
            $container = new class {
                public function getLocale(): string
                {
                    $locale = $_SESSION['locale'] ?? config('app.locale', 'en');
                    return str_replace('-', '_', $locale);
                }

                public function environment(...$environments): bool|string
                {
                    $env = $_ENV['APP_ENV'] ?? 'production';
                    return $environments === [] ? $env : in_array($env, $environments, true);
                }

                public function make(string $abstract = null): mixed
                {
                    if ($abstract === \App\Core\ViewErrorBag::class) {
                        $errors = \App\Core\Session::getInstance()->getFlash('errors') ?? [];
                        return new \App\Core\ViewErrorBag(is_array($errors) ? $errors : []);
                    }
                    return null;
                }

                public function bound(string $abstract): bool
                {
                    return false;
                }

                public function runningUnitTests(): bool
                {
                    return defined('PHPUNIT_COMPOSER_INSTALL') || class_exists(\PHPUnit\Framework\TestCase::class);
                }
            };
        }
        if ($abstract === null) {
            return $container;
        }
        return $container->make($abstract);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        return match (strtolower((string) $value)) {
            'true', '(true)'  => true,
            'false', '(false)' => false,
            'null', '(null)'  => null,
            'empty', '(empty)' => '',
            default           => $value,
        };
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        $root = dirname(__DIR__, 2) . '/public';
        return $path === '' ? $root : $root . '/' . ltrim($path, '/');
    }
}

if (!function_exists('can')) {
    function can(string $ability, mixed $arguments = []): bool
    {
        return \App\Core\Gate::allows($ability, $arguments);
    }
}

if (!function_exists('blank')) {
    function blank(mixed $value): bool
    {
        if (is_null($value)) {
            return true;
        }
        if (is_string($value)) {
            return trim($value) === '';
        }
        if (is_numeric($value) || is_bool($value)) {
            return false;
        }
        if ($value instanceof \Countable) {
            return count($value) === 0;
        }
        if (is_array($value)) {
            return $value === [];
        }
        return empty($value);
    }
}

if (!function_exists('filled')) {
    function filled(mixed $value): bool
    {
        return !blank($value);
    }
}

if (!function_exists('data_get')) {
    function data_get(mixed $target, mixed $key, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $target;
        }
        if (is_array($key)) {
            $result = [];
            foreach ($key as $k) {
                $result[$k] = data_get($target, $k, $default);
            }
            return $result;
        }
        foreach (explode('.', (string) $key) as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return $default;
            }
        }
        return $target;
    }
}

if (!function_exists('array_get')) {
    function array_get(mixed $target, mixed $key, mixed $default = null): mixed
    {
        return data_get($target, $key, $default);
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $root = dirname(__DIR__, 2);
        return $path === '' ? $root : $root . '/' . ltrim($path, '/');
    }
}

if (!function_exists('now')) {
    // already defined above (returns Carbon); guard kept for safety
}

if (!function_exists('str')) {
    function str(string $value = ''): \App\Core\Support\Stringable
    {
        return new \App\Core\Support\Stringable($value);
    }
}

if (!function_exists('json_encode_unescaped')) {
    function json_encode_unescaped(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

// Blade templates expect the same globals as Laravel. Registered here (not just
// in bootstrap.php) so the PHPUnit bootstrap also gets them.
if (!class_exists('Str', false)) {
    class_alias(\App\Core\Support\Str::class, 'Str');
}
if (!class_exists('Carbon', false)) {
    class_alias(\App\Core\Support\Carbon::class, 'Carbon');
}
if (!class_exists('Optional', false)) {
    class_alias(\App\Core\Support\Optional::class, 'Optional');
}
if (!class_exists('Collection', false)) {
    class_alias(\App\Core\Support\Collection::class, 'Collection');
}
if (!class_exists('Illuminate\Support\Str', false)) {
    class_alias(\App\Core\Support\Str::class, 'Illuminate\Support\Str');
}
if (!class_exists('Illuminate\Support\Carbon', false)) {
    class_alias(\App\Core\Support\Carbon::class, 'Illuminate\Support\Carbon');
}
if (!class_exists('Illuminate\Support\Facades\Schema', false)) {
    class_alias(\App\Core\Schema::class, 'Illuminate\Support\Facades\Schema');
}
if (!class_exists('Illuminate\Support\Facades\Storage', false)) {
    class_alias(\App\Core\Storage::class, 'Illuminate\Support\Facades\Storage');
}

if (!function_exists('class_basename')) {
    function class_basename(object|string $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}
