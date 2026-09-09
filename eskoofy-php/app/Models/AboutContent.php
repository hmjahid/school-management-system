<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class AboutContent extends Model
{
    protected static string $table = 'about_contents';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'school_name', 'tagline', 'logo_path', 'favicon_path',
        'established_year', 'about_summary', 'mission', 'vision', 'history',
        'core_values', 'contact_info', 'social_links', 'address', 'phone',
        'email', 'website',
    ];

    protected array $casts = [
        'core_values' => 'json',
        'contact_info' => 'json',
        'social_links' => 'json',
        'established_year' => 'integer',
    ];
}
