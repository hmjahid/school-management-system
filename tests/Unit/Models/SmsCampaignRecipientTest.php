<?php

namespace Tests\Unit\Models;

use App\Models\SmsCampaign;
use App\Models\SmsCampaignRecipient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsCampaignRecipientTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCampaign(): SmsCampaign
    {
        return SmsCampaign::create([
            'name' => 'Campaign '.uniqid(),
            'audience_type' => SmsCampaign::AUDIENCE_ALL,
            'message' => 'Message',
        ]);
    }

    /** @test */
    public function it_persists_required_columns(): void
    {
        $campaign = $this->makeCampaign();

        $recipient = SmsCampaignRecipient::create([
            'sms_campaign_id' => $campaign->id,
            'phone' => '01700000000',
            'user_type' => 'student',
            'user_id' => 5,
            'status' => 'queued',
        ]);

        $this->assertDatabaseHas('sms_campaign_recipients', [
            'sms_campaign_id' => $campaign->id,
            'phone' => '01700000000',
            'user_type' => 'student',
            'user_id' => 5,
            'status' => 'queued',
        ]);
    }

    /** @test */
    public function it_defaults_status_to_queued(): void
    {
        $campaign = $this->makeCampaign();

        $recipient = SmsCampaignRecipient::create([
            'sms_campaign_id' => $campaign->id,
            'phone' => '01711111111',
        ]);

        $this->assertDatabaseHas('sms_campaign_recipients', [
            'id' => $recipient->id,
            'status' => 'queued',
        ]);
    }

    /** @test */
    public function it_belongs_to_a_campaign(): void
    {
        $campaign = $this->makeCampaign();

        $recipient = SmsCampaignRecipient::create([
            'sms_campaign_id' => $campaign->id,
            'phone' => '01722222222',
            'status' => 'sent',
        ]);

        $this->assertTrue($recipient->campaign->is($campaign));
    }
}
