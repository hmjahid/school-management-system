<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal URL generator so Blade views can call url()->current(), url()->full()
 * and url('path') like Laravel.
 */
class UrlGenerator
{
    public function current(): string
    {
        return $this->full();
    }

    public function full(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return $scheme . '://' . $host . $uri;
    }

    public function previous(string $fallback = '/'): string
    {
        return $_SERVER['HTTP_REFERER'] ?? $fallback;
    }

    public function to(string $path = ''): string
    {
        return url($path);
    }

    public function asset(string $path): string
    {
        return asset($path);
    }

    public function route(string $name, mixed $parameters = []): string
    {
        return route($name, $parameters);
    }

    public function __toString(): string
    {
        return rtrim((string) config('app.url', ''), '/');
    }
}
