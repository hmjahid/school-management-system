<?php

namespace Tests\Unit\Models;

use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\SmsCampaign;
use App\Models\SmsCampaignRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsCampaignTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_required_columns(): void
    {
        $campaign = SmsCampaign::create([
            'name' => 'Holiday Alert',
            'audience_type' => SmsCampaign::AUDIENCE_ALL,
            'message' => 'School closed tomorrow',
            'status' => SmsCampaign::STATUS_DRAFT,
        ]);

        $this->assertDatabaseHas('sms_campaigns', [
            'name' => 'Holiday Alert',
            'audience_type' => SmsCampaign::AUDIENCE_ALL,
            'message' => 'School closed tomorrow',
            'status' => SmsCampaign::STATUS_DRAFT,
        ]);
    }

    /** @test */
    public function it_defaults_status_to_draft(): void
    {
        $campaign = SmsCampaign::create([
            'name' => 'Reminder',
            'audience_type' => SmsCampaign::AUDIENCE_STAFF,
            'message' => 'Meeting at 3pm',
        ]);

        $this->assertDatabaseHas('sms_campaigns', [
            'id' => $campaign->id,
            'status' => SmsCampaign::STATUS_DRAFT,
        ]);
    }

    /** @test */
    public function it_exposes_audience_and_status_constants(): void
    {
        $this->assertEquals('all', SmsCampaign::AUDIENCE_ALL);
        $this->assertEquals('class', SmsCampaign::AUDIENCE_CLASS);
        $this->assertEquals('section', SmsCampaign::AUDIENCE_SECTION);
        $this->assertEquals('staff', SmsCampaign::AUDIENCE_STAFF);
        $this->assertEquals('draft', SmsCampaign::STATUS_DRAFT);
        $this->assertEquals('sending', SmsCampaign::STATUS_SENDING);
        $this->assertEquals('sent', SmsCampaign::STATUS_SENT);
        $this->assertEquals('failed', SmsCampaign::STATUS_FAILED);
    }

    /** @test */
    public function it_belongs_to_a_class_when_set(): void
    {
        $class = SchoolClass::factory()->create();

        $campaign = SmsCampaign::create([
            'name' => 'Class Alert',
            'audience_type' => SmsCampaign::AUDIENCE_CLASS,
            'school_class_id' => $class->id,
            'message' => 'Class message',
        ]);

        $this->assertTrue($campaign->class->is($class));
    }

    /** @test */
    public function it_belongs_to_a_section_when_set(): void
    {
        $class = SchoolClass::factory()->create();
        $academicYear = \App\Models\AcademicYear::create([
            'name' => 'AY '.uniqid(),
            'session' => 'sess'.uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $section = \App\Models\Section::create([
            'name' => 'Section '.uniqid(),
            'slug' => 'sec'.uniqid(),
            'academic_year_id' => $academicYear->id,
            'class_id' => $class->id,
        ]);

        $campaign = SmsCampaign::create([
            'name' => 'Section Alert',
            'audience_type' => SmsCampaign::AUDIENCE_SECTION,
            'section_id' => $section->id,
            'message' => 'Section message',
        ]);

        $this->assertTrue($campaign->section->is($section));
    }

    /** @test */
    public function it_belongs_to_a_creator_when_set(): void
    {
        $creator = User::factory()->create();

        $campaign = SmsCampaign::create([
            'name' => 'Creator Alert',
            'audience_type' => SmsCampaign::AUDIENCE_ALL,
            'message' => 'Message',
            'created_by' => $creator->id,
        ]);

        $this->assertTrue($campaign->creator->is($creator));
    }

    /** @test */
    public function it_has_many_recipients(): void
    {
        $campaign = SmsCampaign::create([
            'name' => 'Recipients Alert',
            'audience_type' => SmsCampaign::AUDIENCE_ALL,
            'message' => 'Message',
        ]);

        $recipient = SmsCampaignRecipient::create([
            'sms_campaign_id' => $campaign->id,
            'phone' => '01700000000',
            'user_type' => 'student',
            'status' => 'queued',
        ]);

        $this->assertTrue($campaign->recipients->contains($recipient));
    }
}
