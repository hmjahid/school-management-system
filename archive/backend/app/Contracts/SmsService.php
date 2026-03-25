<?php

namespace App\Contracts;

interface SmsService
{
    /**
     * Send an SMS message.
     *
     * @param  string  $to
     * @param  string  $message
     * @param  array  $options
     * @return bool
     */
    public function send(string $to, string $message, array $options = []): bool;
    
    /**
     * Get the remaining SMS balance.
     *
     * @return float
     */
    public function getBalance(): float;
    
    /**
     * Get the delivery status of a sent message.
     *
     * @param  string  $messageId
     * @return array
     */
    public function getStatus(string $messageId): array;
}
