<?php
declare(strict_types=1);

namespace App\Services\Sms;

abstract class AbstractSmsService implements SmsServiceInterface
{
    /**
     * Driver configuration.
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->defaultConfig(), $config);
    }

    /**
     * Driver name.
     */
    public function name(): string
    {
        return isset($this->config['driver']) ? (string) $this->config['driver'] : 'sms';
    }

    /**
     * Default configuration for the driver.
     *
     * @return array<string, mixed>
     */
    abstract protected function defaultConfig(): array;

    /**
     * Perform an HTTP request.
     *
     * @param  array<string, string>  $headers
     * @return array{status: int, body: string}
     */
    protected function httpRequest(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $context = stream_context_create([
            'http' => [
                'method'        => $method,
                'header'        => implode("\r\n", $headers),
                'content'       => $body ?? '',
                'timeout'       => (int) ($this->config['timeout'] ?? 30),
                'ignore_errors' => true,
                'follow_location' => true,
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            $error = error_get_last();

            throw new \RuntimeException($error['message'] ?? 'SMS HTTP request failed.');
        }

        $status = 0;
        if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
            $status = (int) $m[1];
        }

        return ['status' => $status, 'body' => $response];
    }

    /**
     * Basic auth header value.
     */
    protected function basicAuthHeader(string $username, string $password): string
    {
        return 'Authorization: Basic ' . base64_encode($username . ':' . $password);
    }

    /**
     * Format a phone number into E.164-ish form.
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber) ?? '';
        if ($phoneNumber === '') {
            return $phoneNumber;
        }

        if (str_starts_with($phoneNumber, '00')) {
            $phoneNumber = '+' . substr($phoneNumber, 2);
        }

        if ((strlen($phoneNumber) === 10 && str_starts_with($phoneNumber, '0')) || (strlen($phoneNumber) === 11 && str_starts_with($phoneNumber, '1'))) {
            $countryCode = $this->config['country_code'] ?? '1';
            $phoneNumber = $countryCode . ltrim($phoneNumber, '0');
            if (! str_starts_with($phoneNumber, '+')) {
                $phoneNumber = '+' . $phoneNumber;
            }
        }

        if (! str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+' . $phoneNumber;
        }

        return $phoneNumber;
    }

    /**
     * Append to the driver log file (used by LogSmsService and for failures).
     *
     * @param  array<string, mixed>  $data
     */
    protected function writeLog(array $data): void
    {
        $file = $this->config['log_file'] ?? '';
        if ($file === '') {
            $file = dirname(__DIR__, 3) . '/storage/logs/sms.log';
        }

        $dir = dirname($file);
        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $line = '[' . date('Y-m-d H:i:s') . '] ' . json_encode($data, JSON_UNESCAPED_SLASHES) . PHP_EOL;
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}