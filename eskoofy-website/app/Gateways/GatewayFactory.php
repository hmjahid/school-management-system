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
            'stripe'  => new StripeGateway($db),
            'paypal'  => new PaypalGateway($db),
            'paddle'  => new PaddleGateway($db),
            default   => throw new \InvalidArgumentException("Unsupported payment gateway: {$code}"),
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
     * BD gateway codes (local payment methods).
     *
     * @return string[]
     */
    public static function bdGateways(): array
    {
        return ['bkash', 'rocket', 'nagad'];
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
            $gw = self::make($code);
            if (method_exists($gw, 'isConfigured') && !$gw->isConfigured()) {
                continue;
            }
            $available[] = $code;
        }
        $available[] = 'manual';

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