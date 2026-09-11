<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class WebsiteSetting extends Model
{
    protected static string $table = 'website_settings';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'school_name', 'school_name_bn', 'tagline', 'tagline_bn',
        'logo_path', 'footer_logo_path', 'footer_logo_dark_path',
        'og_image_path', 'favicon_path', 'established_year', 'address',
        'city', 'state', 'country', 'postal_code', 'phone', 'email',
        'website', 'opening_hours', 'facebook_url', 'show_facebook',
        'twitter_url', 'show_twitter', 'instagram_url', 'show_instagram',
        'linkedin_url', 'show_linkedin', 'youtube_url', 'show_youtube',
        'meta_title', 'meta_description', 'meta_keywords', 'timezone',
        'date_format', 'time_format', 'default_locale', 'section_visibility',
        'maintenance_mode', 'maintenance_message', 'send_absence_sms',
        'absence_sms_template', 'sms_sender_id', 'theme_primary_color',
        'theme_secondary_color', 'theme_font_family', 'theme_border_radius',
        'bkash_merchant_number', 'bkash_api_key', 'bkash_api_secret',
        'bkash_username', 'bkash_password', 'bkash_app_key',
        'bkash_app_secret', 'bkash_sandbox', 'nagad_merchant_number',
        'currency', 'default_payment_method', 'theme_header_style',
        'theme_footer_style', 'theme_button_style', 'theme_section_spacing',
        'theme_style', 'academic_start_month', 'student_id_prefix',
        'website_url', 'footer_description', 'twilio_sid',
        'twilio_auth_token', 'twilio_from_number', 'mail_enabled',
        'mail_driver', 'mail_host', 'mail_port', 'mail_username',
        'mail_password', 'mail_encryption', 'mail_from_address',
        'mail_from_name', 'mail_test_recipient',
    ];

    protected array $hidden = [
        'bkash_merchant_number', 'bkash_api_key', 'bkash_api_secret',
        'bkash_username', 'bkash_password', 'bkash_app_key',
        'bkash_app_secret', 'twilio_sid', 'twilio_auth_token',
        'twilio_from_number', 'mail_username', 'mail_password',
    ];

    protected array $casts = [
        'established_year' => 'integer',
        'opening_hours' => 'json',
        'section_visibility' => 'json',
        'maintenance_mode' => 'boolean',
        'show_facebook' => 'boolean',
        'show_instagram' => 'boolean',
        'show_twitter' => 'boolean',
        'show_youtube' => 'boolean',
        'show_linkedin' => 'boolean',
        'send_absence_sms' => 'boolean',
        'bkash_sandbox' => 'boolean',
        'academic_start_month' => 'integer',
        'mail_enabled' => 'boolean',
    ];

    public static function getSettings(): static
    {
        try {
            return static::query()->first() ?? new static();
        } catch (\Throwable) {
            return new static();
        }
    }

    public function resolvedDefaultLocale(): string
    {
        $supported = (array) config('school.supported_locales', ['en']);
        $value = (string) ($this->default_locale ?: config('app.locale', 'en'));
        return in_array($value, $supported, true) ? $value : ($supported[0] ?? 'en');
    }

    public function getLogoUrlAttribute(): ?string
    {
        $path = $this->attributes['logo_path'] ?? null;
        return $path ? url('storage/' . ltrim($path, '/')) : null;
    }

    public function getLocalizedSchoolNameAttribute(): string
    {
        if (app()->getLocale() === 'bn' && !empty($this->attributes['school_name_bn'] ?? '')) {
            return (string) $this->attributes['school_name_bn'];
        }
        return (string) ($this->attributes['school_name'] ?? '');
    }

    public function getLocalizedTaglineAttribute(): string
    {
        if (app()->getLocale() === 'bn' && !empty($this->attributes['tagline_bn'] ?? '')) {
            return (string) $this->attributes['tagline_bn'];
        }
        return (string) ($this->attributes['tagline'] ?? '');
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->attributes['address'] ?? '',
            $this->attributes['city'] ?? '',
            $this->attributes['state'] ?? '',
            $this->attributes['postal_code'] ?? '',
            $this->attributes['country'] ?? '',
        ]);
        return implode(', ', $parts);
    }

    public function getFaviconUrlAttribute(): ?string
    {
        $path = $this->attributes['favicon_path'] ?? null;
        return $path ? url('storage/' . ltrim($path, '/')) : null;
    }

    public function getOgImageUrlAttribute(): ?string
    {
        $path = $this->attributes['og_image_path'] ?? null;
        return $path ? url('storage/' . ltrim($path, '/')) : null;
    }
}
