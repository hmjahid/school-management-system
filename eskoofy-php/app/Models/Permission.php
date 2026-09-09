<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Permission extends Model
{
    protected static string $table = 'permissions';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'name', 'description', 'guard_name',
    ];
}
