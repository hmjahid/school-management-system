<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WebsiteSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'school_name',
        'tagline',
        'logo_path',
        'favicon_path',
        'established_year',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'email',
        'website',
        'opening_hours',
        'facebook_url',
        'twitter_url',
        'instagram_url',
        'linkedin_url',
        'youtube_url',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'timezone',
        'date_format',
        'time_format',
        'maintenance_mode',
        'maintenance_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'established_year' => 'integer',
        'opening_hours' => 'array',
        'maintenance_mode' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'logo_url',
        'favicon_url',
        'full_address',
    ];

    /**
     * Get the URL to the logo.
     *
     * @return string|null
     */
    public function getLogoUrlAttribute()
    {
        return $this->logo_path ? Storage::url($this->logo_path) : null;
    }

    /**
     * Get the URL to the favicon.
     *
     * @return string|null
     */
    public function getFaviconUrlAttribute()
    {
        return $this->favicon_path ? Storage::url($this->favicon_path) : null;
    }

    /**
     * Get the full address as a single string.
     *
     * @return string
     */
    public function getFullAddressAttribute()
    {
        $parts = [
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ];

        return implode(', ', array_filter($parts));
    }

    /**
     * Get the website settings.
     * If no settings exist, return a new instance.
     *
     * @return \App\Models\WebsiteSetting
     */
    public static function getSettings()
    {
        return static::first() ?? new static();
    }
}
