<?php

namespace App\Services\Payment;

class GatewayAdapterFactory
{
    /**
     * Create a gateway adapter for the given gateway code.
     *
     * Bespoke integrations take precedence; every other code (the international
     * gateways shipped disabled by default, and any gateway an admin adds
     * manually) is handled by the config-driven GenericHostedGatewayAdapter.
     *
     * @throws \Exception
     */
    public static function make(string $gatewayCode): GatewayAdapterInterface
    {
        return match ($gatewayCode) {
            'bkash' => new BkashGatewayAdapter,
            'nagad' => new NagadGatewayAdapter,
            'rocket' => new RocketGatewayAdapter,
            'uddoktapay' => new UddoktapayGatewayAdapter,
            'stripe' => new StripeGatewayAdapter,
            'paypal' => new PaypalGatewayAdapter,
            'paddle' => new PaddleGatewayAdapter,
            default => new GenericHostedGatewayAdapter,
        };
    }
}
