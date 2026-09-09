<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class WebsiteMedia extends Model
{
    protected static string $table = 'website_media';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'title', 'category', 'file_path', 'mime_type', 'file_size',
    ];

    protected array $casts = [
        'file_size' => 'integer',
    ];
}
