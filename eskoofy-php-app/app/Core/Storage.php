<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal Storage facade replacement (Illuminate\Support\Facades\Storage) for
 * views that resolve uploaded-file URLs.
 */
class Storage
{
    public static function disk(?string $name = null): static
    {
        return new static();
    }

    public static function url(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return url('storage/' . ltrim($path, '/'));
    }

    public static function exists(string $path): bool
    {
        return is_file(public_path('storage/' . ltrim($path, '/')));
    }

    public static function get(string $path): ?string
    {
        $full = public_path('storage/' . ltrim($path, '/'));
        return is_file($full) ? (string) file_get_contents($full) : null;
    }

    public function urlFor(string $path): string
    {
        return static::url($path);
    }

    public function __call(string $method, array $args): mixed
    {
        return null;
    }
}
