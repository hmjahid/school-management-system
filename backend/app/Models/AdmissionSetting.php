<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionSetting extends Model
{
    protected $table = 'admission_settings';

    protected $fillable = [
        'is_open',
        'closed_message_en',
        'closed_message_bn',
    ];

    protected $casts = [
        'is_open' => 'boolean',
    ];

    protected $attributes = [
        'is_open' => true,
    ];

    /**
     * Get the singleton admission settings row. If none exists, create one
     * with default values (admissions open) and return it.
     */
    public static function getSettings(): self
    {
        $row = static::first();

        if (!$row) {
            $row = static::create(['is_open' => true]);
        }

        return $row;
    }

    /**
     * Get the localized "admissions closed" message for the current app locale.
     * Falls back to the English value, then to a built-in default.
     */
    public function getClosedMessageAttribute(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'bn') {
            $bn = trim((string) $this->closed_message_bn);
            if ($bn !== '') {
                return $bn;
            }
        }

        $en = trim((string) $this->closed_message_en);
        if ($en !== '') {
            return $en;
        }

        return $locale === 'bn'
            ? 'বর্তমানে ভর্তি কার্যক্রম বন্ধ আছে। অনুগ্রহ করে পরে আবার দেখুন।'
            : 'Admissions are not currently open. Please check back later.';
    }
}
