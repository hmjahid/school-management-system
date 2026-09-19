<?php
declare(strict_types=1);

namespace App\Gateways;

/**
 * Stripe payment gateway (international). Payment Intents API.
 */
class StripeGateway extends AbstractGateway
{
    public function id(): string
    {
        return 'stripe';
    }

    public function name(): string
    {
        return 'Stripe';
    }

    public function isConfigured(): bool
    {
        return (string) ($this->setting('secret_key') ?? '') !== '';
    }

    private function apiUrl(): string
    {
        return ($this->setting('test_mode') ?? true) === true
            ? 'https://api.stripe.com/v1'
            : 'https://api.stripe.com/v1';
    }

    public function process(array $order, array $payment): array
    {
        $amount   = (float) ($order['amount'] ?? $payment['amount'] ?? 0);
        $currency = strtolower((string) ($order['currency'] ?? $payment['currency'] ?? 'usd'));
        $secret   = (string) ($this->setting('secret_key') ?? '');
        $reference = (string) ($payment['reference'] ?? '');

        if ($amount <= 0) {
            return ['success' => false, 'transaction_id' => null, 'message' => 'Invalid amount', 'raw' => null];
        }
        if (!$this->isConfigured()) {
            return ['success' => false, 'transaction_id' => null, 'message' => 'Stripe is not configured.', 'raw' => null];
        }

        $amountCents = (int) round($amount * 100);
        $response = $this->http(
            $this->apiUrl() . '/payment_intents',
            [
                'Authorization' => 'Bearer ' . $secret,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            http_build_query([
                'amount'               => $amountCents,
                'currency'             => $currency,
                'description'          => 'Eskoofy subscription ' . $reference,
                'automatic_payment_methods[enabled]' => 'true',
                'metadata[reference]'  => $reference,
            ])
        );

        if ($response['ok'] && is_array($response['decoded']) && isset($response['decoded']['id'])) {
            return [
                'success'        => true,
                'transaction_id' => $response['decoded']['id'],
                'message'        => 'Stripe payment intent created.',
                'redirect_url'   => $order['return_url'] ?? null,
                'client_secret'  => $response['decoded']['client_secret'] ?? null,
                'publishable_key' => (string) ($this->setting('publishable_key') ?? ''),
                'raw'            => $response['decoded'],
            ];
        }

        return [
            'success'        => false,
            'transaction_id' => null,
            'message'        => $response['decoded']['error']['message'] ?? 'Stripe payment intent creation failed.',
            'raw'            => $response['decoded'],
        ];
    }

    public function verify(array $payment, array $data = []): array
    {
        $intent = (string) ($data['payment_intent'] ?? $payment['transaction_id'] ?? '');
        $secret = (string) ($this->setting('secret_key') ?? '');
        if ($intent === '' || $secret === '') {
            return ['success' => false, 'transaction_id' => null, 'status' => 'pending', 'message' => 'Missing payment intent.', 'raw' => null];
        }

        $response = $this->http(
            $this->apiUrl() . '/payment_intents/' . urlencode($intent),
            ['Authorization' => 'Bearer ' . $secret],
            null,
            'GET'
        );

        $decoded = is_array($response['decoded']) ? $response['decoded'] : [];
        $status  = (string) ($decoded['status'] ?? 'pending');
        $ok      = $status === 'succeeded';

        if ($ok) {
            $this->markPaid($payment, $intent, ['raw' => json_encode($decoded)]);
            $this->logProcessed($payment, $intent);
        }

        return [
            'success'        => $ok,
            'transaction_id' => $intent,
            'status'         => $status,
            'message'        => $ok ? 'Payment verified.' : 'Payment not completed.',
            'raw'            => $decoded,
        ];
    }

    /**
     * Verify a Stripe webhook signature (Stripe-Signature header).
     */
    public function verifyWebhook(string $payload, string $signature): bool
    {
        $secret = (string) ($this->setting('webhook_secret') ?? '');
        if ($secret === '' || $payload === '' || $signature === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signature) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) === 2) {
                $parts[$kv[0]] = $kv[1];
            }
        }
        if (!isset($parts['t'], $parts['v1'])) {
            return false;
        }

        $expected = hash_hmac('sha256', $parts['t'] . '.' . $payload, $secret);

        return hash_equals($expected, $parts['v1']);
    }
}