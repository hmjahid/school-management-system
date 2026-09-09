<?php
declare(strict_types=1);

namespace App\Gateways;

class RocketGateway implements GatewayInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    private function baseUrl(): string
    {
        return ($this->config['test_mode'] ?? true)
            ? ($this->config['sandbox_url'] ?? '')
            : ($this->config['live_url'] ?? '');
    }

    public function initialize(array $params): array
    {
        $amount   = $params['amount'] ?? 0;
        $invoiceId = $params['invoice_id'] ?? '';

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid amount', 'data' => []];
        }

        $tokenResult = $this->requestToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        $token   = $tokenResult['data']['id_token'] ?? '';
        $baseUrl = $this->baseUrl();
        $url     = $baseUrl . '/checkout/payment/create';

        $payload = [
            'amount'         => (string) number_format($amount, 2, '.', ''),
            'currency'       => 'BDT',
            'intent'         => 'sale',
            'merchantInvoiceNumber' => $invoiceId,
        ];

        $response = $this->curl($url, $payload, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);

        if ($response === false) {
            return ['success' => false, 'message' => 'Rocket API connection failed', 'data' => []];
        }

        $result = json_decode($response, true);

        if (isset($result['paymentID'])) {
            return [
                'success' => true,
                'message' => 'Payment created successfully',
                'data'    => [
                    'payment_id'  => $result['paymentID'],
                    'payment_url' => $result['redirect_url'] ?? '',
                    'status'      => $result['transactionStatus'] ?? 'pending',
                ],
            ];
        }

        return [
            'success' => false,
            'message' => $result['errorMessage'] ?? 'Payment creation failed',
            'data'    => $result,
        ];
    }

    public function verifyPayment(string $transactionId): bool
    {
        $tokenResult = $this->requestToken();
        if (!$tokenResult['success']) {
            return false;
        }

        $token   = $tokenResult['data']['id_token'] ?? '';
        $baseUrl = $this->baseUrl();
        $url     = $baseUrl . '/checkout/payment/executed/' . $transactionId;

        $response = $this->curl($url, null, [
            'Authorization: Bearer ' . $token,
        ]);

        if ($response === false) {
            return false;
        }

        $result = json_decode($response, true);
        return ($result['transactionStatus'] ?? '') === 'Completed';
    }

    public function processCallback(array $payload): array
    {
        $paymentId = $payload['paymentID'] ?? '';
        $status    = $payload['transactionStatus'] ?? '';

        if (empty($paymentId)) {
            return ['success' => false, 'message' => 'Missing payment ID', 'data' => []];
        }

        if ($status === 'Completed') {
            return [
                'success' => true,
                'message' => 'Payment completed',
                'data'    => [
                    'transaction_id' => $paymentId,
                    'status'         => 'completed',
                    'amount'         => $payload['amount'] ?? 0,
                    'currency'       => $payload['currency'] ?? 'BDT',
                ],
            ];
        }

        return [
            'success' => false,
            'message' => 'Payment not completed: ' . $status,
            'data'    => $payload,
        ];
    }

    public function refund(string $transactionId, float $amount, string $reason): array
    {
        $tokenResult = $this->requestToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        $token   = $tokenResult['data']['id_token'] ?? '';
        $baseUrl = $this->baseUrl();
        $url     = $baseUrl . '/checkout/payment/refund';

        $payload = [
            'paymentID' => $transactionId,
            'amount'    => (string) number_format($amount, 2, '.', ''),
            'reason'    => $reason,
        ];

        $response = $this->curl($url, $payload, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);

        if ($response === false) {
            return ['success' => false, 'message' => 'Refund API connection failed', 'data' => []];
        }

        $result = json_decode($response, true);

        if (isset($result['refundTrxID'])) {
            return [
                'success' => true,
                'message' => 'Refund successful',
                'data'    => [
                    'refund_id' => $result['refundTrxID'],
                    'status'    => $result['statusMessage'] ?? 'completed',
                ],
            ];
        }

        return [
            'success' => false,
            'message' => $result['errorMessage'] ?? 'Refund failed',
            'data'    => $result,
        ];
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = $this->config['api_secret'] ?? '';
        if (empty($secret)) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    private function requestToken(): array
    {
        $baseUrl = $this->baseUrl();
        $url     = $baseUrl . '/checkout/oauth/token';

        $payload = [
            'app_key'    => $this->config['api_key'] ?? '',
            'app_secret' => $this->config['api_secret'] ?? '',
        ];

        $response = $this->curl($url, $payload, [
            'Content-Type: application/json',
        ]);

        if ($response === false) {
            return ['success' => false, 'message' => 'Rocket auth failed', 'data' => []];
        }

        $result = json_decode($response, true);

        if (isset($result['id_token'])) {
            return ['success' => true, 'message' => 'Token obtained', 'data' => $result];
        }

        return [
            'success' => false,
            'message' => $result['errorMessage'] ?? 'Token request failed',
            'data'    => $result,
        ];
    }

    private function curl(string $url, ?array $data, array $headers = []): string|false
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_POST           => $data !== null,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
