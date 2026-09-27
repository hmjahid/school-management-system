<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * UddoktaPay gateway adapter (Bangladesh).
 *
 * UddoktaPay is a Bangladeshi payment aggregator: a single hosted checkout
 * covers bKash, Nagad, Rocket, Upay and bank transfer, so no per-provider code
 * is required. Flow (see https://uddoktapay.readme.io/reference/overview):
 *
 *  1. POST {base}/checkout-v2            -> { payment_url }
 *  2. customer pays on the hosted page
 *  3. UddoktaPay returns to redirect_url with an `invoice_id`
 *  4. POST {base}/verify-payment         -> { status: COMPLETED|PENDING|ERROR, ... }
 *
 * A successful redirect is NOT authoritative — the payment is only completed
 * after server-side verification.
 */
class UddoktapayGatewayAdapter implements GatewayAdapterInterface
{
    use PaymentSideEffects;

    /**
     * Initialize an UddoktaPay checkout.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function initialize(Payment $payment, PaymentGateway $gateway, array $options = []): array
    {
        $returnUrl = (string) (
            $options['return_url']
            ?? $payment->payment_details['return_url']
            ?? $gateway->callback_url
            ?? url('/')
        );
        $cancelUrl = (string) (
            $options['cancel_url']
            ?? $payment->payment_details['cancel_url']
            ?? $gateway->cancel_url
            ?? $returnUrl
        );

        $response = $this->request($gateway, 'checkout-v2', [
            'full_name' => (string) ($options['customer_name'] ?? $payment->metadata['student_name'] ?? $payment->metadata['customer_name'] ?? 'Customer'),
            'email' => (string) ($options['customer_email'] ?? $payment->metadata['customer_email'] ?? optional($payment->createdBy)->email ?? config('mail.from.address', 'noreply@example.com')),
            'amount' => number_format((float) $payment->total_amount, 2, '.', ''),
            'metadata' => [
                'payment_id' => $payment->id,
                'invoice_number' => $payment->invoice_number,
            ],
            'redirect_url' => $returnUrl,
            'return_type' => 'GET',
            'cancel_url' => $cancelUrl,
            'webhook_url' => $gateway->webhook_url ?: url('/api/payments/uddoktapay/webhook'),
        ]);

        $data = $response->json() ?? [];

        if (! $response->successful() || ($data['status'] ?? false) !== true || empty($data['payment_url'])) {
            Log::error('Failed to initialize UddoktaPay payment', $data);
            throw new \Exception($data['message'] ?? 'Failed to initialize UddoktaPay payment');
        }

        $payment->update([
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'uddoktapay_payment_url' => $data['payment_url'],
            ]),
        ]);

        return [
            'success' => true,
            'gateway' => 'uddoktapay',
            'payment_id' => $payment->id,
            'invoice_number' => $payment->invoice_number,
            'amount' => $payment->total_amount,
            'currency' => $payment->currency ?? $gateway->currency,
            'redirect_url' => $data['payment_url'],
            'payment_details' => [
                'payment_url' => $data['payment_url'],
            ],
        ];
    }

    /**
     * Process an UddoktaPay return/webhook payload.
     *
     * The payload carries an `invoice_id` (and our metadata); we persist it and
     * verify server-side before completing.
     *
     * @param  array<string, mixed>  $data
     */
    public function processCallback(array $data, PaymentGateway $gateway): Payment
    {
        $invoiceId = (string) ($data['invoice_id'] ?? $data['invoiceId'] ?? '');
        $payment = null;

        if ($invoiceId !== '') {
            $payment = Payment::where('payment_details->uddoktapay_invoice_id', $invoiceId)->latest()->first();
        }

        if (! $payment) {
            $metadata = $data['metadata'] ?? [];
            if (is_string($metadata)) {
                $metadata = json_decode($metadata, true) ?: [];
            }

            if (! empty($metadata['payment_id'])) {
                $payment = Payment::find($metadata['payment_id']);
            }
            if (! $payment && ! empty($metadata['invoice_number'])) {
                $payment = Payment::where('invoice_number', $metadata['invoice_number'])->latest()->first();
            }
        }

        if (! $payment) {
            throw new \Exception('Payment not found for UddoktaPay callback.');
        }

        if ($invoiceId !== '' && ($payment->payment_details['uddoktapay_invoice_id'] ?? null) !== $invoiceId) {
            $payment->update([
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'uddoktapay_invoice_id' => $invoiceId,
                ]),
            ]);
        }

        return $this->verifyPayment($payment, $gateway);
    }

    /**
     * Verify an UddoktaPay payment status server-side.
     */
    public function verifyPayment(Payment $payment, PaymentGateway $gateway): Payment
    {
        $invoiceId = (string) ($payment->payment_details['uddoktapay_invoice_id'] ?? '');

        if ($invoiceId === '') {
            return $payment;
        }

        $response = $this->request($gateway, 'verify-payment', ['invoice_id' => $invoiceId]);

        if (! $response->successful()) {
            Log::error('Failed to verify UddoktaPay payment', $response->json() ?? []);

            return $payment;
        }

        $data = $response->json() ?? [];
        $status = strtoupper((string) ($data['status'] ?? ''));

        if ($status === 'COMPLETED') {
            return $this->complete($payment, $gateway, $data);
        }

        if ($status === 'ERROR') {
            $payment->update([
                'payment_status' => Payment::STATUS_FAILED,
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'uddoktapay_status' => $status,
                    'failure_reason' => $data['message'] ?? 'Payment failed',
                    'gateway_response' => $data,
                    'verified_at' => now(),
                ]),
            ]);
        }

        return $payment;
    }

    /**
     * Verify an UddoktaPay webhook (fail-closed).
     *
     * UddoktaPay signs webhooks by echoing the configured API key in the
     * `RT-UDDOKTAPAY-API-KEY` request header; anything else is rejected.
     */
    public function verifyWebhookSignature(Request $request, PaymentGateway $gateway): bool
    {
        $apiKey = (string) ($gateway->api_key ?? '');

        if ($apiKey === '') {
            return false;
        }

        $provided = (string) $request->header('RT-UDDOKTAPAY-API-KEY', '');

        if ($provided === '') {
            return false;
        }

        return hash_equals($apiKey, $provided);
    }

    /**
     * Process an UddoktaPay refund.
     *
     * @param  array<string, mixed>  $paymentDetails
     * @return array<string, mixed>
     */
    public function refund(PaymentGateway $gateway, string $transactionId, float $amount, string $reason, array $paymentDetails = []): array
    {
        $response = $this->request($gateway, 'refund-payment', [
            'transaction_id' => $transactionId,
            'payment_method' => (string) ($paymentDetails['payment_method'] ?? 'bkash'),
            'amount' => number_format($amount, 2, '.', ''),
            'product_name' => (string) ($paymentDetails['product_name'] ?? config('app.name', 'Payment')),
            'reason' => $reason,
        ]);

        $data = $response->json() ?? [];

        if ($response->successful()) {
            return [
                'success' => true,
                'transaction_id' => $data['transaction_id'] ?? $transactionId,
                'gateway_response' => $data,
            ];
        }

        return [
            'success' => false,
            'message' => $data['message'] ?? 'UddoktaPay refund failed',
        ];
    }

    /**
     * Mark a payment completed and apply side effects.
     *
     * @param  array<string, mixed>  $data
     */
    protected function complete(Payment $payment, PaymentGateway $gateway, array $data): Payment
    {
        $transactionId = (string) (
            $data['transaction_id']
            ?? $payment->transaction_id
            ?? $payment->payment_details['uddoktapay_invoice_id']
            ?? ''
        );

        $payment->update([
            'payment_status' => Payment::STATUS_COMPLETED,
            'paid_amount' => $payment->total_amount,
            'due_amount' => 0,
            'payment_date' => now(),
            'transaction_id' => $transactionId,
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'transaction_id' => $transactionId,
                'uddoktapay_status' => $data['status'] ?? 'COMPLETED',
                'payment_method' => $data['payment_method'] ?? ($payment->payment_details['payment_method'] ?? null),
                'payment_method_details' => $data,
                'gateway_response' => $data,
                'verified_at' => now(),
            ]),
        ]);

        $this->applyPaymentSideEffects($payment, [
            'gateway' => 'uddoktapay',
            'transaction_id' => $transactionId,
            'raw' => $data,
        ]);

        event(new \App\Events\PaymentProcessed($payment));

        return $payment;
    }

    /**
     * Send an authenticated request to the UddoktaPay API.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function request(PaymentGateway $gateway, string $path, array $payload): Response
    {
        $config = $gateway->getApiConfig();
        $base = (string) ($config['test_mode'] ?? true ? $gateway->sandbox_url : $gateway->live_url);
        $base = rtrim($base !== '' ? $base : 'https://sandbox.uddoktapay.com/api', '/');

        return Http::withHeaders([
            'RT-UDDOKTAPAY-API-KEY' => (string) ($config['api_key'] ?? $gateway->api_key ?? ''),
            'Accept' => 'application/json',
        ])->post("{$base}/{$path}", $payload);
    }
}
