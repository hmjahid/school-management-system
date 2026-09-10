<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Post extends Model
{
    protected static string $table = 'posts';

    public static function published(int $limit = 12, int $offset = 0): array
    {
        return self::db()->fetchAll(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM posts p
             LEFT JOIN post_categories c ON c.id = p.category_id
             WHERE status = 'published' AND deleted_at IS NULL
             ORDER BY published_at DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
    }

    public static function countPublished(): int
    {
        return (int) self::db()->fetch(
            "SELECT COUNT(*) AS total FROM posts WHERE status = 'published' AND deleted_at IS NULL"
        )['total'];
    }

    public static function latest(int $limit = 3): array
    {
        return self::published($limit);
    }

    public static function bySlug(string $slug): ?array
    {
        $row = self::db()->fetch(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM posts p
             LEFT JOIN post_categories c ON c.id = p.category_id
             WHERE slug = ? AND deleted_at IS NULL LIMIT 1",
            [$slug]
        );

        return is_array($row) ? $row : null;
    }

    public static function publishedByCategory(string $slug, int $limit = 12, int $offset = 0): array
    {
        $category = PostCategory::bySlug($slug);
        if (!$category) {
            return [];
        }

        return self::db()->fetchAll(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM posts p
             LEFT JOIN post_categories c ON c.id = p.category_id
             WHERE status = 'published' AND deleted_at IS NULL AND category_id = ?
             ORDER BY published_at DESC
             LIMIT {$limit} OFFSET {$offset}",
            [(int) $category['id']]
        );
    }

    public static function countPublishedByCategory(string $slug): int
    {
        $category = PostCategory::bySlug($slug);
        if (!$category) {
            return 0;
        }

        return (int) self::db()->fetch(
            "SELECT COUNT(*) AS total FROM posts WHERE status = 'published' AND deleted_at IS NULL AND category_id = ?",
            [(int) $category['id']]
        )['total'];
    }
}