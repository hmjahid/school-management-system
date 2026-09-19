<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class PostCategory extends Model
{
    protected static string $table = 'post_categories';

    public static function active(): array
    {
        return self::db()->fetchAll(
            "SELECT * FROM post_categories WHERE active = 1 ORDER BY sort_order ASC, name ASC"
        );
    }

    public static function bySlug(string $slug): ?array
    {
        $row = self::db()->fetch("SELECT * FROM post_categories WHERE slug = ? AND active = 1 LIMIT 1", [$slug]);

        return is_array($row) ? $row : null;
    }
}