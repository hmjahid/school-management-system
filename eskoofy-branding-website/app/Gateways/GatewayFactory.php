<?php
declare(strict_types=1);

namespace App\Gateways;

use App\Core\DatabaseInterface;

class GatewayFactory
{
    /**
     * @var array<string, PaymentGatewayInterface>
     */
    private static array $instances = [];

    /**
     * @param  array<string, mixed>|null  $config
     */
    public static function make(string $code, ?array $config = null, ?DatabaseInterface $db = null): PaymentGatewayInterface
    {
        $code = strtolower(trim($code));
        if (isset(self::$instances[$code])) {
            return self::$instances[$code];
        }

        $config = $config ?? self::loadConfig();

        $service = match ($code) {
            'manual'  => new ManualGateway($db),
            'bkash'   => new BkashGateway($db),
            'rocket'  => new RocketGateway($db),
            'nagad'   => new NagadGateway($db),
            'uddoktapay' => new UddoktapayGateway($db),
            'test_gateway' => new TestGateway($db),
            'stripe'  => new StripeGateway($db),
            'paypal'  => new PaypalGateway($db),
            'paddle'  => new PaddleGateway($db),
            default   => self::makeGeneric($code, $config, $db),
        };

        return self::$instances[$code] = $service;
    }

    /**
     * Default gateway code from config.
     */
    public static function defaultCode(?array $config = null): string
    {
        $config = $config ?? self::loadConfig();

        return (string) ($config['default'] ?? 'manual');
    }

    /**
     * USD → BDT rate for deriving BDT prices for BD customers.
     */
    public static function bdtRate(?array $config = null): float
    {
        $config = $config ?? self::loadConfig();

        return (float) ($config['bdt_rate'] ?? 110);
    }

    /**
     * Convert a USD amount to BDT at the configured rate.
     */
    public static function toBdt(float $usd, ?array $config = null): float
    {
        return round($usd * self::bdtRate($config), 2);
    }

    /**
     * Whether a customer country should use BD local gateways.
     */
    public static function isBdCountry(?string $country): bool
    {
        if ($country === null || $country === '') {
            return false;
        }
        $c = strtoupper(trim($country));

        return in_array($c, ['BD', 'BANGLADESH', 'BDG'], true);
    }

    /**
     * Config-driven hosted gateways (no bespoke class) resolve through
     * GenericHostedGateway; anything else is unsupported.
     *
     * @param  array<string, mixed>  $config
     */
    private static function makeGeneric(string $code, array $config, ?DatabaseInterface $db): PaymentGatewayInterface
    {
        $driver = $config['gateways'][$code]['driver'] ?? null;
        if ($driver === 'generic_hosted') {
            return new GenericHostedGateway($db, $code);
        }

        throw new \InvalidArgumentException("Unsupported payment gateway: {$code}");
    }

    /**
     * Whether the zero-credential test gateway is offered at checkout.
     * Off by default; enable with TEST_GATEWAY_ENABLED=true.
     */
    public static function testGatewayEnabled(): bool
    {
        return in_array(strtolower((string) ($_ENV['TEST_GATEWAY_ENABLED'] ?? 'false')), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * BD gateway codes (local payment methods).
     *
     * @return string[]
     */
    public static function bdGateways(): array
    {
        return ['bkash', 'rocket', 'nagad', 'uddoktapay', 'shurjopay', 'portwallet', 'cellfin', 'purse', 'cashby', 'upay', 'mycash', 'payer'];
    }

    /**
     * International gateway codes.
     *
     * @return string[]
     */
    public static function intGateways(): array
    {
        return ['stripe', 'paypal', 'paddle'];
    }

    /**
     * Ordered gateway codes for a customer country, falling back to manual.
     *
     * @return string[]
     */
    public static function gatewaysForCountry(?string $country): array
    {
        $codes = self::isBdCountry($country) ? self::bdGateways() : self::intGateways();

        try {
            $settings = \App\Models\Settings::all();
        } catch (\Throwable) {
            $settings = [];
        }

        // Only keep configured gateways; always keep manual as fallback.
        $available = [];
        foreach ($codes as $code) {
            $enabled = $settings['gateway.' . $code . '.enabled'] ?? null;
            if ($enabled !== null && !in_array((string) $enabled, ['1', 'true', 'yes', 'on'], true)) {
                continue;
            }
            try {
                $gw = self::make($code);
                if (method_exists($gw, 'isConfigured') && !$gw->isConfigured()) {
                    continue;
                }
                $available[] = $code;
            } catch (\Throwable) {
                // A gateway that cannot even be constructed (e.g. a missing
                // runtime dependency) must never take the whole checkout down.
            }
        }
        $available[] = 'manual';

        // The zero-credential test gateway is a development tool: only offer it
        // when explicitly enabled, and never as the default choice.
        if (self::testGatewayEnabled() && !in_array('test_gateway', $available, true)) {
            array_unshift($available, 'test_gateway');
        }

        return $available;
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadConfig(): array
    {
        $file = dirname(__DIR__, 2) . '/config/gateways.php';

        return file_exists($file) ? (array) require $file : ['default' => 'manual', 'gateways' => []];
    }
}