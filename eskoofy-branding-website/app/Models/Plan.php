<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Plan extends Model
{
    protected static string $table = 'plans';

    public static function activeFor(string $product): array
    {
        return self::db()->fetchAll(
            "SELECT * FROM plans WHERE product = ? AND active = 1 ORDER BY sort_order ASC",
            [$product]
        );
    }
}
