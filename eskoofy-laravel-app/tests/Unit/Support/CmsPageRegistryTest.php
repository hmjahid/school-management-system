<?php

namespace Tests\Unit\Support;

use App\Support\CmsPageRegistry;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CmsPageRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cms_pages' => [
            'about' => ['group' => 'content', 'label' => 'About Us'],
            'contact' => ['group' => 'content', 'label' => 'Contact'],
            'home' => ['group' => 'system', 'label' => 'Home'],
        ]]);
    }

    #[Test]
    public function it_returns_all_registered_pages(): void
    {
        $all = CmsPageRegistry::all();

        $this->assertArrayHasKey('about', $all);
        $this->assertArrayHasKey('contact', $all);
        $this->assertArrayHasKey('home', $all);
    }

    #[Test]
    public function it_filters_content_pages_only(): void
    {
        $content = CmsPageRegistry::contentPages();

        $this->assertArrayHasKey('about', $content);
        $this->assertArrayHasKey('contact', $content);
        $this->assertArrayNotHasKey('home', $content);
    }

    #[Test]
    public function it_builds_label_map(): void
    {
        $labels = CmsPageRegistry::labels();

        $this->assertEquals('About Us', $labels['about']);
        $this->assertEquals('Contact', $labels['contact']);
    }

    #[Test]
    public function it_uses_slugified_title_when_label_missing(): void
    {
        config(['cms_pages' => ['our-history' => ['group' => 'content']]]);

        $labels = CmsPageRegistry::labels();

        $this->assertEquals('Our history', $labels['our-history']);
    }

    #[Test]
    public function it_returns_page_definition_by_slug(): void
    {
        $this->assertEquals(['group' => 'content', 'label' => 'About Us'], CmsPageRegistry::get('about'));
    }

    #[Test]
    public function it_returns_null_for_unknown_slug(): void
    {
        $this->assertNull(CmsPageRegistry::get('does-not-exist'));
    }

    #[Test]
    public function it_reports_existence(): void
    {
        $this->assertTrue(CmsPageRegistry::exists('about'));
        $this->assertFalse(CmsPageRegistry::exists('nope'));
    }
}
