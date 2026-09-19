<?php

namespace Tests\Unit\Models;

use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsiteSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function makeSetting(array $overrides = []): WebsiteSetting
    {
        return WebsiteSetting::create(array_merge([
            'school_name' => 'Greenfield School',
            'address' => '1 Main St',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'country' => 'Bangladesh',
            'postal_code' => '1207',
            'phone' => '01700000000',
            'email' => 'info'.uniqid().'@example.com',
            'established_year' => 1995,
        ], $overrides));
    }

    #[Test]
    public function it_persists_required_columns(): void
    {
        $setting = $this->makeSetting();

        $this->assertDatabaseHas('website_settings', [
            'school_name' => 'Greenfield School',
            'city' => 'Dhaka',
            'email' => $setting->email,
            'established_year' => 1995,
        ]);
    }

    #[Test]
    public function its_localized_school_name_returns_english_by_default(): void
    {
        $setting = $this->makeSetting([
            'school_name' => 'Greenfield School',
            'school_name_bn' => 'গ্রিনফিল্ড স্কুল',
        ]);

        $this->assertEquals('Greenfield School', $setting->localized_school_name);
    }

    #[Test]
    public function its_localized_school_name_returns_bengali_when_locale_is_bn(): void
    {
        $setting = $this->makeSetting([
            'school_name' => 'Greenfield School',
            'school_name_bn' => 'গ্রিনফিল্ড স্কুল',
        ]);

        App::setLocale('bn');
        $this->assertEquals('গ্রিনফিল্ড স্কুল', $setting->localized_school_name);
        App::setLocale('en');
    }

    #[Test]
    public function its_localized_tagline_falls_back_to_english_when_bengali_empty(): void
    {
        $setting = $this->makeSetting([
            'tagline' => 'Learn and Grow',
            'tagline_bn' => null,
        ]);

        App::setLocale('bn');
        $this->assertEquals('Learn and Grow', $setting->localized_tagline);
        App::setLocale('en');
    }

    #[Test]
    public function its_full_address_attribute_combines_parts(): void
    {
        $setting = $this->makeSetting([
            'address' => '1 Main St',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postal_code' => '1207',
            'country' => 'Bangladesh',
        ]);

        $this->assertEquals('1 Main St, Dhaka, Dhaka, 1207, Bangladesh', $setting->full_address);
    }

    #[Test]
    public function its_logo_url_accessor_returns_null_without_path(): void
    {
        $setting = $this->makeSetting(['logo_path' => null]);

        $this->assertNull($setting->logo_url);
    }

    #[Test]
    public function its_logo_url_accessor_returns_storage_url(): void
    {
        $setting = $this->makeSetting(['logo_path' => '/logos/logo.png']);

        $this->assertEquals(url('storage/logos/logo.png'), $setting->logo_url);
    }

    #[Test]
    public function its_resolved_default_locale_returns_configured_locale(): void
    {
        $setting = $this->makeSetting(['default_locale' => 'bn']);

        $this->assertEquals('bn', $setting->resolvedDefaultLocale());
    }

    #[Test]
    public function get_settings_returns_existing_record(): void
    {
        $setting = $this->makeSetting();

        $this->assertTrue(WebsiteSetting::getSettings()->is($setting));
    }

    #[Test]
    public function get_settings_returns_new_instance_when_empty(): void
    {
        $this->assertInstanceOf(WebsiteSetting::class, WebsiteSetting::getSettings());
        $this->assertNull(WebsiteSetting::getSettings()->id);
    }
}
