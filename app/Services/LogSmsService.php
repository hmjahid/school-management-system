<?php

namespace App\Services;

use App\Contracts\SmsService;
use Illuminate\Support\Facades\Log;

class LogSmsService implements SmsService
{
    /**
     * Send an SMS message (logs it instead of actually sending).
     */
    public function send(string $to, string $message, array $options = []): bool
    {
        Log::info("SMS to {$to}: {$message}", $options);

        return true;
    }

    /**
     * Get the remaining SMS balance.
     */
    public function getBalance(): float
    {
        return 100.0; // Return a dummy balance for testing
    }

    /**
     * Get the delivery status of a sent message.
     */
    public function getStatus(string $messageId): array
    {
        return [
            'status' => 'delivered',
            'message_id' => $messageId,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
