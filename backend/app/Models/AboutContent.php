<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AboutContent extends Model
{
    protected $fillable = [
        'school_name',
        'tagline',
        'logo_path',
        'favicon_path',
        'established_year',
        'about_summary',
        'mission',
        'vision',
        'history',
        'core_values',
        'contact_info',
        'social_links',
        'address',
        'phone',
        'email',
        'website',
    ];

    protected $casts = [
        'core_values' => 'array',
        'contact_info' => 'array',
        'social_links' => 'array',
        'established_year' => 'integer',
    ];

    protected $appends = ['logo_url', 'favicon_url'];

    public function getLogoUrlAttribute()
    {
        return $this->logo_path ? Storage::url($this->logo_path) : null;
    }

    public function getFaviconUrlAttribute()
    {
        return $this->favicon_path ? Storage::url($this->favicon_path) : null;
    }

    public static function getContent()
    {
        return self::first() ?? new self();
    }
}
