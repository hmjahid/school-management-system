<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Role extends Model
{
    protected static string $table = 'roles';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'name', 'description', 'guard_name',
    ];

    protected array $hidden = ['guard_name'];
}
