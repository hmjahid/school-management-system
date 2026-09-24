<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ContactMessage extends Model
{
    protected static string $table = 'contact_messages';

    public static function countUnread(): int
    {
        return (int) static::db()->fetch(
            "SELECT COUNT(*) AS total FROM contact_messages WHERE read_at IS NULL"
        )['total'];
    }
}
