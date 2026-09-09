<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Announcement extends Model
{
    protected static string $table = 'announcements';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'title', 'title_bn', 'body', 'body_bn', 'audience', 'display_target',
        'is_published', 'starts_at', 'ends_at',
    ];

    protected array $casts = [
        'audience' => 'json',
        'is_published' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}
