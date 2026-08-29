<?php

namespace Tests\Unit\Models;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_key_columns(): void
    {
        $news = News::create([
            'title' => 'Sports Day',
            'slug' => 'sports-day',
            'content' => 'Annual sports.',
            'category' => 'event',
            'is_published' => true,
            'is_event' => true,
        ]);

        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'slug' => 'sports-day',
            'is_published' => true,
            'is_event' => true,
        ]);

        $this->assertTrue($news->is_published);
        $this->assertTrue($news->is_event);
    }

    /** @test */
    public function it_scopes_published_news(): void
    {
        News::create(['title' => 'P', 'slug' => 'p', 'content' => 'c', 'is_published' => true]);
        News::create(['title' => 'D', 'slug' => 'd', 'content' => 'c', 'is_published' => false]);

        $this->assertCount(1, News::published()->get());
    }

    /** @test */
    public function it_scopes_events(): void
    {
        News::create(['title' => 'E', 'slug' => 'e', 'content' => 'c', 'is_event' => true]);
        News::create(['title' => 'N', 'slug' => 'n', 'content' => 'c', 'is_event' => false]);

        $this->assertCount(1, News::events()->get());
    }

    /** @test */
    public function it_scopes_upcoming_events(): void
    {
        News::create([
            'title' => 'Future',
            'slug' => 'future',
            'content' => 'c',
            'is_event' => true,
            'event_date' => now()->addDays(3),
        ]);
        News::create([
            'title' => 'Past',
            'slug' => 'past',
            'content' => 'c',
            'is_event' => true,
            'event_date' => now()->subDays(3),
        ]);

        $this->assertCount(1, News::upcoming()->get());
    }

    /** @test */
    public function it_soft_deletes(): void
    {
        $news = News::create(['title' => 'Soft', 'slug' => 'soft', 'content' => 'c']);

        $news->delete();

        $this->assertSoftDeleted('news', ['id' => $news->id]);
        $this->assertCount(0, News::all());
        $this->assertCount(1, News::withTrashed()->get());
    }
}
