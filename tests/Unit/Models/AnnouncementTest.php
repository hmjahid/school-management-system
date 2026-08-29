<?php

namespace Tests\Unit\Models;

use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_key_columns(): void
    {
        $announcement = Announcement::create([
            'title' => 'Exam Notice',
            'body' => 'Final exam schedule.',
            'audience' => ['all'],
            'display_target' => 'header',
            'is_published' => false,
        ]);

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => 'Exam Notice',
            'display_target' => 'header',
            'is_published' => false,
        ]);

        $this->assertSame(['all'], $announcement->audience);
        $this->assertFalse($announcement->is_published);
    }

    /** @test */
    public function it_returns_english_title_by_default(): void
    {
        $announcement = Announcement::create([
            'title' => 'English Title',
            'title_bn' => 'বাংলা শিরোনাম',
        ]);

        App::setLocale('en');
        $this->assertSame('English Title', $announcement->localizedTitle());

        App::setLocale('bn');
        $this->assertSame('বাংলা শিরোনাম', $announcement->localizedTitle());
    }

    /** @test */
    public function it_returns_english_body_when_bn_missing(): void
    {
        $announcement = Announcement::create([
            'title' => 'Title',
            'body' => 'English body',
        ]);

        App::setLocale('bn');
        $this->assertSame('English body', $announcement->localizedBody());
    }

    /** @test */
    public function it_scopes_published_announcements(): void
    {
        Announcement::create(['title' => 'Published', 'is_published' => true]);
        Announcement::create(['title' => 'Draft', 'is_published' => false]);

        $this->assertCount(1, Announcement::published()->get());
    }

    /** @test */
    public function it_scopes_active_announcements(): void
    {
        Announcement::create([
            'title' => 'Active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);
        Announcement::create([
            'title' => 'Expired',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
        ]);

        $this->assertCount(1, Announcement::active()->get());
    }

    /** @test */
    public function it_scopes_for_header_and_notification(): void
    {
        Announcement::create(['title' => 'H', 'display_target' => 'header']);
        Announcement::create(['title' => 'N', 'display_target' => 'notification']);
        Announcement::create(['title' => 'B', 'display_target' => 'both']);

        $this->assertCount(2, Announcement::forHeader()->get());
        $this->assertCount(2, Announcement::forNotification()->get());
    }
}
