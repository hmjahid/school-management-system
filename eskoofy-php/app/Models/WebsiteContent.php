<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class WebsiteContent extends Model
{
    protected static string $table = 'website_contents';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'page', 'title', 'title_en', 'title_bn', 'content', 'content_en',
        'content_bn', 'cms_input_mode', 'meta_description',
        'meta_description_en', 'meta_description_bn', 'meta_keywords',
        'images', 'is_active', 'settings',
    ];

    protected array $casts = [
        'content' => 'json',
        'content_en' => 'json',
        'content_bn' => 'json',
        'images' => 'json',
        'settings' => 'json',
        'is_active' => 'boolean',
    ];
}
