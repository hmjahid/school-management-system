<?php

namespace Tests\Unit\Models;

use App\Models\CommitteeMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommitteeMemberTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_persists_key_columns(): void
    {
        $member = CommitteeMember::create([
            'name' => 'John Smith',
            'designation' => 'Chairman',
            'is_active' => false,
            'sort_order' => 3,
        ]);

        $this->assertDatabaseHas('committee_members', [
            'id' => $member->id,
            'name' => 'John Smith',
            'designation' => 'Chairman',
            'sort_order' => 3,
        ]);

        $this->assertFalse($member->is_active);
        $this->assertSame(3, $member->sort_order);
    }

    #[Test]
    public function it_returns_photo_url_accessor(): void
    {
        $withoutPhoto = CommitteeMember::create([
            'name' => 'No Photo',
            'designation' => 'Member',
        ]);
        $this->assertNull($withoutPhoto->photo_url);

        $withPhoto = CommitteeMember::create([
            'name' => 'Has Photo',
            'designation' => 'Member',
            'photo' => 'members/abc.jpg',
        ]);
        $this->assertStringContainsString('members/abc.jpg', $withPhoto->photo_url);
    }

    #[Test]
    public function it_returns_localized_fields(): void
    {
        $member = CommitteeMember::create([
            'name' => 'English Name',
            'name_bn' => 'বাংলা নাম',
            'designation' => 'English Desig',
            'designation_bn' => 'বাংলা পদবী',
            'bio' => 'English bio',
            'bio_bn' => 'বাংলা বিবরণ',
        ]);

        App::setLocale('en');
        $this->assertSame('English Name', $member->localizedName());
        $this->assertSame('English Desig', $member->localizedDesignation());
        $this->assertSame('English bio', $member->localizedBio());

        App::setLocale('bn');
        $this->assertSame('বাংলা নাম', $member->localizedName());
        $this->assertSame('বাংলা পদবী', $member->localizedDesignation());
        $this->assertSame('বাংলা বিবরণ', $member->localizedBio());
    }

    #[Test]
    public function it_scopes_active_and_ordered_members(): void
    {
        CommitteeMember::create(['name' => 'A', 'designation' => 'D', 'is_active' => false, 'sort_order' => 1]);
        CommitteeMember::create(['name' => 'B', 'designation' => 'D', 'is_active' => true, 'sort_order' => 2]);
        CommitteeMember::create(['name' => 'C', 'designation' => 'D', 'is_active' => true, 'sort_order' => 1]);

        $this->assertCount(2, CommitteeMember::active()->get());

        $ordered = CommitteeMember::ordered()->get();
        $this->assertSame('A', $ordered->first()->name);
        $this->assertSame('B', $ordered->last()->name);
    }
}
