<?php
declare(strict_types=1);

namespace App\Gateways;

/**
 * Config-driven hosted-checkout gateway (raw-PHP port).
 *
 * Serves the international gateways that ship disabled by default (Google Pay,
 * Apple Pay, Razorpay, Paystack, Flutterwave, SSLCommerz, Square, Mollie,
 * Authorize.Net, Xendit, Adyen, Skrill) and any gateway an admin adds manually,
 * without vendor-specific code.
 *
 * Config keys (payment_gateways row or config/payment.php fallback):
 *   sandbox_url / live_url  hosted checkout endpoint (picked via test_mode)
 *   api_key / api_secret    credentials, api_secret also signs webhooks
 *   checkout_method         GET (default) | POST
 *   checkout_url_template   optional URL with {amount} {currency} {reference}
 *                           {invoice} {callback} {cancel} {api_key} placeholders
 *   verify_url              server-side verification endpoint
 *   verify_success_path     JSON path in the verify response (default: status)
 *   verify_success_value    expected value at that path (default: COMPLETED)
 *   refund_url              refund endpoint; absence means refunds are unsupported
 *   signature_header        webhook signature header (default: X-Webhook-Signature)
 */
class GenericHostedGateway implements GatewayInterface
{
    private const SUCCESS_VALUES = ['COMPLETED', 'SUCCESS', 'SUCCEEDED', 'PAID', 'CAPTURED', 'SETTLED', 'OK'];
    private const FAILURE_VALUES = ['FAILED', 'CANCELLED', 'CANCELED', 'EXPIRED', 'ERROR', 'DECLINED'];

    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function initialize(array $params): array
    {
        $amount = (float) ($params['amount'] ?? 0);

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid amount', 'data' => []];
        }

        $url = $this->buildCheckoutUrl([
            'amount'    => number_format($amount, 2, '.', ''),
            'currency'  => (string) ($params['currency'] ?? $this->config['currency'] ?? 'USD'),
            'reference' => (string) ($params['invoice_id'] ?? ''),
            'invoice'   => (string) ($params['invoice_id'] ?? ''),
            'callback'  => $this->callbackUrl($params),
            'cancel'    => (string) ($params['cancel_url'] ?? $this->callbackUrl($params)),
            'api_key'   => (string) ($this->config['api_key'] ?? ''),
        ]);

        if ($url === '') {
            return ['success' => false, 'message' => ($this->config['name'] ?? 'This gateway') . ' is not configured.', 'data' => []];
        }

