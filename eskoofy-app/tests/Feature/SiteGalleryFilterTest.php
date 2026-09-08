<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\WebsiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteGalleryFilterTest extends TestCase
{
    use RefreshDatabase;

    private function renderSiteGallery(): string
    {
        WebsiteContent::create(['page' => 'gallery', 'title' => 'Gallery', 'is_active' => true]);
        Gallery::create(['title' => 'First', 'category' => 'Field Trip', 'image_path' => 'website/gallery/1.jpg', 'is_published' => true]);
        Gallery::create(['title' => 'Second', 'category' => 'Sports Day', 'image_path' => 'website/gallery/2.jpg', 'is_published' => true]);
        Gallery::create(['title' => 'Third', 'category' => 'Field Trip', 'image_path' => 'website/gallery/3.jpg', 'is_published' => true]);

        $response = $this->get(route('site.gallery'));
        $response->assertStatus(200);

        return $response->getContent();
    }

    public function test_tabs_and_items_share_matching_slugs(): void
    {
        $html = $this->renderSiteGallery();

        preg_match_all('/data-filter="([^"]+)"/', $html, $tabMatches);
        preg_match_all('/data-category="([^"]+)"/', $html, $itemMatches);

        $tabs = array_values(array_unique($tabMatches[1]));
        $items = array_values(array_unique($itemMatches[1]));

        $this->assertContains('all', $tabs, 'All tab must exist');

        $expectedTabs = ['field-trip', 'sports-day'];
        $expectedItems = ['field-trip', 'sports-day'];

        $this->assertEquals($expectedTabs, array_values(array_diff($tabs, ['all'])), 'tab slugs');
        $this->assertEquals($expectedItems, $items, 'item slugs');
    }

    public function test_every_category_has_a_matching_tab(): void
    {
        $html = $this->renderSiteGallery();

        preg_match_all('/data-filter="([^"]+)"/', $html, $tabMatches);
        preg_match_all('/data-category="([^"]+)"/', $html, $itemMatches);

        $tabs = array_unique($tabMatches[1]);
        $items = array_unique($itemMatches[1]);

        foreach ($items as $item) {
            $this->assertContains($item, $tabs, "Item category slug '{$item}' has no matching tab");
        }
    }
}
