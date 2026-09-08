<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaypalGatewayAdapter implements GatewayAdapterInterface
{
    use PaymentSideEffects;
    use VerifiesWebhookSignature;

    /**
     * Initialize a PayPal Checkout order.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function initialize(Payment $payment, PaymentGateway $gateway, array $options = []): array
    {
        $token = $this->accessToken($gateway);
        $base = $this->baseUrl($gateway);

        $response = Http::withToken($token)
            ->post("{$base}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $payment->invoice_number,
                    'description' => $payment->description ?? "Payment {$payment->invoice_number}",
                    'amount' => [
                        'currency_code' => $gateway->currency ?: 'USD',
                        'value' => number_format($payment->total_amount, 2, '.', ''),
                    ],
                ]],
            ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to create PayPal order: '.$response->body());
        }

        $order = $response->json();
        $orderId = $order['id'] ?? null;

        $approveLink = collect($order['links'] ?? [])
            ->first(fn ($link) => ($link['rel'] ?? '') === 'approve')['href'] ?? null;

        $payment->update([
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'paypal_order_id' => $orderId,
            ]),
        ]);

        return [
            'success' => true,
            'gateway' => 'paypal',
            'payment_id' => $payment->id,
            'invoice_number' => $payment->invoice_number,
            'amount' => $payment->total_amount,
            'currency' => $payment->currency ?? $gateway->currency,
            'redirect_url' => $approveLink,
            'payment_details' => [
                'order_id' => $orderId,
                'approval_url' => $approveLink,
            ],
        ];
    }

    /**
     * Process a PayPal webhook/IPN event.
     *
     * @param  array<string, mixed>  $data
     */
    public function processCallback(array $data, PaymentGateway $gateway): Payment
    {
        $orderId = $data['resource']['supplementary_data']['related_ids']['order_id']
            ?? $data['resource']['order_id']
            ?? $data['order_id']
            ?? null;

        if (! $orderId) {
            throw new \Exception('PayPal callback missing order reference');
        }

        $payment = Payment::where('payment_details->paypal_order_id', $orderId)->latest()->first();

        if (! $payment) {
            throw new \Exception("Payment not found for PayPal order: {$orderId}");
        }

        $eventType = (string) ($data['event_type'] ?? '');
        $status = strtoupper((string) ($data['resource']['status'] ?? ''));

        if (str_contains($eventType, 'PAYMENT.CAPTURE.COMPLETED') || $status === 'COMPLETED') {
            return $this->complete($payment, $gateway, [
                'transaction_id' => $data['resource']['id'] ?? $orderId,
                'gateway_response' => $data,
            ]);
        }

        if (str_contains($eventType, 'PAYMENT.CAPTURE.DENIED') || $status === 'VOIDED') {
            $payment->update(['payment_status' => Payment::STATUS_FAILED]);
        }

        return $payment;
    }

    /**
     * Verify a PayPal order, capturing it if not yet captured.
     */
    public function verifyPayment(Payment $payment, PaymentGateway $gateway): Payment
    {
        $orderId = $payment->payment_details['paypal_order_id'] ?? null;

        if (! $orderId) {
            return $payment;
        }

        $token = $this->accessToken($gateway);
        $base = $this->baseUrl($gateway);

        $response = Http::withToken($token)->get("{$base}/v2/checkout/orders/{$orderId}");

        if (! $response->successful()) {
            return $payment;
        }

        $order = $response->json();
        $status = strtoupper((string) ($order['status'] ?? ''));

        if ($status === 'APPROVED') {
            $capture = Http::withToken($token)
                ->post("{$base}/v2/checkout/orders/{$orderId}/capture");

            if ($capture->successful()) {
                return $this->complete($payment, $gateway, [
                    'transaction_id' => $orderId,
                    'gateway_response' => $capture->json(),
                ]);
            }

            return $payment;
        }

        if ($status === 'COMPLETED') {
            return $this->complete($payment, $gateway, [
                'transaction_id' => $orderId,
                'gateway_response' => $order,
            ]);
        }

        return $payment;
    }

    /**
     * Verify a PayPal webhook signature (fail-closed).
     *
     * Full PayPal verification validates the transmission cert + webhook ID
     * via /v1/notifications/verify-webhook-signature. For local fail-closed
     * parity we verify the HMAC of the raw body under the configured secret.
     */
    public function verifyWebhookSignature(Request $request, PaymentGateway $gateway): bool
    {
        $secret = $gateway->api_secret;

        if (empty($secret)) {
            return false;
        }

        $expected = (string) $request->header('PayPal-Transmission-Sig', '');

        if ($expected === '') {
            return false;
        }

        $transmissionTime = (string) $request->header('PayPal-Transmission-Time', '');
        $webhookId = (string) $request->header('PayPal-Webhook-Id', '');

        $signedPayload = $webhookId.'|'.$transmissionTime.'|'.$request->getContent();
        $computed = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, $computed);
    }

    /**
     * Refund a captured PayPal payment.
     *
     * @param  array<string, mixed>  $paymentDetails
     * @return array<string, mixed>
     */
    public function refund(PaymentGateway $gateway, string $transactionId, float $amount, string $reason, array $paymentDetails = []): array
    {
        $captureId = $paymentDetails['paypal_capture_id'] ?? $transactionId;

        $token = $this->accessToken($gateway);
        $base = $this->baseUrl($gateway);

        $response = Http::withToken($token)
            ->asJson()
            ->post("{$base}/v2/payments/captures/{$captureId}/refund", [
                'amount' => [
                    'currency_code' => $gateway->currency ?: 'USD',
                    'value' => number_format($amount, 2, '.', ''),
                ],
            ]);

        if ($response->successful()) {
            $refund = $response->json();

            return [
                'success' => true,
                'transaction_id' => $refund['id'] ?? ('REF-'.Str::random(12)),
                'gateway_response' => $refund,
            ];
        }

        return [
            'success' => false,
            'message' => $response->json('message') ?? 'PayPal refund failed',
        ];
    }

    /**
     * Fetch an OAuth2 access token for the PayPal REST API.
     */
    protected function accessToken(PaymentGateway $gateway): string
    {
        $base = $this->baseUrl($gateway);

        $response = Http::withBasicAuth($gateway->api_key, $gateway->api_secret)
            ->asForm()
            ->post("{$base}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        $token = $response->successful() ? $response->json('access_token') : null;

        if (! $token) {
            throw new \Exception('Failed to obtain PayPal access token');
        }

        return $token;
    }

    /**
     * Resolve the PayPal API base URL.
     */
    protected function baseUrl(PaymentGateway $gateway): string
    {
        return $gateway->test_mode
            ? ($gateway->sandbox_url ?: 'https://api-m.sandbox.paypal.com')
            : ($gateway->live_url ?: 'https://api-m.paypal.com');
    }

    /**
     * Mark a PayPal payment as completed and apply side effects.
     *
     * @param  array<string, mixed>  $context
     */
    protected function complete(Payment $payment, PaymentGateway $gateway, array $context = []): Payment
    {
        $payment->update([
            'payment_status' => Payment::STATUS_COMPLETED,
            'paid_amount' => $payment->total_amount,
            'due_amount' => 0,
            'payment_date' => now(),
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'transaction_id' => $context['transaction_id'] ?? $payment->payment_details['transaction_id'] ?? null,
                'paypal_capture_id' => $context['gateway_response']['resource']['id'] ?? $context['gateway_response']['id'] ?? null,
                'gateway_response' => $context['gateway_response'] ?? null,
                'verified_at' => now(),
            ]),
        ]);

        $this->applyPaymentSideEffects($payment, [
            'transaction_id' => $context['transaction_id'] ?? null,
            'gateway' => 'paypal',
        ]);

        event(new \App\Events\PaymentProcessed($payment));

        return $payment;
    }
}
