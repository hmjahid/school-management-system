<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;

interface GatewayAdapterInterface
{
    /**
     * Initialize a payment with the gateway.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function initialize(Payment $payment, PaymentGateway $gateway, array $options = []): array;

    /**
     * Process a payment callback from the gateway.
     *
     * @param  array<string, mixed>  $data
     */
    public function processCallback(array $data, PaymentGateway $gateway): Payment;

    /**
     * Verify a payment status with the gateway.
     */
    public function verifyPayment(Payment $payment, PaymentGateway $gateway): Payment;

    /**
     * Verify the authenticity of an incoming webhook/IPN request.
     *
     * Implementations MUST fail closed: return false when the signature is
     * missing, empty, or does not match the gateway secret.
     */
    public function verifyWebhookSignature(Request $request, PaymentGateway $gateway): bool;

    /**
     * Process a refund with the gateway.
     *
     * @param  array<string, mixed>  $paymentDetails
     * @return array<string, mixed>
     */
    public function refund(PaymentGateway $gateway, string $transactionId, float $amount, string $reason, array $paymentDetails = []): array;
}
