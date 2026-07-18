<?php

namespace App\Support;

class CmsPageRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return (array) config('cms_pages', []);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function contentPages(): array
    {
        return array_filter(self::all(), fn ($p) => ($p['group'] ?? 'content') === 'content');
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $out = [];
        foreach (self::all() as $slug => $def) {
            $out[$slug] = $def['label'] ?? ucfirst(str_replace('-', ' ', $slug));
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $slug): ?array
    {
        $pages = self::all();

        return $pages[$slug] ?? null;
    }

    public static function exists(string $slug): bool
    {
        return isset(self::all()[$slug]);
    }
}
