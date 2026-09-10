<?php
declare(strict_types=1);

namespace App\Services\Sms;

class SmsManager
{
    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @var array<string, SmsServiceInterface>
     */
    private array $instances = [];

    /**
     * @param  array<string, mixed>|null  $config
     */
    public function __construct(?array $config = null)
    {
        $this->config = $config ?? $this->loadConfig();
    }

    /**
     * @return array<string, mixed>
     */
    private function loadConfig(): array
    {
        $file = dirname(__DIR__, 2) . '/config/sms.php';

        $config = file_exists($file) ? (array) require $file : [];

        return array_merge(['default' => 'log', 'drivers' => []], $config);
    }

    public function defaultDriver(): string
    {
        return (string) ($this->config['default'] ?? 'log');
    }

    /**
     * @return array<string, SmsServiceInterface>
     */
    public function drivers(): array
    {
        $out = [];
        foreach (array_keys($this->config['drivers'] ?? []) as $name) {
            $out[$name] = $this->driver((string) $name);
        }

        return $out;
    }

    public function driver(?string $name = null): SmsServiceInterface
    {
        $name = $name ?? $this->defaultDriver();

        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        $spec = $this->config['drivers'][$name] ?? [];
        if (! is_array($spec)) {
            $spec = [];
        }

        $aliases = $this->config['aliases'] ?? [];
        $driverName = (string) ($spec['driver'] ?? $name);
        if (isset($aliases[$driverName])) {
            $driverName = (string) $aliases[$driverName];
        }

        $service = match ($driverName) {
            'twilio' => new TwilioSmsService(array_merge($spec, ['driver' => $name])),
            'vonage', 'nexmo' => new VonageSmsService(array_merge($spec, ['driver' => $driverName])),
            'log' => new LogSmsService(array_merge($spec, ['driver' => $name])),
            default => throw new \InvalidArgumentException("Unsupported SMS driver: {$name}"),
        };

        return $this->instances[$name] = $service;
    }
}