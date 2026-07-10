<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WebsiteSetting extends Model
{
    use LogsActivity;

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('website_settings.first'));
        static::deleted(fn () => Cache::forget('website_settings.first'));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['school_name', 'school_name_bn', 'tagline', 'tagline_bn', 'email', 'phone', 'address', 'default_locale', 'meta_title', 'meta_description'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('school_settings');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'school_name',
        'school_name_bn',
        'tagline',
        'tagline_bn',
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
        'show_facebook',
        'twitter_url',
        'show_twitter',
        'instagram_url',
        'show_instagram',
        'linkedin_url',
        'show_linkedin',
        'youtube_url',
        'show_youtube',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'timezone',
        'date_format',
        'time_format',
        'default_locale',
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
        'show_facebook' => 'boolean',
        'show_instagram' => 'boolean',
        'show_twitter' => 'boolean',
        'show_youtube' => 'boolean',
        'show_linkedin' => 'boolean',
    ];

    /**
     * Default attribute values for new instances.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'default_locale' => 'en',
        'show_facebook' => true,
        'show_instagram' => true,
        'show_twitter' => true,
        'show_youtube' => true,
        'show_linkedin' => true,
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
     * The current application locale, used by localized accessors.
     * Falls back to the framework default when the app is not yet bootstrapped.
     */
    protected function currentLocale(): string
    {
        try {
            return app()->getLocale();
        } catch (\Throwable $e) {
            return (string) config('app.locale', 'en');
        }
    }

    /**
     * Pick the value from a (base, *_bn) pair for the current locale.
     * Falls back to the base value when the localized value is empty.
     */
    protected function localizedString(?string $base, ?string $localized): string
    {
        if ($this->currentLocale() === 'bn') {
            $trimmed = trim((string) $localized);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return (string) ($base ?? '');
    }

    /**
     * School name localized to the current app locale.
     */
    public function getLocalizedSchoolNameAttribute(): string
    {
        return $this->localizedString($this->school_name, $this->school_name_bn);
    }

    /**
     * Tagline localized to the current app locale.
     */
    public function getLocalizedTaglineAttribute(): string
    {
        return $this->localizedString($this->tagline, $this->tagline_bn);
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

    /**
     * The default language shown to first-time public-site visitors.
     * Falls back to the framework's app.locale config when unset or invalid.
     */
    public function resolvedDefaultLocale(): string
    {
        $supported = (array) config('school.supported_locales', ['en']);
        $value = (string) ($this->default_locale ?: config('app.locale', 'en'));

        return in_array($value, $supported, true) ? $value : ($supported[0] ?? 'en');
    }
}
