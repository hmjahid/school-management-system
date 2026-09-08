<?php

namespace App\Jobs;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public array $recipientIds,
        public string $type,
        public string $subject,
        public string $content,
        public array $data = [],
        public array $channels = ['database', 'mail']
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $notificationService
            ->to($this->recipientIds)
            ->type($this->type)
            ->subject($this->subject)
            ->content($this->content)
            ->with($this->data)
            ->via($this->channels)
            ->send();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendNotificationJob failed', [
            'type' => $this->type,
            'recipient_ids' => $this->recipientIds,
            'error' => $exception->getMessage(),
            'request_id' => request()->header('X-Request-ID'),
        ]);
    }
}
