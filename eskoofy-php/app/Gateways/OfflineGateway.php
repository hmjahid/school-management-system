<?php
declare(strict_types=1);

namespace App\Gateways;

class OfflineGateway implements GatewayInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Return bank transfer details. No API call is made.
     */
    public function initialize(array $params): array
    {
        $amount   = $params['amount'] ?? 0;
        $invoiceId = $params['invoice_id'] ?? '';

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid amount', 'data' => []];
        }

        return [
            'success' => true,
            'message' => 'Please use the bank details below to complete your transfer',
            'data'    => [
                'bank_name'     => $this->config['bank_name'] ?? '',
                'account_name'  => $this->config['account_name'] ?? '',
                'account_number' => $this->config['account_number'] ?? '',
                'routing_number' => $this->config['routing_number'] ?? '',
                'instructions'   => $this->config['instructions'] ?? '',
                'amount'         => $amount,
                'invoice_id'     => $invoiceId,
                'currency'       => 'BDT',
                'status'         => 'pending_verification',
            ],
        ];
    }

    /**
     * Manual verification – always pending until admin approves.
     */
    public function verifyPayment(string $transactionId): bool
    {
        return false;
    }

    /**
     * Process callback – for offline gateways this is essentially a no-op.
     * Admin must manually verify.
     */
    public function processCallback(array $payload): array
    {
        return [
            'success' => false,
            'message' => 'Offline payments require manual admin verification',
            'data'    => $payload,
        ];
    }

    /**
     * Offline gateway does not support programmatic refunds.
     */
    public function refund(string $transactionId, float $amount, string $reason): array
    {
        return [
            'success' => false,
            'message' => 'Offline refunds must be processed manually',
            'data'    => [
                'transaction_id' => $transactionId,
                'amount'         => $amount,
                'reason'         => $reason,
            ],
        ];
    }

    /**
     * Offline gateway has no webhooks.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        return false;
    }
}
