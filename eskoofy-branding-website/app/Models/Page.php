<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * CMS page rows — one per public route (`route` unique). Per-locale content and
 * SEO live in `*_en` / `*_bn` columns; shared SEO (canonical, hreflang, JSON-LD)
 * is locale-independent. NULL columns mean "use the translated template default".
 */
class Page extends Model
{
    protected static string $table = 'pages';
    protected static bool $softDeletes = true;

    /** Active, non-deleted page row for a public path (with parent-segment fallback). */
    public static function forPath(string $path): ?array
    {
        $path = '/' . trim($path !== '' ? $path : '/', '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $candidates = [$path];
        $trimmed = trim($path, '/');
        if ($trimmed !== '') {
            $segments = explode('/', $trimmed);
            while (count($segments) > 1) {
                array_pop($segments);
                $candidates[] = '/' . implode('/', $segments);
            }
        }

        foreach ($candidates as $candidate) {
            $row = self::db()->fetch(
                "SELECT * FROM pages WHERE route = ? AND status = 'active' AND deleted_at IS NULL LIMIT 1",
                [$candidate]
            );
            if (is_array($row)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Expand a raw row into locale-resolved keys + shared SEO.
     *
     * @return array<string, mixed>
     */
    public static function localized(array $row, string $locale): array
    {
        $pick = static function (array $row, string $field) use ($locale): ?string {
            $value = $row[$field . '_' . $locale] ?? null;
            if ($value === null || $value === '') {
                // Fall back to English when the locale column is empty.
                $value = $row[$field . '_en'] ?? null;
            }

            return $value === '' ? null : $value;
        };

        return [
            'id'               => $row['id'] ?? null,
            'name'             => $row['name'] ?? '',
            'route'            => $row['route'] ?? '',
            'status'           => $row['status'] ?? 'active',
            'noindex'          => (int) ($row['noindex'] ?? 0) === 1,
            'title'            => $pick($row, 'title'),
            'heading'          => $pick($row, 'heading'),
            'intro'            => $pick($row, 'intro'),
            'content'          => $pick($row, 'content'),
            'meta_title'       => $pick($row, 'meta_title'),
            'meta_description' => $pick($row, 'meta_description'),
            'canonical'        => ($row['canonical'] ?? '') !== '' ? $row['canonical'] : null,
            'hreflang_en'      => ($row['hreflang_en'] ?? '') !== '' ? $row['hreflang_en'] : null,
            'hreflang_bn'      => ($row['hreflang_bn'] ?? '') !== '' ? $row['hreflang_bn'] : null,
            'json_schema'      => ($row['json_schema'] ?? '') !== '' ? $row['json_schema'] : null,
        ];
    }

    /** @return list<string> */
    public static function routes(): array
    {
        return array_map(
            static fn (array $row): string => (string) $row['route'],
            self::db()->fetchAll("SELECT route FROM pages WHERE deleted_at IS NULL ORDER BY sort_order ASC, id ASC")
        );
    }
}
