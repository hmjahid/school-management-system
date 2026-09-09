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
        'stripe' => StripeGateway::class,
        'paypal' => PaypalGateway::class,
        'paddle' => PaddleGateway::class,
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

    public static function make(string $code): GatewayInterface
    {
        if (isset(self::$instances[$code])) {
            return self::$instances[$code];
        }

        $config = self::loadConfig();

        if ($code === 'offline') {
            $instance = new OfflineGateway($config['offline'] ?? []);
            self::$instances[$code] = $instance;
            return $instance;
        }

        if (!isset(self::GATEWAY_MAP[$code])) {
            throw new \InvalidArgumentException("Unknown payment gateway: {$code}");
        }

        if (!isset($config['gateways'][$code])) {
            throw new \InvalidArgumentException("No configuration for gateway: {$code}");
        }

        $class      = self::GATEWAY_MAP[$code];
        $gatewayCfg = $config['gateways'][$code];
        $instance   = new $class($gatewayCfg);
        self::$instances[$code] = $instance;

        return $instance;
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
