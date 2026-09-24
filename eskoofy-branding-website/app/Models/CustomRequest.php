<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/** Custom development / modification / extra-feature order requests. */
class CustomRequest extends Model
{
    protected static string $table = 'custom_requests';

    public const PRODUCTS = ['app', 'theme', 'php', 'node', 'multi'];
    public const TYPES = ['custom_development', 'modification', 'extra_feature', 'other'];
    public const STATUSES = ['new', 'in_review', 'quoting', 'approved', 'declined', 'done'];

    public static function countUnread(): int
    {
        return (int) self::db()->fetch(
            "SELECT COUNT(*) AS total FROM custom_requests WHERE read_at IS NULL"
        )['total'];
    }

    /** @return list<string> */
    public static function statuses(): array
    {
        return self::STATUSES;
    }
}
