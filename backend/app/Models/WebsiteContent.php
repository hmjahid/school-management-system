<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WebsiteContent extends Model
{
    protected $fillable = [
        'page',
        'title',
        'content',
        'meta_description',
        'meta_keywords',
        'images',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'content' => 'array',
        'images' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get content by page name
     *
     * @param string $page
     * @return WebsiteContent
     */
    public static function getContent(string $page, array $default = [])
    {
        return self::where('page', $page)->first() ?? new self([
            'page' => $page,
            'title' => ucfirst(str_replace('-', ' ', $page)),
            'content' => $default,
            'is_active' => true,
        ]);
    }

    /**
     * Get image URL for a given path
     */
    public function getImageUrl($path)
    {
        return $path ? Storage::url($path) : null;
    }

    /**
     * Get all active pages
     */
    public static function getActivePages()
    {
        return self::where('is_active', true)
            ->pluck('page')
            ->toArray();
    }
}
