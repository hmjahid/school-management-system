<?php

namespace App\Services;

use App\Contracts\SmsService;
use Illuminate\Support\Facades\Log;

class LogSmsService implements SmsService
{
    /**
     * Send an SMS message (logs it instead of actually sending).
     *
     * @param  string  $to
     * @param  string  $message
     * @param  array  $options
     * @return bool
     */
    public function send(string $to, string $message, array $options = []): bool
    {
        Log::info("SMS to {$to}: {$message}", $options);
        return true;
    }
    
    /**
     * Get the remaining SMS balance.
     *
     * @return float
     */
    public function getBalance(): float
    {
        return 100.0; // Return a dummy balance for testing
    }
    
    /**
     * Get the delivery status of a sent message.
     *
     * @param  string  $messageId
     * @return array
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
