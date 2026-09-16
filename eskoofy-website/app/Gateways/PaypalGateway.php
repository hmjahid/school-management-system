<?php
declare(strict_types=1);

namespace App\Gateways;

/**
 * PayPal payment gateway (international). Orders v2 / capture.
 */
class PaypalGateway extends AbstractGateway
{
    public function id(): string
    {
        return 'paypal';
    }

    public function name(): string
    {
        return 'PayPal';
    }

    public function isConfigured(): bool
    {
        return (string) ($this->setting('client_id') ?? '') !== ''
            && (string) ($this->setting('client_secret') ?? '') !== '';
    }

    private function baseUrl(): string
    {
        return ($this->setting('test_mode') ?? true) === true
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    private function accessToken(): ?string
    {
        $clientId     = (string) ($this->setting('client_id') ?? '');
        $clientSecret = (string) ($this->setting('client_secret') ?? '');
        if ($clientId === '' || $clientSecret === '') {
            return null;
        }

        $response = $this->http(
            $this->baseUrl() . '/v1/oauth2/token',
            [
                'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'grant_type=client_credentials'
        );

        $decoded = is_array($response['decoded']) ? $response['decoded'] : [];

        return $response['ok'] && isset($decoded['access_token']) ? (string) $decoded['access_token'] : null;
    }

    public function process(array $order, array $payment): array
    {
        $amount    = (float) ($order['amount'] ?? $payment['amount'] ?? 0);
        $currency  = strtoupper((string) ($order['currency'] ?? $payment['currency'] ?? 'USD'));
        $reference = (string) ($payment['reference'] ?? '');
        $token     = $this->accessToken();

        if ($amount <= 0) {
            return ['success' => false, 'transaction_id' => null, 'message' => 'Invalid amount', 'raw' => null];
        }
        if ($token === null) {
            return ['success' => false, 'transaction_id' => null, 'message' => 'PayPal is not configured.', 'raw' => null];
        }

        $response = $this->http(
            $this->baseUrl() . '/v2/checkout/orders',
            [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'Prefer'        => 'return=representation',
            ],
            json_encode([
                'intent'              => 'CAPTURE',
                'purchase_units'      => [[
                    'reference_id' => $reference,
                    'amount'       => [
                        'currency_code' => $currency,
                        'value'         => number_format($amount, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => $order['return_url'] ?? '',
                    'cancel_url' => $order['cancel_url'] ?? '',
                ],
            ])
        );

        $decoded = is_array($response['decoded']) ? $response['decoded'] : [];

        if ($response['ok'] && isset($decoded['id'])) {
            $approve = '';
            foreach ($decoded['links'] ?? [] as $link) {
                if (($link['rel'] ?? '') === 'approve') {
                    $approve = (string) ($link['href'] ?? '');
                    break;
                }
            }

            return [
                'success'        => true,
                'transaction_id' => (string) $decoded['id'],
                'message'        => 'PayPal order created.',
                'redirect_url'   => $approve,
                'raw'            => $decoded,
            ];
        }

        return [
            'success'        => false,
            'transaction_id' => null,
            'message'        => $decoded['message'] ?? 'PayPal order creation failed.',
            'raw'            => $decoded,
        ];
    }

    public function verify(array $payment, array $data = []): array
    {
        $orderId = (string) ($data['order_id'] ?? $payment['transaction_id'] ?? '');
        $token   = $this->accessToken();
        if ($orderId === '' || $token === null) {
            return ['success' => false, 'transaction_id' => null, 'status' => 'pending', 'message' => 'Missing order / PayPal not configured.', 'raw' => null];
        }

        $response = $this->http(
            $this->baseUrl() . '/v2/checkout/orders/' . urlencode($orderId) . '/capture',
            [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            '{}'
        );

        $decoded = is_array($response['decoded']) ? $response['decoded'] : [];
        $status  = (string) ($decoded['status'] ?? '');
        $ok      = $status === 'COMPLETED';

        if ($ok) {
            $this->markPaid($payment, $orderId, ['raw' => json_encode($decoded)]);
            $this->logProcessed($payment, $orderId);
        }

        return [
            'success'        => $ok,
            'transaction_id' => $orderId,
            'status'         => strtolower($status),
            'message'        => $ok ? 'Payment verified.' : 'Payment not completed.',
            'raw'            => $decoded,
        ];
    }
}