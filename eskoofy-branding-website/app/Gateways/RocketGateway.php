<?php
declare(strict_types=1);

namespace App\Gateways;

/**
 * Rocket payment gateway (Bangladesh). dbbl.com.bd merchant API.
 */
class RocketGateway extends AbstractGateway
{
    public function id(): string
    {
        return 'rocket';
    }

    public function name(): string
    {
        return 'Rocket';
    }

    public function isConfigured(): bool
    {
        return (string) ($this->setting('api_key') ?? '') !== '';
    }

    private function baseUrl(): string
    {
        return ($this->setting('test_mode') ?? true) === true
            ? (string) ($this->setting('sandbox_url') ?? 'https://sandbox.rocket.dbblltd.com')
            : (string) ($this->setting('live_url') ?? 'https://api.rocket.dbblltd.com');
    }

    public function process(array $order, array $payment): array
    {
        $amount    = (float) ($order['amount'] ?? $payment['amount'] ?? 0);
        $reference = (string) ($payment['reference'] ?? '');
        $apiKey    = (string) ($this->setting('api_key') ?? '');

        if ($amount <= 0) {
            return ['success' => false, 'transaction_id' => null, 'message' => 'Invalid amount', 'raw' => null];
        }
        if (!$this->isConfigured()) {
            return ['success' => false, 'transaction_id' => null, 'message' => 'Rocket is not configured.', 'raw' => null];
        }

        $response = $this->http(
            $this->baseUrl() . '/pg-api/payment/create',
            [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'API-KEY'      => $apiKey,
            ],
            json_encode([
                'amount'          => number_format($amount, 2, '.', ''),
                'merchant_inv_no' => $reference,
                'callback_url'    => $order['return_url'] ?? '',
                'customer_name'   => (string) ($order['customer']['name'] ?? ''),
                'customer_mobile' => (string) ($order['customer']['phone'] ?? ''),
            ])
        );

        $decoded = is_array($response['decoded']) ? $response['decoded'] : [];

        if ($response['ok'] && isset($decoded['payment_url'])) {
            return [
                'success'        => true,
                'transaction_id' => (string) ($decoded['transaction_id'] ?? ''),
                'message'        => 'Redirecting to Rocket payment.',
                'redirect_url'   => (string) $decoded['payment_url'],
                'raw'            => $decoded,
            ];
        }

        return [
            'success'        => false,
            'transaction_id' => null,
            'message'        => $decoded['message'] ?? 'Rocket payment creation failed.',
            'raw'            => $decoded,
        ];
    }

    public function verify(array $payment, array $data = []): array
    {
        $ref = (string) ($data['reference'] ?? $data['transaction_id'] ?? $payment['reference'] ?? '');
        if ($ref === '') {
            return ['success' => false, 'transaction_id' => null, 'status' => 'pending', 'message' => 'Missing reference.', 'raw' => null];
        }

        // Rocket reports via callback with trx_status = SUCCESS/FAILED.
        $status = strtolower((string) ($data['trx_status'] ?? $data['status'] ?? ''));
        $ok = $status === 'success';

        if ($ok) {
            $this->markPaid($payment, $ref, ['raw' => json_encode($data)]);
            $this->logProcessed($payment, $ref);
        }

        return [
            'success'        => $ok,
            'transaction_id' => $ref,
            'status'         => $status ?: 'pending',
            'message'        => $ok ? 'Payment verified.' : 'Payment not completed.',
            'raw'            => $data,
        ];
    }
}