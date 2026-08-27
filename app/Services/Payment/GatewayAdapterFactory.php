<?php

namespace App\Services\Payment;

class GatewayAdapterFactory
{
    /**
     * Create a gateway adapter for the given gateway code.
     *
     * @throws \Exception
     */
    public static function make(string $gatewayCode): GatewayAdapterInterface
    {
        return match ($gatewayCode) {
            'bkash' => new BkashGatewayAdapter,
            'nagad' => new NagadGatewayAdapter,
            'rocket' => new RocketGatewayAdapter,
            default => throw new \Exception("Payment method not implemented: {$gatewayCode}"),
        };
    }
}
