<?php

namespace Tests\Unit\Models;

use App\Models\AdmissionSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdmissionSettingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function get_settings_returns_singleton_row(): void
    {
        $row = AdmissionSetting::getSettings();

        $this->assertInstanceOf(AdmissionSetting::class, $row);
        $this->assertNotNull($row->id);

        $again = AdmissionSetting::getSettings();
        $this->assertEquals($row->id, $again->id);
    }

    #[Test]
    public function closed_message_default_when_empty(): void
    {
        App::setLocale('en');
        $this->assertEquals('Admissions are not currently open. Please check back later.', AdmissionSetting::getSettings()->closed_message);

        App::setLocale('bn');
        $this->assertEquals('বর্তমানে ভর্তি কার্যক্রম বন্ধ আছে। অনুগ্রহ করে পরে আবার দেখুন।', AdmissionSetting::getSettings()->closed_message);
    }

    #[Test]
    public function closed_message_uses_configured_values(): void
    {
        AdmissionSetting::getSettings();
        AdmissionSetting::query()->update([
            'closed_message_en' => 'Closed for summer',
            'closed_message_bn' => 'গ্রীষ্মকালীন বন্ধ',
        ]);

        App::setLocale('en');
        $this->assertEquals('Closed for summer', AdmissionSetting::getSettings()->closed_message);

        App::setLocale('bn');
        $this->assertEquals('গ্রীষ্মকালীন বন্ধ', AdmissionSetting::getSettings()->closed_message);
    }

    #[Test]
    public function notice_returns_configured_notice(): void
    {
        AdmissionSetting::getSettings();
        AdmissionSetting::query()->update([
            'notice_en' => 'Apply early',
            'notice_bn' => 'তাড়াতাড়ি আবেদন করুন',
        ]);

        App::setLocale('en');
        $this->assertEquals('Apply early', AdmissionSetting::getSettings()->notice);

        App::setLocale('bn');
        $this->assertEquals('তাড়াতাড়ি আবেদন করুন', AdmissionSetting::getSettings()->notice);
    }

    #[Test]
    public function payment_instructions_returns_configured_text(): void
    {
        AdmissionSetting::getSettings();
        AdmissionSetting::query()->update([
            'payment_instructions_en' => 'Pay via bKash',
            'payment_instructions_bn' => 'বিকাশের মাধ্যমে পেমেন্ট করুন',
        ]);

        App::setLocale('en');
        $this->assertEquals('Pay via bKash', AdmissionSetting::getSettings()->payment_instructions);

        App::setLocale('bn');
        $this->assertEquals('বিকাশের মাধ্যমে পেমেন্ট করুন', AdmissionSetting::getSettings()->payment_instructions);
    }
}
