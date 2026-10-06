<?php
declare(strict_types=1);

namespace App\Gateways;

/**
 * Test / sandbox gateway.
 *
 * A zero-credential, offline stand-in for a hosted checkout. `initialize`
 * redirects the payer to the local sandbox page; that page posts a
 * `simulate=success|failure|cancel` result back to the app. Completion is
 * recorded with a `TEST-<reference>` transaction id so it is easy to spot and
 * always verifies as paid (idempotent). It never talks to a payment network.
 */
class TestGateway implements GatewayInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function initialize(array $params): array
    {
        $amount = (float) ($params['amount'] ?? 0);

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid amount', 'data' => []];
        }

        $paymentId = (int) ($params['payment_id'] ?? 0);
        $reference = (string) ($params['invoice_id'] ?? '');

        return [
            'success' => true,
            'message' => 'Test payment created',
            'data'    => [
                'payment_url'    => $this->sandboxUrl($paymentId),
                'payment_id'     => $paymentId,
                'invoice_number' => $reference,
                'currency'       => $this->currency($params),
                'status'         => 'pending',
            ],
        ];
    }

    public function verifyPayment(string $transactionId): bool
    {
        return str_starts_with($transactionId, 'TEST-');
    }

    public function processCallback(array $payload): array
    {
        $reference = (string) ($payload['invoice_id'] ?? $payload['reference'] ?? '');
        $paymentId = $payload['payment_id'] ?? null;
        $simulate = strtolower((string) ($payload['simulate'] ?? $payload['status'] ?? ''));
        $data = [
            'payment_id' => $paymentId,
            'invoice_id' => $reference,
        ];

        if ($simulate === 'success') {
            $data['transaction_id'] = 'TEST-' . ($reference !== '' ? $reference : (string) $paymentId);
            $data['status'] = 'completed';
            $data['amount'] = $payload['amount'] ?? null;
            $data['currency'] = $this->currency($payload);

            return ['success' => true, 'message' => 'Test payment completed', 'data' => $data];
        }

        if ($simulate === 'cancel') {
            $data['status'] = 'cancelled';

            return ['success' => false, 'message' => 'Test payment cancelled', 'data' => $data];
        }

        $data['status'] = 'failed';

        return ['success' => false, 'message' => 'Test payment failed', 'data' => $data];
    }

    public function refund(string $transactionId, float $amount, string $reason): array
    {
        return [
            'success' => false,
            'message' => 'Refunds are not supported for the test gateway.',
            'data'    => [],
        ];
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        return false;
    }

    private function sandboxUrl(int $paymentId): string
    {
        $base = rtrim((string) ($this->config['app_url'] ?? $_ENV['APP_URL'] ?? config('app.url', '')), '/');

        return $base . '/payments/sandbox/' . $paymentId;
    }

    private function currency(array $params): string
    {
        return (string) ($this->config['currency']
            ?? $params['currency']
            ?? config('payment.currency', 'BDT'));
    }
}