        return [
            'success' => true,
            'message' => 'Payment created successfully',
            'data'    => [
                'payment_url'    => $url,
                'payment_id'     => (int) ($params['payment_id'] ?? 0),
                'invoice_number' => (string) ($params['invoice_id'] ?? ''),
                'status'         => 'pending',
            ],
        ];
    }

    public function verifyPayment(string $transactionId): bool
    {
        $verifyUrl = (string) ($this->config['verify_url'] ?? '');

        if ($verifyUrl === '' || $transactionId === '') {
            return false;
        }

        $result = $this->verifyReference($transactionId);

        return $this->isSuccess($this->statusFrom($result));
    }

    public function processCallback(array $payload): array
    {
        $metadata = $payload['metadata'] ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        $reference = (string) ($payload['reference'] ?? $payload['invoice_id'] ?? $payload['invoice_number'] ?? $metadata['invoice_number'] ?? '');
        $paymentId = $metadata['payment_id'] ?? ($payload['payment_id'] ?? null);

        $verifyUrl = (string) ($this->config['verify_url'] ?? '');
        $result = ($verifyUrl !== '' && $reference !== '') ? $this->verifyReference($reference) : $payload;

        $status = $this->statusFrom($result);

        if ($this->isSuccess($status)) {
            return [
                'success' => true,
                'message' => 'Payment completed',
                'data'    => [
                    'transaction_id' => $result['transaction_id'] ?? $result['id'] ?? $reference,
                    'invoice_id'     => $reference,
                    'payment_id'     => $paymentId,
                    'amount'         => $result['amount'] ?? ($payload['amount'] ?? 0),
                    'currency'       => $this->config['currency'] ?? 'USD',
                    'status'         => 'completed',
                    'raw'            => $result,
                ],
            ];
        }

        return [
            'success' => false,
            'message' => 'Payment not completed' . ($status !== '' ? ': ' . $status : ''),
            'data'    => array_merge(['payment_id' => $paymentId, 'invoice_id' => $reference], is_array($result) ? $result : []),
        ];
    }

    public function refund(string $transactionId, float $amount, string $reason): array
    {
        $refundUrl = (string) ($this->config['refund_url'] ?? '');

        if ($refundUrl === '') {
            return ['success' => false, 'message' => 'Refunds are not supported for this gateway.', 'data' => []];
        }

        $response = $this->curl($refundUrl, [
            'transaction_id' => $transactionId,
            'amount'         => number_format($amount, 2, '.', ''),
            'currency'       => $this->config['currency'] ?? 'USD',
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
        $secret = (string) ($this->config['api_secret'] ?? '');

        if ($secret !== '') {
            return $signature !== '' && hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
        }

        $apiKey = (string) ($this->config['api_key'] ?? '');

        return $apiKey !== '' && $signature !== '' && hash_equals($apiKey, $signature);
    }

    /**
     * @param array<string, string> $params
     */
    private function buildCheckoutUrl(array $params): string
    {
        $template = (string) ($this->config['checkout_url_template'] ?? '');

        if ($template !== '') {
            $replacements = [];
            foreach ($params as $key => $value) {
                $replacements['{' . $key . '}'] = rawurlencode($value);
            }

            return strtr($template, $replacements);
        }

        $base = $this->baseUrl();

        if ($base === '') {
            return '';
        }

        $query = array_filter([
            'amount'       => $params['amount'],
            'currency'     => $params['currency'],
            'reference'    => $params['reference'],
            'callback_url' => $params['callback'],
        ], static fn ($value) => $value !== '' && $value !== null);

        return $base . (str_contains($base, '?') ? '&' : '?') . http_build_query($query);
    }

    private function baseUrl(): string
    {
        $base = ($this->config['test_mode'] ?? true)
            ? ($this->config['sandbox_url'] ?? '')
            : ($this->config['live_url'] ?? '');

        return rtrim((string) $base, '/');
    }

    private function callbackUrl(array $params): string
    {
        if (!empty($params['return_url'])) {
            return (string) $params['return_url'];
        }

        $paymentId = (int) ($params['payment_id'] ?? 0);

        return rtrim((string) ($_ENV['APP_URL'] ?? ''), '/') . '/payments/status/' . $paymentId;
    }

    /**
     * @return array<string, mixed>
     */
    private function verifyReference(string $reference): array
    {
        $verifyUrl = (string) ($this->config['verify_url'] ?? '');

        if ($verifyUrl === '') {
            return [];
        }

        $response = $this->curl($verifyUrl, [
            'reference'      => $reference,
            'transaction_id' => $reference,
            'currency'       => $this->config['currency'] ?? 'USD',
        ]);

        if ($response === false) {
            return [];
        }

        $result = json_decode($response, true);

        return is_array($result) ? $result : [];
    }

    /**
     * @param array<string, mixed> $result
     */
    private function statusFrom(array $result): string
    {
        $path = (string) ($this->config['verify_success_path'] ?? 'status');
        $value = $result;

        foreach (explode('.', $path) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return '';
            }
        }

        return strtoupper((string) (is_scalar($value) ? $value : ''));
    }

    private function isSuccess(string $status): bool
    {
        if (in_array($status, self::SUCCESS_VALUES, true)) {
            return true;
        }

        $expected = strtoupper((string) ($this->config['verify_success_value'] ?? ''));

        return $expected !== '' && $expected === $status;
    }

    private function curl(string $url, array $data): string|false
    {
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        $apiKey = (string) ($this->config['api_key'] ?? '');

        if ($apiKey !== '') {
            $headers[] = 'X-API-Key: ' . $apiKey;
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}
