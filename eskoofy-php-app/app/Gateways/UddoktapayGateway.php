<?php
declare(strict_types=1);

namespace App\Gateways;

/**
 * UddoktaPay gateway (Bangladesh aggregator: bKash/Nagad/Rocket/Upay/bank).
 *
 * A single hosted checkout covers every Bangladeshi method, so one integration
 * replaces the individual MFS gateways. API reference:
 * https://uddoktapay.readme.io/reference/overview
 */
class UddoktapayGateway implements GatewayInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    private function baseUrl(): string
    {
        return ($this->config['test_mode'] ?? true)
            ? ($this->config['sandbox_url'] ?? 'https://sandbox.uddoktapay.com/api')
            : ($this->config['live_url'] ?? 'https://pay.uddoktapay.com/api');
    }

    private function apiKey(): string
    {
        return (string) ($this->config['api_key'] ?? '');
    }

    private function appUrl(): string
    {
        return rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
    }

    public function initialize(array $params): array
    {
        $amount = (float) ($params['amount'] ?? 0);

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid amount', 'data' => []];
        }

        if ($this->apiKey() === '') {
            return ['success' => false, 'message' => 'UddoktaPay is not configured.', 'data' => []];
        }

        $notificationId = (string) ($params['invoice_id'] ?? '');
        $paymentId = (int) ($params['payment_id'] ?? 0);
        $returnUrl = $this->appUrl() . '/payments/status/' . $paymentId;

        $response = $this->curl($this->baseUrl() . '/checkout-v2', [
            'full_name'    => (string) ($params['customer_name'] ?? 'Customer'),
            'email'        => (string) ($params['customer_email'] ?? ($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com')),
            'amount'       => number_format($amount, 2, '.', ''),
            'metadata'     => [
                'payment_id'     => $paymentId,
                'invoice_number' => $notificationId,
            ],
            'redirect_url' => $returnUrl,
            'return_type'  => 'GET',
            'cancel_url'   => $returnUrl . '?status=cancelled',
            'webhook_url'  => $this->appUrl() . '/payments/webhook/uddoktapay',
        ]);

        if ($response === false) {
            return ['success' => false, 'message' => 'UddoktaPay API connection failed', 'data' => []];
        }

        $result = json_decode($response, true);

        if (is_array($result) && ($result['status'] ?? false) === true && !empty($result['payment_url'])) {
            return [
                'success' => true,
                'message' => $result['message'] ?? 'Payment created successfully',
                'data'    => [
                    'payment_url'    => $result['payment_url'],
                    'payment_id'     => $paymentId,
                    'invoice_number' => $notificationId,
                    'status'         => 'pending',
                ],
            ];
        }

        return [
            'success' => false,
            'message' => is_array($result) ? ($result['message'] ?? 'Payment creation failed') : 'Payment creation failed',
            'data'    => is_array($result) ? $result : [],
        ];
    }

    public function verifyPayment(string $transactionId): bool
    {
        $result = $this->verifyInvoice($transactionId);

        return ($result['status'] ?? '') === 'COMPLETED';
    }

    public function processCallback(array $payload): array
    {
        $invoiceId = (string) ($payload['invoice_id'] ?? '');

        if ($invoiceId === '') {
            return ['success' => false, 'message' => 'Missing invoice ID', 'data' => $payload];
        }

        $result = $this->verifyInvoice($invoiceId);

        $metadata = $result['metadata'] ?? ($payload['metadata'] ?? []);
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        $status = strtoupper((string) ($result['status'] ?? ''));

        if ($status === 'COMPLETED') {
            return [
                'success' => true,
                'message' => 'Payment completed',
                'data'    => [
                    'transaction_id' => $result['transaction_id'] ?? $invoiceId,
                    'invoice_id'     => $invoiceId,
                    'payment_method' => $result['payment_method'] ?? null,
                    'payment_id'     => $metadata['payment_id'] ?? null,
                    'amount'         => $result['amount'] ?? ($payload['amount'] ?? 0),
                    'currency'       => 'BDT',
                    'status'         => 'completed',
                    'raw'            => $result,
                ],
            ];
        }

        return [
            'success' => false,
            'message' => 'Payment not completed: ' . ($status !== '' ? $status : 'unknown'),
            'data'    => $result,
        ];
    }

    public function refund(string $transactionId, float $amount, string $reason): array
    {
        if ($this->apiKey() === '') {
            return ['success' => false, 'message' => 'UddoktaPay is not configured.', 'data' => []];
        }

        $response = $this->curl($this->baseUrl() . '/refund-payment', [
            'transaction_id' => $transactionId,
            'payment_method' => (string) ($this->config['refund_payment_method'] ?? 'bkash'),
            'amount'         => number_format($amount, 2, '.', ''),
            'product_name'   => (string) ($_ENV['APP_NAME'] ?? 'Payment'),
            'reason'         => $reason,
        ]);

        if ($response === false) {
            return ['success' => false, 'message' => 'Refund API connection failed', 'data' => []];
        }

        $result = json_decode($response, true);

        if (is_array($result) && ($result['status'] ?? true) !== false) {
            return [
                'success' => true,
                'message' => 'Refund submitted',
                'data'    => ['refund_id' => $result['transaction_id'] ?? $transactionId],
            ];
        }

        return [
            'success' => false,
            'message' => is_array($result) ? ($result['message'] ?? 'Refund failed') : 'Refund failed',
            'data'    => is_array($result) ? $result : [],
        ];
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $apiKey = $this->apiKey();

        if ($apiKey === '' || $signature === '') {
            return false;
        }

        // UddoktaPay echoes the configured API key in the RT-UDDOKTAPAY-API-KEY header.
        return hash_equals($apiKey, $signature);
    }

    /**
     * @return array<string, mixed>
     */
    private function verifyInvoice(string $invoiceId): array
    {
        if ($invoiceId === '' || $this->apiKey() === '') {
            return [];
        }

        $response = $this->curl($this->baseUrl() . '/verify-payment', [
            'invoice_id' => $invoiceId,
        ]);

        if ($response === false) {
            return [];
        }

        $result = json_decode($response, true);

        return is_array($result) ? $result : [];
    }

    private function curl(string $url, array $data): string|false
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'RT-UDDOKTAPAY-API-KEY: ' . $this->apiKey(),
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}
