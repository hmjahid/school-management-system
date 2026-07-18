<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class LogSmsService extends BaseSmsService
{
    /**
     * Get the default configuration for the service.
     *
     * @return array
     */
    protected function getDefaultConfig(): array
    {
        return [
            'log_channel' => env('SMS_LOG_CHANNEL', 'stack'),
        ];
    }

    /**
     * Send the SMS message to the given number.
     *
     * @param  string  $to
     * @param  string  $message
     * @param  array  $options
     * @return array
     */
    protected function sendSms(string $to, string $message, array $options = [])
    {
        $from = $options['from'] ?? $this->getFrom();
        
        $logMessage = sprintf(
            "[SMS] From: %s, To: %s, Message: %s",
            $from,
            $to,
            $message
        );
        
        Log::channel($this->config['log_channel'])->info($logMessage, $options);
        
        $response = [
            'success' => true,
            'message_id' => uniqid('sms_', true),
            'to' => $to,
            'from' => $from,
            'message' => $message,
            'timestamp' => now()->toDateTimeString(),
        ];
        
        $this->setLastResponse($response);
        
        return $response;
    }

    /**
     * Determine if the SMS was sent successfully.
     *
     * @param  mixed  $response
     * @return bool
     */
    protected function wasSuccessful($response): bool
    {
        return is_array($response) && ($response['success'] ?? false);
    }

    /**
     * Get the remaining SMS balance.
     *
     * @return float
     */
    public function getBalance(): float
    {
        // In development, we have unlimited credits
        return 9999.99;
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
