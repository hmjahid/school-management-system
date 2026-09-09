<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Gallery extends Model
{
    protected static string $table = 'galleries';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'title', 'description', 'image_path', 'category', 'is_published',
    ];

    protected array $casts = [
        'is_published' => 'boolean',
    ];
}
