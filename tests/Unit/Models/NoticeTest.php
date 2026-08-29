<?php

namespace Tests\Unit\Models;

use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class NoticeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_key_columns(): void
    {
        $user = User::factory()->create();

        $notice = Notice::create([
            'title' => 'Holiday',
            'content' => 'School closed.',
            'pinned' => true,
            'audience' => ['all'],
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('notices', [
            'id' => $notice->id,
            'title' => 'Holiday',
            'pinned' => true,
            'created_by' => $user->id,
        ]);

        $this->assertTrue($notice->pinned);
        $this->assertSame(['all'], $notice->audience);
    }

    /** @test */
    public function it_returns_localized_title_and_content(): void
    {
        $notice = Notice::create([
            'title' => 'English Title',
            'title_bn' => 'বাংলা শিরোনাম',
            'content' => 'English content',
            'content_bn' => 'বাংলা বিষয়বস্তু',
            'created_by' => User::factory()->create()->id,
        ]);

        App::setLocale('en');
        $this->assertSame('English Title', $notice->localizedTitle());
        $this->assertSame('English content', $notice->localizedContent());

        App::setLocale('bn');
        $this->assertSame('বাংলা শিরোনাম', $notice->localizedTitle());
        $this->assertSame('বাংলা বিষয়বস্তু', $notice->localizedContent());
    }

    /** @test */
    public function it_belongs_to_a_creator(): void
    {
        $user = User::factory()->create();

        $notice = Notice::create([
            'title' => 'Notice',
            'content' => 'Body',
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $notice->creator);
        $this->assertSame($user->id, $notice->creator->id);
    }
}
