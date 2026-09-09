<?php
declare(strict_types=1);

namespace App\Gateways;

class PaddleGateway implements GatewayInterface
{
    private array $config;

    private const API_BASE = 'https://api.paddle.com';

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function initialize(array $params): array
    {
        $amount    = $params['amount'] ?? 0;
        $invoiceId = $params['invoice_id'] ?? '';
        $currency  = $params['currency'] ?? 'USD';
        $returnUrl = $params['return_url'] ?? '';
        $quantity  = $params['quantity'] ?? 1;

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid amount', 'data' => []];
        }

        $tokenResult = $this->getClientToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        $token   = $tokenResult['data'];
        $payload = [
            'items' => [[
                'name'   => $params['description'] ?? 'Fee Payment',
                'quantity' => $quantity,
                'price'  => [
                    'amount'         => (int) round($amount * 100),
                    'currency_code'  => strtoupper($currency),
                ],
            ]],
            'display_settings' => [
                'theme' => 'dark',
            ],
            'settings' => [
                'return_url'         => $returnUrl,
                'invoice_number'     => $invoiceId,
                'passthrough'        => json_encode(['invoice_id' => $invoiceId]),
            ],
        ];

        $response = $this->apiCall('POST', '/1.0/api/checkouts', $payload, $token);

        if (isset($response['data']['id'])) {
            return [
                'success' => true,
                'message' => 'Checkout created successfully',
                'data'    => [
                    'checkout_id' => $response['data']['id'],
                    'checkout_url' => $response['data']['url'] ?? '',
                    'status'       => 'pending',
                ],
            ];
        }

        $errorMessage = $response['error']['detail'] ?? $response['error']['message'] ?? 'Checkout creation failed';
        return [
            'success' => false,
            'message' => $errorMessage,
            'data'    => $response,
        ];
    }

    public function verifyPayment(string $transactionId): bool
    {
        $tokenResult = $this->getClientToken();
        if (!$tokenResult['success']) {
            return false;
        }

        $token    = $tokenResult['data'];
        $response = $this->apiCall('GET', '/1.0/api/transactions/' . $transactionId, [], $token);

        if (isset($response['data'])) {
            $status = $response['data']['status'] ?? '';
            return in_array($status, ['completed', 'captured'], true);
        }

        return false;
    }

    public function processCallback(array $payload): array
    {
        $event   = $payload['event'] ?? '';
        $data    = $payload['data'] ?? [];

        if (!in_array($event, ['transaction.completed', 'checkout.completed'], true)) {
            return [
                'success' => false,
                'message' => 'Ignoring event: ' . $event,
                'data'    => $payload,
            ];
        }

        $transactionId = $data['id'] ?? '';
        $invoiceId     = '';

        if (!empty($data['invoice_id'])) {
            $invoiceId = $data['invoice_id'];
        } elseif (!empty($data['passthrough'])) {
            $passthrough = json_decode($data['passthrough'], true);
            $invoiceId   = $passthrough['invoice_id'] ?? '';
        }

        $amount = 0;
        if (isset($data['totals']['total'])) {
            $amount = $data['totals']['total'] / 100;
        }

        return [
            'success' => true,
            'message' => 'Payment completed',
            'data'    => [
                'transaction_id' => $transactionId,
                'invoice_id'     => $invoiceId,
                'status'         => 'completed',
                'amount'         => $amount,
                'currency'       => $data['currency_code'] ?? 'USD',
            ],
        ];
    }

    /**
     * Paddle does not support programmatic refunds.
     * Refunds must be processed via the Paddle dashboard.
     */
    public function refund(string $transactionId, float $amount, string $reason): array
    {
        return [
            'success' => false,
            'message' => 'Paddle refunds must be processed manually via the Paddle dashboard',
            'data'    => [
                'transaction_id' => $transactionId,
                'amount'         => $amount,
                'reason'         => $reason,
            ],
        ];
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $webhookKey = $this->config['vendor_auth_code'] ?? '';
        if (empty($webhookKey)) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $webhookKey);
        return hash_equals($expected, $signature);
    }

    private function getClientToken(): array
    {
        $response = $this->apiCall('POST', '/1.0/auth/client-token', []);

        if (isset($response['data']['token'])) {
            return ['success' => true, 'message' => 'Token obtained', 'data' => $response['data']['token']];
        }

        return [
            'success' => false,
            'message' => $response['error']['detail'] ?? 'Client token request failed',
            'data'    => $response,
        ];
    }

    private function apiCall(string $method, string $endpoint, array $data = [], string $token = ''): array
    {
        $url = self::API_BASE . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if (!empty($token)) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            return ['error' => ['message' => 'Paddle API connection failed']];
        }

        return json_decode($response, true) ?? ['error' => ['message' => 'Invalid response from Paddle']];
    }
}
