<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Config-driven hosted-checkout adapter.
 *
 * Handles every gateway that has a hosted checkout page instead of a bespoke
 * API integration — including the international gateways that ship disabled by
 * default (Google Pay, Apple Pay, Razorpay, Paystack, Flutterwave, SSLCommerz,
 * Square, Mollie, Authorize.Net, Xendit, Adyen, Skrill) and any gateway an
 * admin adds manually from Dashboard > Payment Gateways.
 *
 * The behaviour is entirely driven by the `payment_gateways` row:
 *   - sandbox_url / live_url : the hosted checkout endpoint (picked via test_mode)
 *   - api_key / api_secret   : credentials, also used for webhook verification
 *   - extra_attributes       : the optional tuning contract
 *
 * extra_attributes keys:
 *   checkout_method     GET (default) | POST
 *   checkout_url_template  optional URL with {amount} {currency} {reference}
 *                          {invoice} {callback} {cancel} {api_key} placeholders
 *   verify_url          server-side verification endpoint (redirect is NOT authoritative)
 *   verify_success_path JSON path in the verify response (default: status)
 *   verify_success_value expected value at that path (default: COMPLETED)
 *   refund_url          refund endpoint; absence means refunds are unsupported
 *   signature_header    webhook signature header (default: X-Webhook-Signature)
 */
