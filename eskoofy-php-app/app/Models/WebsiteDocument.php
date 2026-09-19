<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class WebsiteDocument extends Model
{
    protected static string $table = 'website_documents';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'title', 'description', 'file_path', 'category', 'is_published',
        'published_at', 'downloads', 'created_by',
    ];

    protected array $casts = [
        'is_published' => 'boolean',
        'downloads' => 'integer',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}