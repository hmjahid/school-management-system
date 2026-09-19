<?php
declare(strict_types=1);

namespace App\Services\Sms;

interface SmsServiceInterface
{
    /**
     * Send an SMS message.
     *
     * @return array{success: bool, message_id: ?string, status: string, provider: string, raw?: mixed}
     */
    public function send(string $to, string $message, array $options = []): array;

    /**
     * Get the remaining SMS balance.
     *
     * @return array{amount: float, currency: string}
     */
    public function getBalance(): array;

    /**
     * Get the delivery status of a sent message.
     *
     * @return array<string, mixed>
     */
    public function getStatus(string $messageId): array;

    /**
     * Driver name (twilio, vonage, log, ...).
     */
    public function name(): string;
}