class GenericHostedGatewayAdapter implements GatewayAdapterInterface
{
    use PaymentSideEffects;
    use VerifiesWebhookSignature;

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function initialize(Payment $payment, PaymentGateway $gateway, array $options = []): array
    {
        $config = $gateway->getApiConfig();
        $base = $this->baseUrl($gateway, $config);
        $method = strtoupper((string) ($config['checkout_method'] ?? 'GET'));

        if ($base === '' && empty($config['checkout_url_template'])) {
            throw new \Exception("Payment gateway [{$gateway->code}] has no checkout URL configured.");
        }

        $returnUrl = (string) (
            $options['return_url']
            ?? $payment->payment_details['return_url']
            ?? $gateway->success_url
            ?? $gateway->callback_url
            ?? url('/')
        );
        $cancelUrl = (string) (
            $options['cancel_url']
            ?? $payment->payment_details['cancel_url']
            ?? $gateway->cancel_url
            ?? $returnUrl
        );

        $reference = (string) $payment->invoice_number;
        $currency = (string) ($payment->currency ?? $gateway->currency ?? config('payment.currency'));

        $params = [
            'amount' => number_format((float) $payment->total_amount, 2, '.', ''),
            'currency' => $currency,
            'reference' => $reference,
            'invoice' => $reference,
            'callback' => $returnUrl,
            'cancel' => $cancelUrl,
            'api_key' => (string) ($gateway->api_key ?? ''),
        ];

        $redirectUrl = $this->buildCheckoutUrl($base, $config, $params);

        $payment->update([
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'gateway_reference' => $reference,
                'gateway_checkout_url' => $redirectUrl,
            ]),
        ]);

        return [
            'success' => true,
            'gateway' => $gateway->code,
            'payment_id' => $payment->id,
            'invoice_number' => $payment->invoice_number,
            'amount' => $payment->total_amount,
            'currency' => $currency,
            'checkout_method' => $method,
            'redirect_url' => $redirectUrl,
            'payment_details' => ['payment_url' => $redirectUrl],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function processCallback(array $data, PaymentGateway $gateway): Payment
    {
        $metadata = $data['metadata'] ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        $payment = null;

        foreach ([$metadata['payment_id'] ?? null, $data['payment_id'] ?? null] as $id) {
            if ($id) {
                $payment = Payment::find($id);
                if ($payment) {
                    break;
                }
            }
        }

        if (! $payment) {
            $reference = $data['reference'] ?? $data['invoice_number'] ?? $metadata['invoice_number'] ?? null;
            if ($reference) {
                $payment = Payment::where('invoice_number', $reference)->latest()->first();
            }
        }

        if (! $payment) {
            throw new \Exception("Payment not found for {$gateway->code} callback.");
        }

        return $this->verifyPayment($payment, $gateway);
    }

    public function verifyPayment(Payment $payment, PaymentGateway $gateway): Payment
    {
        $config = $gateway->getApiConfig();
        $verifyUrl = (string) ($config['verify_url'] ?? '');

        if ($verifyUrl === '') {
            return $payment;
        }

        $response = $this->request($gateway, $config, $verifyUrl, [
            'reference' => (string) ($payment->invoice_number ?? ''),
            'amount' => number_format((float) $payment->total_amount, 2, '.', ''),
            'currency' => (string) ($payment->currency ?? $gateway->currency),
            'transaction_id' => (string) ($payment->transaction_id ?? ''),
        ]);

        if (! $response->successful()) {
            Log::error("Failed to verify {$gateway->code} payment", $response->json() ?? []);

            return $payment;
        }

        $data = $response->json() ?? [];
        $actual = strtoupper((string) data_get($data, (string) ($config['verify_success_path'] ?? 'status'), ''));
        $expected = strtoupper((string) ($config['verify_success_value'] ?? 'COMPLETED'));

        $successValues = [$expected, 'COMPLETED', 'SUCCESS', 'SUCCEEDED', 'PAID', 'CAPTURED', 'SETTLED', 'OK'];

        if (in_array($actual, $successValues, true)) {
            return $this->complete($payment, $gateway, $data);
        }

        if (in_array($actual, ['FAILED', 'CANCELLED', 'CANCELED', 'EXPIRED', 'ERROR', 'DECLINED'], true)) {
            $payment->update([
                'payment_status' => Payment::STATUS_FAILED,
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'gateway_status' => $actual,
                    'failure_reason' => $data['message'] ?? 'Payment failed',
                    'gateway_response' => $data,
                    'verified_at' => now(),
                ]),
            ]);
        }

        return $payment;
    }

    public function verifyWebhookSignature(Request $request, PaymentGateway $gateway): bool
    {
        $config = $gateway->getApiConfig();
        $header = (string) ($config['signature_header'] ?? 'X-Webhook-Signature');

        if (! empty($gateway->api_secret)) {
            return $this->verifyHmacSignature($request, $gateway, $header);
        }

        $apiKey = (string) ($gateway->api_key ?? '');
        if ($apiKey === '') {
            return false;
        }

        $provided = (string) ($request->header($header) ?: $request->header('X-API-Key') ?: '');

        return $provided !== '' && hash_equals($apiKey, $provided);
    }

    /**
     * @param  array<string, mixed>  $paymentDetails
     * @return array<string, mixed>
     */
    public function refund(PaymentGateway $gateway, string $transactionId, float $amount, string $reason, array $paymentDetails = []): array
    {
        $config = $gateway->getApiConfig();
        $refundUrl = (string) ($config['refund_url'] ?? '');

        if ($refundUrl === '') {
            return [
                'success' => false,
                'message' => "Refunds are not supported for gateway: {$gateway->code}",
            ];
        }

        $response = $this->request($gateway, $config, $refundUrl, [
            'transaction_id' => $transactionId,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => (string) ($paymentDetails['currency'] ?? $gateway->currency),
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
            'message' => $data['message'] ?? "{$gateway->code} refund failed",
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function complete(Payment $payment, PaymentGateway $gateway, array $data): Payment
    {
        $transactionId = (string) (
            $data['transaction_id']
            ?? $data['id']
            ?? $payment->transaction_id
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
                'gateway_status' => $data['status'] ?? 'COMPLETED',
                'gateway_response' => $data,
                'verified_at' => now(),
            ]),
        ]);

        $this->applyPaymentSideEffects($payment, [
            'gateway' => $gateway->code,
            'transaction_id' => $transactionId,
            'raw' => $data,
        ]);

        event(new \App\Events\PaymentProcessed($payment));

        return $payment;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function baseUrl(PaymentGateway $gateway, array $config): string
    {
        $testMode = (bool) ($config['test_mode'] ?? true);
        $base = (string) ($testMode ? ($gateway->sandbox_url ?? '') : ($gateway->live_url ?? ''));

        return rtrim($base, '/');
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, string>  $params
     */
    protected function buildCheckoutUrl(string $base, array $config, array $params): string
    {
        $template = (string) ($config['checkout_url_template'] ?? '');

        if ($template !== '') {
            $replacements = [];
            foreach ($params as $key => $value) {
                $replacements['{'.$key.'}'] = rawurlencode((string) $value);
            }

            return strtr($template, $replacements);
        }

        $query = array_filter([
            'amount' => $params['amount'],
            'currency' => $params['currency'],
            'reference' => $params['reference'],
            'callback_url' => $params['callback'],
        ], fn ($value) => $value !== '' && $value !== null);

        $separator = str_contains($base, '?') ? '&' : '?';

        return $base.$separator.http_build_query($query);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $payload
     */
    protected function request(PaymentGateway $gateway, array $config, string $url, array $payload): Response
    {
        return Http::withHeaders($this->authHeaders($gateway, $config))
            ->asJson()
            ->post($url, $payload);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, string>
     */
    protected function authHeaders(PaymentGateway $gateway, array $config): array
    {
        return array_filter([
            'Accept' => 'application/json',
            'Authorization' => ($key = (string) ($config['api_key'] ?? $gateway->api_key ?? '')) !== '' ? 'Bearer '.$key : '',
            'X-API-Key' => $key,
        ]);
    }
}
