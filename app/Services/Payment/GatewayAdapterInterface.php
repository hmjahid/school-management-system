<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentGateway;

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
     * Process a refund with the gateway.
     *
     * @param  array<string, mixed>  $paymentDetails
     * @return array<string, mixed>
     */
    public function refund(PaymentGateway $gateway, string $transactionId, float $amount, string $reason, array $paymentDetails = []): array;
}
