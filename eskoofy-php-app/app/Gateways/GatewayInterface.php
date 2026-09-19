<?php
declare(strict_types=1);

namespace App\Gateways;

interface GatewayInterface
{
    /**
     * Initialize a payment and return payment URL or params.
     *
     * @param array $params Payment details: amount, invoice_id, student_id, etc.
     * @return array{success: bool, message: string, data: array}
     */
    public function initialize(array $params): array;

    /**
     * Verify a payment by transaction ID.
     *
     * @param string $transactionId
     * @return bool
     */
    public function verifyPayment(string $transactionId): bool;

    /**
     * Process a callback/webhook payload.
     *
     * @param array $payload Raw callback data
     * @return array{success: bool, message: string, data: array}
     */
    public function processCallback(array $payload): array;

    /**
     * Refund a transaction.
     *
     * @param string $transactionId
     * @param float  $amount
     * @param string $reason
     * @return array{success: bool, message: string, data: array}
     */
    public function refund(string $transactionId, float $amount, string $reason): array;

    /**
     * Verify a webhook signature.
     *
     * @param string $payload  Raw request body
     * @param string $signature Header signature value
     * @return bool
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool;
}
