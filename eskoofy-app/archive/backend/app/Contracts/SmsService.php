<?php

namespace App\Contracts;

interface SmsService
{
    /**
     * Send an SMS message.
     */
    public function send(string $to, string $message, array $options = []): bool;

    /**
     * Get the remaining SMS balance.
     */
    public function getBalance(): float;

    /**
     * Get the delivery status of a sent message.
     */
    public function getStatus(string $messageId): array;
}
