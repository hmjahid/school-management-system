<?php
declare(strict_types=1);

namespace App\Gateways;

class PaypalGateway implements GatewayInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    private function baseUrl(): string
    {
        return ($this->config['mode'] ?? 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function initialize(array $params): array
    {
        $amount    = $params['amount'] ?? 0;
        $invoiceId = $params['invoice_id'] ?? '';
        $currency  = $params['currency'] ?? 'USD';
        $returnUrl = $params['return_url'] ?? '';
        $cancelUrl = $params['cancel_url'] ?? '';

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid amount', 'data' => []];
        }

        $tokenResult = $this->getAccessToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        $token     = $tokenResult['data']['access_token'] ?? '';
        $baseUrl   = $this->baseUrl();
        $url       = $baseUrl . '/v2/checkout/orders';

        $payload = json_encode([
            'intent'              => 'CAPTURE',
            'purchase_units'      => [[
                'reference_id'     => $invoiceId,
                'amount'           => [
                    'currency_code' => $currency,
                    'value'         => number_format($amount, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
        ]);

        $response = $this->curl($url, $payload, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ]);

        if ($response === false) {
            return ['success' => false, 'message' => 'PayPal API connection failed', 'data' => []];
        }

        $result = json_decode($response, true);

        if (isset($result['id'])) {
            $approvalUrl = '';
            foreach ($result['links'] ?? [] as $link) {
                if (($link['rel'] ?? '') === 'approve') {
                    $approvalUrl = $link['href'] ?? '';
                    break;
                }
            }

            return [
                'success' => true,
                'message' => 'Order created successfully',
                'data'    => [
                    'order_id'     => $result['id'],
                    'approval_url' => $approvalUrl,
                    'status'       => $result['status'] ?? 'CREATED',
                ],
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Order creation failed',
            'data'    => $result,
        ];
    }

    public function verifyPayment(string $transactionId): bool
    {
        $tokenResult = $this->getAccessToken();
        if (!$tokenResult['success']) {
            return false;
        }

        $token   = $tokenResult['data']['access_token'] ?? '';
        $baseUrl = $this->baseUrl();
        $url     = $baseUrl . '/v2/checkout/orders/' . $transactionId;

        $response = $this->curl($url, null, [
            'Authorization: Bearer ' . $token,
        ]);

        if ($response === false) {
            return false;
        }

        $result = json_decode($response, true);
        return ($result['status'] ?? '') === 'APPROVED';
    }

    public function processCallback(array $payload): array
    {
        $eventType = $payload['event_type'] ?? '';
        $resource  = $payload['resource'] ?? [];

        $validEvents = ['PAYMENT.CAPTURE.COMPLETED', 'CHECKOUT.ORDER.APPROVED'];
        if (!in_array($eventType, $validEvents, true)) {
            return [
                'success' => false,
                'message' => 'Ignoring event: ' . $eventType,
                'data'    => $payload,
            ];
        }

        $orderId = $resource['custom_id'] ?? $resource['id'] ?? '';

        return [
            'success' => true,
            'message' => 'Payment verified',
            'data'    => [
                'transaction_id' => $orderId,
                'order_id'       => $resource['id'] ?? '',
                'status'         => 'completed',
                'amount'         => $resource['amount']['value'] ?? 0,
                'currency'       => $resource['amount']['currency_code'] ?? 'USD',
            ],
        ];
    }

    public function refund(string $transactionId, float $amount, string $reason): array
    {
        $tokenResult = $this->getAccessToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        $token   = $tokenResult['data']['access_token'] ?? '';
        $baseUrl = $this->baseUrl();
        $url     = $baseUrl . '/v2/payments/capture/' . $transactionId . '/refund';

        $payload = json_encode([
            'amount'    => [
                'value'         => number_format($amount, 2, '.', ''),
                'currency_code' => 'USD',
            ],
            'note_to_payer' => $reason,
        ]);

        $response = $this->curl($url, $payload, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);

        if ($response === false) {
            return ['success' => false, 'message' => 'Refund API connection failed', 'data' => []];
        }

        $result = json_decode($response, true);

        if (isset($result['id'])) {
            return [
                'success' => true,
                'message' => 'Refund successful',
                'data'    => [
                    'refund_id' => $result['id'],
                    'status'    => $result['status'] ?? 'COMPLETED',
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
        $tokenResult = $this->getAccessToken();
        if (!$tokenResult['success']) {
            return false;
        }

        $token   = $tokenResult['data']['access_token'] ?? '';
        $baseUrl = $this->baseUrl();
        $url     = $baseUrl . '/v1/notifications/verify-webhook-signature';

        $headers = [];
        foreach (explode("\n", $signature) as $line) {
            if (preg_match('/^([a-zA-Z-]+):\s*(.+)$/', $line, $m)) {
                $headers[$m[1]] = $m[2];
            }
        }

        $body = json_encode([
            'auth_algo'       => $headers['PayPal-Auth-Algo'] ?? '',
            'cert_url'        => $headers['PayPal-Cert-Url'] ?? '',
            'transmission_id' => $headers['PayPal-Transmission-Id'] ?? '',
            'timestamp'       => $headers['PayPal-Transmission-Time'] ?? '',
            'webhook_id'      => $this->config['webhook_id'] ?? '',
            'webhook_event'   => json_decode($payload, true),
        ]);

        $response = $this->curl($url, $body, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);

        if ($response === false) {
            return false;
        }

        $result = json_decode($response, true);
        return ($result['verification_status'] ?? '') === 'SUCCESS';
    }

    private function getAccessToken(): array
    {
        $baseUrl = $this->baseUrl();
        $url     = $baseUrl . '/v1/oauth2/token';

        $credentials = base64_encode(
            ($this->config['client_id'] ?? '') . ':' . ($this->config['client_secret'] ?? '')
        );

        $response = $this->curl($url, 'grant_type=client_credentials', [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        if ($response === false) {
            return ['success' => false, 'message' => 'PayPal auth failed', 'data' => []];
        }

        $result = json_decode($response, true);

        if (isset($result['access_token'])) {
            return ['success' => true, 'message' => 'Token obtained', 'data' => $result];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Token request failed',
            'data'    => $result,
        ];
    }

    private function curl(string $url, string|array|null $data, array $headers = []): string|false
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        if ($data !== null) {
            if (is_string($data)) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
