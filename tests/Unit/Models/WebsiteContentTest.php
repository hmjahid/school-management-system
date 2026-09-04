<?php

namespace Tests\Unit\Models;

use App\Models\WebsiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsiteContentTest extends TestCase
{
    use RefreshDatabase;

    private function make(array $overrides = []): WebsiteContent
    {
        return WebsiteContent::create(array_merge([
            'page' => 'wc-'.uniqid(),
            'section' => 'hero',
            'title_en' => 'Welcome',
            'title_bn' => 'স্বাগতম',
            'content_en' => ['title' => 'Welcome', 'subtitle' => 'School'],
            'content_bn' => ['title' => 'স্বাগতম'],
            'is_active' => true,
        ], $overrides));
    }

    #[Test]
    public function english_content_tree_falls_back_to_content(): void
    {
        $c = WebsiteContent::create([
            'page' => 'about',
            'content' => ['title' => 'Legacy'],
        ]);

        $this->assertEquals(['title' => 'Legacy'], $c->englishContentTree());
    }

    #[Test]
    public function english_content_tree_prefers_content_en(): void
    {
        $c = $this->make();

        $this->assertEquals(['title' => 'Welcome', 'subtitle' => 'School'], $c->englishContentTree());
    }

    #[Test]
    public function bengali_content_tree_prunes_identical_to_english(): void
    {
        $c = $this->make([
            'content_en' => ['title' => 'Welcome', 'subtitle' => 'School'],
            'content_bn' => ['title' => 'স্বাগতম', 'subtitle' => 'School'],
        ]);

        $bn = $c->bengaliContentTree();
        $this->assertEquals('স্বাগতম', $bn['title']);
        $this->assertArrayNotHasKey('subtitle', $bn);
    }

    #[Test]
    public function localized_payload_returns_en_tree_for_en_locale(): void
    {
        App::setLocale('en');

        $this->assertEquals(
            ['title' => 'Welcome', 'subtitle' => 'School'],
            $this->make()->localizedPayload()
        );
    }

    #[Test]
    public function localized_payload_for_bn_strips_untranslated_leaves(): void
    {
        App::setLocale('bn');

        $c = $this->make([
            'content_en' => ['title' => 'Welcome', 'subtitle' => 'School'],
            'content_bn' => ['title' => 'স্বাগতম', 'subtitle' => 'School'],
        ]);

        $payload = $c->localizedPayload();
        $this->assertEquals('স্বাগতম', $payload['title']);
        $this->assertArrayNotHasKey('subtitle', $payload);
    }

    #[Test]
    public function localized_title_uses_bn_in_bn_locale_with_fallback(): void
    {
        App::setLocale('bn');
        $this->assertEquals('স্বাগতম', $this->make()->localizedTitle());

        App::setLocale('en');
        $this->assertEquals('Welcome', $this->make()->localizedTitle());
    }

    #[Test]
    public function localized_meta_description_falls_back_appropriately(): void
    {
        $c = $this->make([
            'meta_description_en' => 'EN desc',
            'meta_description_bn' => 'BN desc',
        ]);

        App::setLocale('en');
        $this->assertEquals('EN desc', $c->localizedMetaDescription());

        App::setLocale('bn');
        $this->assertEquals('BN desc', $c->localizedMetaDescription());
    }

    #[Test]
    public function clone_for_public_returns_localized_clone(): void
    {
        App::setLocale('bn');
        $c = $this->make([
            'content_en' => ['title' => 'Welcome', 'subtitle' => 'School'],
            'content_bn' => ['title' => 'স্বাগতম', 'subtitle' => 'School'],
        ]);

        $clone = $c->cloneForPublic();

        $this->assertInstanceOf(WebsiteContent::class, $clone);
        $this->assertNotSame($c, $clone);
        $this->assertEquals(['title' => 'স্বাগতম'], $clone->content);
        $this->assertEquals('স্বাগতম', $clone->title);
    }

    #[Test]
    public function get_content_static_returns_instance_for_page(): void
    {
        $this->make(['page' => 'about']);

        $content = WebsiteContent::getContent('about');

        $this->assertInstanceOf(WebsiteContent::class, $content);
        $this->assertEquals('about', $content->page);
    }

    #[Test]
    public function get_content_static_creates_runtime_row_when_missing(): void
    {
        $content = WebsiteContent::getContent('missing-page');

        $this->assertInstanceOf(WebsiteContent::class, $content);
        $this->assertEquals('missing-page', $content->page);
    }
}
