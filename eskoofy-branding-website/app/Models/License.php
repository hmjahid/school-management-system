<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class License extends Model
{
    protected static string $table = 'licenses';
    protected static bool $softDeletes = true;
}
