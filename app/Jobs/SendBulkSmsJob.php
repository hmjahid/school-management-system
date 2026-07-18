<?php

namespace App\Jobs;

use App\Contracts\SmsService;
use App\Models\SmsCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBulkSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $campaignId) {}

    public function handle(SmsService $sms): void
    {
        $campaign = SmsCampaign::with('recipients')->find($this->campaignId);
        if (!$campaign) {
            return;
        }

        $campaign->update(['status' => SmsCampaign::STATUS_SENDING]);

        foreach ($campaign->recipients as $recipient) {
            try {
                $sms->send($recipient->phone, $campaign->message);
                $recipient->update(['status' => 'sent']);
            } catch (\Throwable $e) {
                Log::warning('Bulk SMS failed', ['phone' => $recipient->phone, 'error' => $e->getMessage()]);
                $recipient->update(['status' => 'failed', 'error' => $e->getMessage()]);
            }
        }

        $campaign->update(['status' => SmsCampaign::STATUS_SENT, 'sent_at' => now()]);
    }
}