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
            'manual' => new ManualGateway($db),
            'paddle' => throw new \RuntimeException('Paddle gateway is not yet available. It will be added as a drop-in gateway later.'),
            default  => throw new \InvalidArgumentException("Unsupported payment gateway: {$code}"),
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
     * @return array<string, mixed>
     */
    private static function loadConfig(): array
    {
        $file = dirname(__DIR__, 2) . '/config/gateways.php';

        return file_exists($file) ? (array) require $file : ['default' => 'manual', 'gateways' => []];
    }
}