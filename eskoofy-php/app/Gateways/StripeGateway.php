<?php
declare(strict_types=1);

namespace App\Gateways;

class StripeGateway implements GatewayInterface
{
    private array $config;

    private const API_BASE = 'https://api.stripe.com/v1';

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function initialize(array $params): array
    {
        $amount   = $params['amount'] ?? 0;
        $invoiceId = $params['invoice_id'] ?? '';
        $currency = $params['currency'] ?? 'usd';
        $description = $params['description'] ?? 'Payment for ' . $invoiceId;

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid amount', 'data' => []];
        }

        // Stripe expects amount in cents
        $amountCents = (int) round($amount * 100);

        $payload = [
            'amount'               => $amountCents,
            'currency'             => strtolower($currency),
            'description'          => $description,
            'metadata[invoice_id]' => $invoiceId,
        ];

        if (!empty($params['student_id'])) {
            $payload['metadata[student_id]'] = $params['student_id'];
        }

        $response = $this->apiCall('POST', '/payment_intents', $payload);

        if (isset($response['id'])) {
            return [
                'success' => true,
                'message' => 'PaymentIntent created',
                'data'    => [
                    'payment_intent_id' => $response['id'],
                    'client_secret'     => $response['client_secret'],
                    'status'            => $response['status'] ?? 'requires_payment_method',
                    'publishable_key'   => $this->config['publishable'] ?? '',
                ],
            ];
        }

        return [
            'success' => false,
            'message' => $response['error']['message'] ?? 'PaymentIntent creation failed',
            'data'    => $response,
        ];
    }

    public function verifyPayment(string $transactionId): bool
    {
        $response = $this->apiCall('GET', '/payment_intents/' . $transactionId);
        return ($response['status'] ?? '') === 'succeeded';
    }

    public function processCallback(array $payload): array
    {
        $type    = $payload['type'] ?? '';
        $data    = $payload['data']['object'] ?? [];

        if ($type !== 'payment_intent.succeeded') {
            return [
                'success' => false,
                'message' => 'Ignoring event type: ' . $type,
                'data'    => $payload,
            ];
        }

        $paymentIntentId = $data['id'] ?? '';

        if (empty($paymentIntentId)) {
            return ['success' => false, 'message' => 'Missing payment intent ID', 'data' => []];
        }

        return [
            'success' => true,
            'message' => 'Payment completed',
            'data'    => [
                'transaction_id'    => $paymentIntentId,
                'status'            => 'completed',
                'amount'            => ($data['amount'] ?? 0) / 100,
                'currency'          => $data['currency'] ?? 'usd',
                'invoice_id'        => $data['metadata']['invoice_id'] ?? '',
                'payment_method'    => $data['payment_method'] ?? '',
            ],
        ];
    }

    public function refund(string $transactionId, float $amount, string $reason): array
    {
        $payload = [
            'payment_intent' => $transactionId,
            'reason'         => $reason ?: 'requested_by_customer',
        ];

        if ($amount > 0) {
            $payload['amount'] = (int) round($amount * 100);
        }

        $response = $this->apiCall('POST', '/refunds', $payload);

        if (isset($response['id'])) {
            return [
                'success' => true,
                'message' => 'Refund successful',
                'data'    => [
                    'refund_id' => $response['id'],
                    'status'    => $response['status'] ?? 'succeeded',
                    'amount'    => ($response['amount'] ?? 0) / 100,
                ],
            ];
        }

        return [
            'success' => false,
            'message' => $response['error']['message'] ?? 'Refund failed',
            'data'    => $response,
        ];
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = $this->config['webhook_secret'] ?? '';
        if (empty($secret)) {
            return false;
        }

        $parts = explode(',', $signature);
        $timestamp = '';
        $v1Sig    = '';

        foreach ($parts as $part) {
            [$key, $value] = explode('=', $part, 2);
            if ($key === 't') $timestamp = $value;
            if ($key === 'v1') $v1Sig = $value;
        }

        if (empty($timestamp) || empty($v1Sig)) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expected       = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, $v1Sig);
    }

    private function apiCall(string $method, string $endpoint, array $data = []): array
    {
        $url = self::API_BASE . $endpoint;

        $headers = [
            'Authorization: Bearer ' . ($this->config['api_key'] ?? ''),
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            return ['error' => ['message' => 'Stripe API connection failed']];
        }

        return json_decode($response, true) ?? ['error' => ['message' => 'Invalid response from Stripe']];
    }
}
