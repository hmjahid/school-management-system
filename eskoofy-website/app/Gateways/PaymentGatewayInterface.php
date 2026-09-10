<?php
declare(strict_types=1);

namespace App\Gateways;

interface PaymentGatewayInterface
{
    /**
     * Gateway code (manual, paddle, ...).
     */
    public function id(): string;

    /**
     * Display name.
     */
    public function name(): string;

    /**
     * Process an order. `$payment` is the pending payment row.
     *
     * @param  array<string, mixed>  $order
     * @param  array<string, mixed>  $payment
     * @return array<string, mixed>  success, transaction_id, message, raw
     */
    public function process(array $order, array $payment): array;

    /**
     * Verify a completed transaction idempotently.
     *
     * @param  array<string, mixed>  $payment
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function verify(array $payment, array $data = []): array;
}