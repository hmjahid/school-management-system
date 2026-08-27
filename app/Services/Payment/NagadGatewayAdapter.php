<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NagadGatewayAdapter implements GatewayAdapterInterface
{
    use PaymentSideEffects;

    /**
     * Initialize Nagad payment.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function initialize(Payment $payment, PaymentGateway $gateway, array $options = []): array
    {
        $config = $gateway->getApiConfig();
        $baseUrl = $config['test_mode'] ? $gateway->sandbox_url : $gateway->live_url;

        // Generate a random string for request ID
        $requestId = Str::uuid()->toString();

        // Step 1: Initialize payment
        $initResponse = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-KM-IP-V4' => request()->ip(),
            'X-KM-Client-Type' => 'PC_WEB',
            'X-KM-Api-Version' => 'v-0.2.0',
        ])->post("$baseUrl/checkout/initialize/", [
            'accountNumber' => $config['merchant_account'],
            'dateTime' => now()->format('YmdHis'),
            'additionalMerchantInfo' => [
                'reference' => $payment->invoice_number,
                'purpose' => 'School Fee Payment',
            ],
            'amount' => (string) $payment->total_amount,
            'orderId' => $payment->invoice_number,
            'reference' => $payment->invoice_number,
        ]);

        if (! $initResponse->successful()) {
            Log::error('Failed to initialize Nagad payment', $initResponse->json());
            throw new \Exception('Failed to initialize Nagad payment');
        }

        $initData = $initResponse->json();

        // Store the payment ID for verification
        $payment->update([
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'nagad_payment_id' => $initData['paymentReferenceId'],
                'nagad_order_id' => $initData['orderId'],
            ]),
        ]);

        // Step 2: Complete the payment (this would be called from the frontend after user completes payment)
        // For now, we'll return the payment URL
        $paymentUrl = $initData['callBackUrl'].'?paymentRefId='.$initData['paymentReferenceId'];

        return [
            'success' => true,
            'gateway' => 'nagad',
            'payment_id' => $payment->id,
            'invoice_number' => $payment->invoice_number,
            'amount' => $payment->total_amount,
            'currency' => $payment->currency ?? $gateway->currency,
            'redirect_url' => $paymentUrl,
            'payment_details' => [
                'payment_reference_id' => $initData['paymentReferenceId'],
                'order_id' => $initData['orderId'],
                'challenge' => $initData['challenge'],
            ],
        ];
    }

    /**
     * Process Nagad callback.
     *
     * @param  array<string, mixed>  $data
     */
    public function processCallback(array $data, PaymentGateway $gateway): Payment
    {
        $payment = Payment::where('invoice_number', $data['orderId'])
            ->orWhere('payment_details->nagad_payment_id', $data['paymentRefId'])
            ->firstOrFail();

        $config = $gateway->getApiConfig();
        $baseUrl = $config['test_mode'] ? $gateway->sandbox_url : $gateway->live_url;

        // Verify the payment with Nagad
        $verifyResponse = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-KM-IP-V4' => request()->ip(),
            'X-KM-Client-Type' => 'PC_WEB',
            'X-KM-Api-Version' => 'v-0.2.0',
        ])->post("$baseUrl/verify/payment/", [
            'paymentRefId' => $data['paymentRefId'],
        ]);

        if (! $verifyResponse->successful()) {
            Log::error('Failed to verify Nagad payment', $verifyResponse->json());
            throw new \Exception('Failed to verify Nagad payment');
        }

        $verifyData = $verifyResponse->json();

        // Update payment status based on Nagad response
        if (isset($verifyData['status']) && $verifyData['status'] === 'Success') {
            $payment->update([
                'payment_status' => Payment::STATUS_COMPLETED,
                'paid_amount' => $payment->total_amount,
                'due_amount' => 0,
                'payment_date' => now(),
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'transaction_id' => $verifyData['paymentId'] ?? null,
                    'payment_status' => $verifyData['status'],
                    'payment_method_details' => $verifyData,
                ]),
            ]);

            $this->applyPaymentSideEffects($payment, [
                'gateway' => $gateway->code,
                'transaction_id' => $verifyData['paymentId'] ?? null,
                'raw' => $verifyData,
            ]);

            // Trigger payment success event
            event(new \App\Events\PaymentProcessed($payment));
        } else {
            $payment->update([
                'payment_status' => Payment::STATUS_FAILED,
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'transaction_id' => $verifyData['paymentId'] ?? null,
                    'payment_status' => $verifyData['status'] ?? 'Failed',
                    'failure_reason' => $verifyData['statusMessage'] ?? 'Payment verification failed',
                    'payment_method_details' => $verifyData,
                ]),
            ]);
        }

        return $payment;
    }

    /**
     * Verify Nagad payment status.
     *
     * Nagad verification is handled during callback processing.
     */
    public function verifyPayment(Payment $payment, PaymentGateway $gateway): Payment
    {
        return $payment;
    }

    /**
     * Process a Nagad refund.
     *
     * @param  array<string, mixed>  $paymentDetails
     * @return array<string, mixed>
     */
    public function refund(PaymentGateway $gateway, string $transactionId, float $amount, string $reason, array $paymentDetails = []): array
    {
        $config = $gateway->getApiConfig();
        $base = $config['test_mode'] ? $gateway->sandbox_url : $gateway->live_url;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-KM-Api-Version' => 'v-0.2.0',
        ])->post("$base/dfs/refund/initialize", [
            'paymentRefId' => $transactionId,
            'amount' => (string) $amount,
            'reason' => $reason,
            'reference' => $paymentDetails['reference'] ?? null,
        ]);

        $data = $response->json();

        if ($response->successful() && ($data['status'] ?? null) === 'Success') {
            return [
                'success' => true,
                'transaction_id' => $data['refundTrxID'] ?? ('R-'.strtoupper(substr(md5($transactionId.$amount), 0, 12))),
                'gateway_response' => $data,
            ];
        }

        return [
            'success' => false,
            'message' => $data['reason'] ?? $data['message'] ?? 'Nagad refund failed',
        ];
    }
}
