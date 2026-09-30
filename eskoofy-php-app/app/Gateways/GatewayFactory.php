<?php
declare(strict_types=1);

namespace App\Gateways;

class GatewayFactory
{
    private static array $config = [];
    private static array $instances = [];

    private const GATEWAY_MAP = [
        'bkash'  => BKashGateway::class,
        'rocket' => RocketGateway::class,
        'nagad'  => NagadGateway::class,
        'uddoktapay' => UddoktapayGateway::class,
        'stripe' => StripeGateway::class,
        'paypal' => PaypalGateway::class,
        'paddle' => PaddleGateway::class,
        // International gateways (disabled by default) + any gateway an admin
        // adds manually are all served by the config-driven adapter.
        'gpay' => GenericHostedGateway::class,
        'applepay' => GenericHostedGateway::class,
        'razorpay' => GenericHostedGateway::class,
        'paystack' => GenericHostedGateway::class,
        'flutterwave' => GenericHostedGateway::class,
        'sslcommerz' => GenericHostedGateway::class,
        'square' => GenericHostedGateway::class,
        'mollie' => GenericHostedGateway::class,
        'authorize_net' => GenericHostedGateway::class,
        'xendit' => GenericHostedGateway::class,
        'adyen' => GenericHostedGateway::class,
        'skrill' => GenericHostedGateway::class,
    ];

    public static function loadConfig(): array
    {
        if (empty(self::$config)) {
            self::$config = require __DIR__ . '/../../config/payment.php';
        }
        return self::$config;
    }

    public static function getDefault(): GatewayInterface
    {
        $config = self::loadConfig();
        $code   = $config['default'] ?? 'bkash';
        return self::make($code);
    }

    /**
     * Build a gateway adapter.
     *
     * @param array<string, mixed> $overrides Per-gateway config (e.g. the
     *        payment_gateways row) merged over config/payment.php. Unknown codes
     *        resolve to the config-driven GenericHostedGateway when overrides are
     *        supplied, so manually-added gateways work without code changes.
     */
    public static function make(string $code, array $overrides = []): GatewayInterface
    {
        $config = self::loadConfig();

        if ($code === 'offline') {
            if (isset(self::$instances[$code])) {
                return self::$instances[$code];
            }

            $instance = new OfflineGateway($config['offline'] ?? []);
            self::$instances[$code] = $instance;

            return $instance;
        }

        $class = self::GATEWAY_MAP[$code] ?? null;

        if ($class === null) {
            if ($overrides === []) {
                throw new \InvalidArgumentException("Unknown payment gateway: {$code}");
            }

            $class = GenericHostedGateway::class;
        }

        if ($overrides === [] && isset(self::$instances[$code])) {
            return self::$instances[$code];
        }

        $gatewayCfg = array_merge($config['gateways'][$code] ?? [], $overrides);

        if ($gatewayCfg === []) {
            throw new \InvalidArgumentException("No configuration for gateway: {$code}");
        }

        $instance = new $class($gatewayCfg);

        if ($overrides === []) {
            self::$instances[$code] = $instance;
        }

        return $instance;
    }

    public static function makeFromPaymentRecord(array $payment, array $gatewayConfig): GatewayInterface
    {
        $code = (string) ($payment['payment_method'] ?? 'offline');

        if ($code === 'offline') {
            return self::make('offline');
        }

        return self::make($code, self::configFromRow($gatewayConfig));
    }

    /**
     * Map a payment_gateways row onto the gateway config keys, dropping empty
     * values so a blank column never overrides an env fallback.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function configFromRow(array $row): array
    {
        if ($row === []) {
            return [];
        }

        $extra = $row['extra_attributes'] ?? [];
        if (is_string($extra)) {
            $extra = json_decode($extra, true) ?: [];
        }

        $mapped = [
            'name'         => $row['name'] ?? null,
            'type'         => $row['type'] ?? null,
            'is_online'    => (bool) ($row['is_online'] ?? false),
            'has_api'      => (bool) ($row['has_api'] ?? false),
            'test_mode'    => (bool) ($row['test_mode'] ?? false),
            'sandbox_url'  => $row['sandbox_url'] ?? '',
            'live_url'     => $row['live_url'] ?? '',
            'api_key'      => $row['api_key'] ?? '',
            'api_secret'   => $row['api_secret'] ?? '',
            'api_username' => $row['api_username'] ?? '',
            'api_password' => $row['api_password'] ?? '',
            'callback_url' => $row['callback_url'] ?? '',
            'webhook_url'  => $row['webhook_url'] ?? '',
            'currency'     => $row['currency'] ?? null,
        ];

        $mapped = array_filter($mapped, static fn ($value) => $value !== '' && $value !== null);

        return array_merge($mapped, is_array($extra) ? $extra : []);
    }

    public static function all(): array
    {
        $config  = self::loadConfig();
        $gateways = [];

        foreach (self::GATEWAY_MAP as $code => $class) {
            if (isset($config['gateways'][$code])) {
                $gateways[$code] = $config['gateways'][$code]['name'] ?? $code;
            }
        }

        $gateways['offline'] = 'Offline / Bank Transfer';
        return $gateways;
    }

    public static function online(): array
    {
        $config  = self::loadConfig();
        $gateways = [];

        foreach (self::GATEWAY_MAP as $code => $class) {
            if (isset($config['gateways'][$code]) && ($config['gateways'][$code]['is_online'] ?? false)) {
                $gateways[$code] = $config['gateways'][$code]['name'] ?? $code;
            }
        }

        return $gateways;
    }

    public static function offline(): array
    {
        return ['offline' => 'Offline / Bank Transfer'];
    }
}
