<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Activity extends Model
{
    protected static string $table = 'activities';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'user_id', 'type', 'title', 'message', 'icon', 'color', 'properties',
        'read_at',
    ];

    protected array $casts = [
        'properties' => 'json',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
