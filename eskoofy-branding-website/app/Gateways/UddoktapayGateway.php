<?php
declare(strict_types=1);

namespace App\Gateways;

/**
 * UddoktaPay payment gateway (Bangladesh).
 *
 * Aggregator: one hosted checkout covers bKash, Nagad, Rocket, Upay and bank
 * transfer. API reference: https://uddoktapay.readme.io/reference/overview
 */
class UddoktapayGateway extends AbstractGateway
{
    public function id(): string
    {
        return 'uddoktapay';
    }

    public function name(): string
    {
        return 'UddoktaPay';
    }

    public function isConfigured(): bool
    {
        return (string) ($this->setting('api_key') ?? '') !== '';
    }

    private function baseUrl(): string
    {
        if (($this->setting('test_mode') ?? true) === true) {
            return (string) ($this->setting('sandbox_url') ?? 'https://sandbox.uddoktapay.com/api');
        }

        return (string) ($this->setting('live_url') ?? 'https://pay.uddoktapay.com/api');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, code: int, body: string, decoded: mixed, error?: string}
     */
    private function request(string $path, array $payload): array
    {
        return $this->http(
            rtrim($this->baseUrl(), '/') . '/' . $path,
            [
                'RT-UDDOKTAPAY-API-KEY' => (string) ($this->setting('api_key') ?? ''),
                'Content-Type'          => 'application/json',
                'Accept'                => 'application/json',
            ],
            (string) json_encode($payload)
        );
    }

    public function process(array $order, array $payment): array
    {
        $amount    = (float) ($order['amount'] ?? $payment['amount'] ?? 0);
        $reference = (string) ($payment['reference'] ?? '');
        $customer  = is_array($order['customer'] ?? null) ? $order['customer'] : [];

        if ($amount <= 0) {
            return ['success' => false, 'transaction_id' => null, 'message' => 'Invalid amount', 'raw' => null];
        }
        if (!$this->isConfigured()) {
            return ['success' => false, 'transaction_id' => null, 'message' => 'UddoktaPay is not configured.', 'raw' => null];
        }

        $response = $this->request('checkout-v2', [
            'full_name'    => (string) ($customer['name'] ?? 'Customer'),
            'email'        => (string) ($customer['email'] ?? ''),
            'amount'       => number_format($amount, 2, '.', ''),
            'metadata'     => [
                'payment_id' => (int) ($payment['id'] ?? 0),
                'reference'  => $reference,
            ],
            'redirect_url' => (string) ($order['return_url'] ?? ''),
            'return_type'  => 'GET',
            'cancel_url'   => (string) ($order['cancel_url'] ?? ''),
            'webhook_url'  => (string) ($order['return_url'] ?? ''),
        ]);

        $decoded = is_array($response['decoded']) ? $response['decoded'] : [];

        if ($response['ok'] && !empty($decoded['payment_url'])) {
            return [
                'success'        => true,
                'transaction_id' => $reference,
                'message'        => 'Redirecting to UddoktaPay payment.',
                'redirect_url'   => (string) $decoded['payment_url'],
                'raw'            => $decoded,
            ];
        }

        return [
            'success'        => false,
            'transaction_id' => null,
            'message'        => $decoded['message'] ?? 'UddoktaPay payment creation failed.',
            'raw'            => $decoded,
        ];
    }

    public function verify(array $payment, array $data = []): array
    {
        $invoiceId = (string) ($data['invoice_id'] ?? ($payment['transaction_id'] ?? ''));

        if ($invoiceId === '' || !$this->isConfigured()) {
            return ['success' => false, 'transaction_id' => null, 'status' => 'pending', 'message' => 'Missing invoice / UddoktaPay not configured.', 'raw' => null];
        }

        $response = $this->request('verify-payment', ['invoice_id' => $invoiceId]);
        $decoded  = is_array($response['decoded']) ? $response['decoded'] : [];
        $status   = strtoupper((string) ($decoded['status'] ?? ''));
        $ok       = $status === 'COMPLETED';

        if ($ok) {
            $trxId = (string) ($decoded['transaction_id'] ?? $invoiceId);
            $this->markPaid($payment, $trxId, ['raw' => (string) json_encode($decoded)]);
            $this->logProcessed($payment, $trxId);
        }

        return [
            'success'        => $ok,
            'transaction_id' => (string) ($decoded['transaction_id'] ?? $invoiceId),
            'status'         => $status !== '' ? strtolower($status) : 'pending',
            'message'        => $ok ? 'Payment verified.' : 'Payment not completed.',
            'raw'            => $decoded,
        ];
    }
}
