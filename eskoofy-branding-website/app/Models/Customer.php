<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Customer extends Model
{
    protected static string $table = 'customers';
    protected static bool $softDeletes = true;
    protected array $hidden = ['password', 'api_token'];

    public static function findByEmail(string $email): ?self
    {
        $row = self::db()->fetch(
            "SELECT * FROM customers WHERE email = ? AND deleted_at IS NULL LIMIT 1",
            [$email]
        );

        return $row ? new static($row) : null;
    }
}
