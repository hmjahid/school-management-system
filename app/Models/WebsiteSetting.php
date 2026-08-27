<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
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
        'footer_logo_path',
        'footer_logo_dark_path',
        'og_image_path',
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
        'section_visibility',
        'maintenance_mode',
        'maintenance_message',
        'send_absence_sms',
        'absence_sms_template',
        'sms_sender_id',
        'theme_primary_color',
        'theme_secondary_color',
        'theme_font_family',
        'theme_border_radius',
        'bkash_merchant_number',
        'bkash_api_key',
        'bkash_api_secret',
        'bkash_username',
        'bkash_password',
        'bkash_app_key',
        'bkash_app_secret',
        'bkash_sandbox',
        'nagad_merchant_number',
        'currency',
        'default_payment_method',
        'theme_header_style',
        'theme_footer_style',
        'theme_button_style',
        'theme_section_spacing',
        'theme_style',
        'academic_start_month',
        'student_id_prefix',
        'website_url',
        'footer_description',
        'twilio_sid',
        'twilio_auth_token',
        'twilio_from_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'established_year' => 'integer',
        'opening_hours' => 'array',
        'section_visibility' => 'array',
        'maintenance_mode' => 'boolean',
        'show_facebook' => 'boolean',
        'show_instagram' => 'boolean',
        'show_twitter' => 'boolean',
        'show_youtube' => 'boolean',
        'show_linkedin' => 'boolean',
        'send_absence_sms' => 'boolean',
        'bkash_sandbox' => 'boolean',
        'academic_start_month' => 'integer',
        'bkash_merchant_number' => 'encrypted',
        'bkash_api_key' => 'encrypted',
        'bkash_api_secret' => 'encrypted',
        'bkash_username' => 'encrypted',
        'bkash_password' => 'encrypted',
        'bkash_app_key' => 'encrypted',
        'bkash_app_secret' => 'encrypted',
        'twilio_sid' => 'encrypted',
        'twilio_auth_token' => 'encrypted',
        'twilio_from_number' => 'encrypted',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'bkash_merchant_number',
        'bkash_api_key',
        'bkash_api_secret',
        'bkash_username',
        'bkash_password',
        'bkash_app_key',
        'bkash_app_secret',
        'twilio_sid',
        'twilio_auth_token',
        'twilio_from_number',
    ];

    /**
     * Default attribute values for new instances.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'default_locale' => 'en',
        'theme_style' => 'default',
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
        'footer_logo_url',
        'footer_logo_dark_url',
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
        return $this->logo_path ? url('storage/'.ltrim($this->logo_path, '/')) : null;
    }

    public function getFooterLogoUrlAttribute(): ?string
    {
        return $this->footer_logo_path ? url('storage/'.ltrim($this->footer_logo_path, '/')) : null;
    }

    public function getFooterLogoDarkUrlAttribute(): ?string
    {
        return $this->footer_logo_dark_path ? url('storage/'.ltrim($this->footer_logo_dark_path, '/')) : null;
    }

    public function getOgImageUrlAttribute(): ?string
    {
        return $this->og_image_path ? url('storage/'.ltrim($this->og_image_path, '/')) : null;
    }

    /**
     * Get the URL to the favicon.
     *
     * @return string|null
     */
    public function getFaviconUrlAttribute()
    {
        return $this->favicon_path ? url('storage/'.ltrim($this->favicon_path, '/')) : null;
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
        return static::first() ?? new static;
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
