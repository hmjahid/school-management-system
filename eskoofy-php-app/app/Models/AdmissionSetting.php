<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class AdmissionSetting extends Model
{
    protected static string $table = 'admission_settings';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'is_open', 'closed_message_en', 'closed_message_bn', 'admission_fee',
        'payment_number', 'payment_instructions_en', 'payment_instructions_bn',
        'notice_en', 'notice_bn', 'display_year', 'bar_title_en', 'bar_title_bn',
    ];

    protected array $casts = [
        'is_open' => 'boolean',
        'admission_fee' => 'float',
    ];

    public static function getSettings(): static
    {
        try {
            return static::query()->first() ?? new static();
        } catch (\Throwable) {
            return new static();
        }
    }

    public function getPaymentInstructionsAttribute(): string
    {
        $locale = app()->getLocale();
        if ($locale === 'bn') {
            $bn = trim((string) ($this->attributes['payment_instructions_bn'] ?? ''));
            if ($bn !== '') {
                return $bn;
            }
        }

        $en = trim((string) ($this->attributes['payment_instructions_en'] ?? ''));

        return $en !== '' ? $en : ($locale === 'bn'
            ? 'নির্ধারিত পেমেন্ট নম্বরে টাকা পাঠিয়ে ট্রানজেকশন আইডি সংরক্ষণ করুন।'
            : 'Send the fee to the configured payment number and keep your transaction ID.');
    }

    public function getClosedMessageAttribute(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'bn') {
            $bn = trim((string) ($this->attributes['closed_message_bn'] ?? ''));
            if ($bn !== '') {
                return $bn;
            }
        }

        $en = trim((string) ($this->attributes['closed_message_en'] ?? ''));
        if ($en !== '') {
            return $en;
        }

        return $locale === 'bn'
            ? 'বর্তমানে ভর্তি কার্যক্রম বন্ধ আছে। অনুগ্রহ করে পরে আবার দেখুন।'
            : 'Admissions are not currently open. Please check back later.';
    }

    public function getNoticeAttribute(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'bn') {
            $bn = trim((string) ($this->attributes['notice_bn'] ?? ''));
            if ($bn !== '') {
                return $bn;
            }
        }

        return trim((string) ($this->attributes['notice_en'] ?? ''));
    }
}
