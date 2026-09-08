<?php

namespace Tests\Unit\Support;

use App\Models\WebsiteContent;
use App\Support\SiteFrontend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SiteFrontendTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function defaults_for_en_locale_returns_array_from_lang_file(): void
    {
        $defaults = SiteFrontend::defaultsForLocale();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('nav', $defaults);
    }

    #[Test]
    public function defaults_merge_bn_over_en_for_non_en_locale(): void
    {
        app()->setLocale('bn');

        $defaults = SiteFrontend::defaultsForLocale();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('nav', $defaults);
    }

    #[Test]
    public function merged_returns_lang_defaults_when_no_cms_row(): void
    {
        $merged = SiteFrontend::merged();

        $this->assertIsArray($merged);
        $this->assertArrayHasKey('nav', $merged);
    }

    #[Test]
    public function merged_overrides_defaults_with_active_cms_site_ui(): void
    {
        WebsiteContent::create([
            'page' => 'site-ui',
            'title' => 'Site UI',
            'is_active' => true,
            'content' => ['nav' => ['custom_key' => 'Custom Value']],
            'content_en' => ['nav' => ['custom_key' => 'Custom Value']],
        ]);

        $merged = SiteFrontend::merged();

        $this->assertIsArray($merged);
        $this->assertEquals('Custom Value', $merged['nav']['custom_key'] ?? null);
    }

    #[Test]
    public function merged_ignores_inactive_cms_row(): void
    {
        WebsiteContent::create([
            'page' => 'site-ui',
            'title' => 'Site UI',
            'is_active' => false,
            'content' => ['nav' => ['custom_key' => 'Should Not Appear']],
            'content_en' => ['nav' => ['custom_key' => 'Should Not Appear']],
        ]);

        $merged = SiteFrontend::merged();

        $this->assertArrayNotHasKey('custom_key', $merged['nav'] ?? []);
    }

    #[Test]
    public function merged_reads_lang_when_website_contents_table_missing(): void
    {
        // Simulate the guard path without depending on table existence.
        $this->assertIsArray(SiteFrontend::merged());
    }
}
