<?php
declare(strict_types=1);

namespace App\Gateways;

class NagadGateway implements GatewayInterface
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

        $baseUrl = $this->baseUrl();
        $url     = $baseUrl . '/api/checkout/initialize';

        $timestamp  = date('YmdHis');
        $payloadData = $invoiceId . $timestamp . $amount . 'BDT';
        $signature  = hash_hmac('sha256', $payloadData, $this->config['api_secret'] ?? '');

        $payload = [
            'merchantInvoiceNumber' => $invoiceId,
            'amount'                => (string) number_format($amount, 2, '.', ''),
            'currency'              => 'BDT',
            'timestamp'             => $timestamp,
            'signature'             => $signature,
        ];

        $response = $this->curl($url, $payload, [
            'Content-Type: application/json',
        ]);

        if ($response === false) {
            return ['success' => false, 'message' => 'Nagad API connection failed', 'data' => []];
        }

        $result = json_decode($response, true);

        if (isset($result['sensitiveData']) && isset($result['signature'])) {
            return [
                'success' => true,
                'message' => 'Payment initialized successfully',
                'data'    => [
                    'payment_url' => $result['url'] ?? '',
                    'status'      => 'pending',
                ],
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Payment initialization failed',
            'data'    => $result,
        ];
    }

    public function verifyPayment(string $transactionId): bool
    {
        $baseUrl = $this->baseUrl();
        $url     = $baseUrl . '/api/checkout/verify/' . $transactionId;

        $response = $this->curl($url, null, [
            'Content-Type: application/json',
        ]);

        if ($response === false) {
            return false;
        }

        $result = json_decode($response, true);
        return ($result['transactionStatus'] ?? '') === 'Success';
    }

    public function processCallback(array $payload): array
    {
        $paymentId = $payload['merchantInvoiceNumber'] ?? '';
        $status    = $payload['transactionStatus'] ?? '';

        if (empty($paymentId)) {
            return ['success' => false, 'message' => 'Missing invoice number', 'data' => []];
        }

        if ($status === 'Success') {
            return [
                'success' => true,
                'message' => 'Payment completed',
                'data'    => [
                    'transaction_id' => $payload['transactionId'] ?? '',
                    'invoice_id'     => $paymentId,
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
        $baseUrl = $this->baseUrl();
        $url     = $baseUrl . '/api/refund';

        $payload = [
            'transactionId' => $transactionId,
            'amount'        => (string) number_format($amount, 2, '.', ''),
            'reason'        => $reason,
        ];

        $response = $this->curl($url, $payload, [
            'Content-Type: application/json',
        ]);

        if ($response === false) {
            return ['success' => false, 'message' => 'Refund API connection failed', 'data' => []];
        }

        $result = json_decode($response, true);

        if (isset($result['refundTransactionId'])) {
            return [
                'success' => true,
                'message' => 'Refund successful',
                'data'    => [
                    'refund_id' => $result['refundTransactionId'],
                    'status'    => $result['status'] ?? 'completed',
                ],
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Refund failed',
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
