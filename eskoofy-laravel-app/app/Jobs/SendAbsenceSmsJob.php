<?php

namespace App\Jobs;

use App\Contracts\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAbsenceSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public string $phone,
        public string $message,
    ) {}

    public function handle(SmsService $sms): void
    {
        try {
            $sms->send($this->phone, $this->message);
        } catch (\Throwable $e) {
            Log::warning('Absence SMS failed', [
                'phone' => $this->phone,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
