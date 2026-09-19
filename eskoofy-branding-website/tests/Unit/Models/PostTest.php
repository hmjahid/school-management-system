<?php
declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Core\Model;
use App\Models\Post;
use App\Models\PostCategory;
use Tests\FakeDatabase;
use Tests\TestCase;

class PostTest extends TestCase
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

    private function seedPost(array $overrides = []): int
    {
        return $this->db->insert('posts', array_merge([
            'title'        => 'Sample post',
            'slug'         => 'sample-post',
            'content'      => '<p>body</p>',
            'status'       => 'published',
            'published_at' => '2026-01-01 00:00:00',
        ], $overrides));
    }

    public function test_published_returns_only_published(): void
    {
        $this->seedPost(['title' => 'A', 'slug' => 'a']);
        $this->seedPost(['title' => 'B', 'slug' => 'b', 'status' => 'draft']);
        $this->seedPost(['title' => 'C', 'slug' => 'c']);

        $rows = Post::published();

        $this->assertCount(2, $rows);
        $this->assertSame('A', $rows[0]['title']);
    }

    public function test_published_excludes_soft_deleted(): void
    {
        $id = $this->seedPost(['title' => 'Z', 'slug' => 'z']);
        $this->db->update('posts', ['deleted_at' => '2026-01-01 00:00:00'], 'id = ?', [$id]);

        $this->assertCount(0, Post::published());
    }

    public function test_count_published(): void
    {
        $this->seedPost(['title' => '1', 'slug' => '1']);
        $this->seedPost(['title' => '2', 'slug' => '2', 'status' => 'draft']);
        $this->seedPost(['title' => '3', 'slug' => '3']);

        $this->assertSame(2, Post::countPublished());
    }

    public function test_latest_returns_up_to_n_published(): void
    {
        $this->seedPost(['title' => '1', 'slug' => '1']);
        $this->seedPost(['title' => '2', 'slug' => '2']);
        $this->seedPost(['title' => '3', 'slug' => '3']);
        $this->seedPost(['title' => '4', 'slug' => '4']);

        $this->assertCount(3, Post::latest(3));
    }

    public function test_by_slug_returns_row(): void
    {
        $this->seedPost(['title' => 'Hello', 'slug' => 'hello']);

        $row = Post::bySlug('hello');

        $this->assertNotNull($row);
        $this->assertSame('Hello', $row['title']);
    }

    public function test_by_slug_returns_null_for_missing(): void
    {
        $this->seedPost(['title' => 'Hello', 'slug' => 'hello']);

        $this->assertNull(Post::bySlug('nope'));
    }

    public function test_published_by_category_filters_by_slug(): void
    {
        $this->db->insert('post_categories', ['name' => 'Guides', 'slug' => 'guides', 'active' => 1]);
        $this->db->insert('post_categories', ['name' => 'Other', 'slug' => 'other', 'active' => 1]);

        $this->seedPost(['title' => 'A', 'slug' => 'a', 'category_id' => 1]);
        $this->seedPost(['title' => 'B', 'slug' => 'b', 'category_id' => 2]);
        $this->seedPost(['title' => 'C', 'slug' => 'c', 'category_id' => 1, 'status' => 'draft']);

        $rows = Post::publishedByCategory('guides');

        $this->assertCount(1, $rows);
        $this->assertSame('A', $rows[0]['title']);
    }

    public function test_post_category_active_returns_only_active(): void
    {
        $this->db->insert('post_categories', ['name' => 'A', 'slug' => 'a', 'active' => 1]);
        $this->db->insert('post_categories', ['name' => 'B', 'slug' => 'b', 'active' => 0]);

        $rows = PostCategory::active();

        $this->assertCount(1, $rows);
        $this->assertSame('A', $rows[0]['name']);
    }

    public function test_post_category_by_slug_returns_null_when_inactive(): void
    {
        $this->db->insert('post_categories', ['name' => 'A', 'slug' => 'a', 'active' => 0]);

        $this->assertNull(PostCategory::bySlug('a'));
    }
}