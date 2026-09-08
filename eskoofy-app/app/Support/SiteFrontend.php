<?php

namespace App\Support;

use App\Models\WebsiteContent;
use Illuminate\Support\Facades\Schema;

class SiteFrontend
{
    /**
     * Base UI copy for the current locale (lang/{locale}/site_frontend.php), merged from English
     * then overridden by the locale file when not English.
     *
     * @return array<string, mixed>
     */
    public static function defaultsForLocale(): array
    {
        $enPath = lang_path('en/site_frontend.php');
        $defaults = is_file($enPath) ? (require $enPath) : [];
        if (! is_array($defaults)) {
            $defaults = [];
        }

        $locale = app()->getLocale();
        if ($locale !== 'en') {
            $locPath = lang_path($locale.'/site_frontend.php');
            if (is_file($locPath)) {
                $localized = require $locPath;
                if (is_array($localized)) {
                    $defaults = array_replace_recursive($defaults, $localized);
                }
            }
        }

        return $defaults;
    }

    /**
     * Merges locale defaults with optional CMS overrides (Dashboard → CMS → page slug: site-ui).
     *
     * @return array<string, mixed>
     */
    public static function merged(): array
    {
        $defaults = self::defaultsForLocale();

        if (! Schema::hasTable('website_contents')) {
            return $defaults;
        }

        $row = WebsiteContent::query()->where('page', 'site-ui')->first();
        if (! $row || ! $row->is_active) {
            return $defaults;
        }

        $custom = $row->localizedPayload();
        if (! is_array($custom)) {
            return $defaults;
        }

        return array_replace_recursive($defaults, $custom);
    }
}
