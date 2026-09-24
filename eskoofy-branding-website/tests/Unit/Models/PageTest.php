<?php
declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Core\Model;
use App\Models\Page;
use Tests\FakeDatabase;
use Tests\TestCase;

class PageTest extends TestCase
{
    private FakeDatabase $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new FakeDatabase();
        Model::setTestDatabase($this->db);
    }

    protected function tearDown(): void
    {
        Model::setTestDatabase(null);
        parent::tearDown();
    }

    private function seedPage(array $overrides = []): int
    {
        return $this->db->insert('pages', array_merge([
            'name'       => 'About',
            'route'      => '/about',
            'sort_order' => 0,
            'status'     => 'active',
            'noindex'    => 0,
            'title_en'   => 'About Eskoofy',
            'title_bn'   => null,
            'created_at' => '2026-01-01 00:00:00',
        ], $overrides));
    }

    public function test_for_path_exact_match(): void
    {
        $this->seedPage();

        $row = Page::forPath('/about');

        $this->assertNotNull($row);
        $this->assertSame('/about', $row['route']);
    }

    public function test_for_path_normalizes_slashes(): void
    {
        $this->seedPage();

        $this->assertNotNull(Page::forPath('about'));
        $this->assertNotNull(Page::forPath('/about/'));
    }

    public function test_for_path_falls_back_to_parent_segment(): void
    {
        $this->seedPage(['route' => '/products']);

        $row = Page::forPath('/products/app');

        $this->assertNotNull($row);
        $this->assertSame('/products', $row['route']);
    }

    public function test_for_path_ignores_inactive_and_deleted(): void
    {
        $this->seedPage(['name' => 'Draft', 'route' => '/draft', 'status' => 'draft']);
        $id = $this->seedPage(['name' => 'Old', 'route' => '/old', 'status' => 'active']);
        $this->db->update('pages', ['deleted_at' => '2026-01-02 00:00:00'], 'id = ?', [$id]);

        $this->assertNull(Page::forPath('/draft'));
        $this->assertNull(Page::forPath('/old'));
    }

    public function test_for_path_returns_null_when_nothing_matches(): void
    {
        $this->assertNull(Page::forPath('/does-not-exist'));
    }

    public function test_localized_picks_locale_column(): void
    {
        $this->seedPage(['heading_en' => 'Hello', 'heading_bn' => 'হ্যালো']);

        $row = Page::localized(Page::forPath('/about'), 'bn');

        $this->assertSame('হ্যালো', $row['heading']);
    }

    public function test_localized_falls_back_to_english(): void
    {
        $this->seedPage(['heading_en' => 'Hello', 'heading_bn' => null]);

        $row = Page::localized(Page::forPath('/about'), 'bn');

        $this->assertSame('Hello', $row['heading']);
    }

    public function test_localized_keeps_shared_seo_fields(): void
    {
        $this->seedPage(['canonical' => 'https://eskoofy.com/about', 'json_schema' => '{"@type":"WebPage"}']);

        $row = Page::localized(Page::forPath('/about'), 'en');

        $this->assertSame('https://eskoofy.com/about', $row['canonical']);
        $this->assertSame('{"@type":"WebPage"}', $row['json_schema']);
        $this->assertTrue($row['name'] === 'About');
    }

    public function test_routes_returns_active_routes_ordered(): void
    {
        $this->seedPage(['route' => '/b']);
        $this->seedPage(['route' => '/a']);
        $this->seedPage(['route' => '/gone', 'status' => 'active']);
        $this->db->update('pages', ['deleted_at' => '2026-01-02 00:00:00'], 'route = ?', ['/gone']);

        $this->assertSame(['/b', '/a'], Page::routes());
    }
}