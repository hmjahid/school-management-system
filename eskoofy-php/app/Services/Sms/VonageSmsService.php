<?php
declare(strict_types=1);

namespace App\Services\Sms;

class VonageSmsService extends AbstractSmsService
{
    protected function defaultConfig(): array
    {
        return [
            'driver'       => 'vonage',
            'api_key'      => $_ENV['VONAGE_API_KEY'] ?? ($_ENV['NEXMO_KEY'] ?? ''),
            'api_secret'   => $_ENV['VONAGE_API_SECRET'] ?? ($_ENV['NEXMO_SECRET'] ?? ''),
            'from'         => $_ENV['VONAGE_FROM'] ?? ($_ENV['NEXMO_FROM_NUMBER'] ?? 'Eskoofy'),
            'brand'        => $_ENV['VONAGE_BRAND'] ?? 'Eskoofy',
            'api_base'     => $_ENV['VONAGE_API_BASE'] ?? 'https://api.nexmo.com',
            'country_code' => $_ENV['SMS_COUNTRY_CODE'] ?? '1',
            'timeout'      => $_ENV['SMS_HTTP_TIMEOUT'] ?? 30,
        ];
    }

    public function send(string $to, string $message, array $options = []): array
    {
        $key = $this->config['api_key'] ?? '';
        $secret = $this->config['api_secret'] ?? '';

        if ($key === '' || $secret === '') {
            return $this->failed('Vonage API key / secret not configured.');
        }

        $payload = [
            'from'         => $options['from'] ?? $this->config['from'] ?? 'Eskoofy',
            'to'           => $this->formatPhoneNumber($to),
            'message_type' => 'text',
            'text'         => $message,
            'channel'      => $this->config['channel'] ?? 'sms',
        ];

        if (isset($options['client_ref'])) {
            $payload['client_ref'] = (string) $options['client_ref'];
        }

        if (isset($options['callback_url'])) {
            $payload['webhooks'] = ['delivery' => ['address' => (string) $options['callback_url']]];
        }

        try {
            $response = $this->httpRequest(
                'POST',
                rtrim($this->config['api_base'] ?? 'https://api.nexmo.com', '/') . '/v1/messages',
                [
                    $this->basicAuthHeader($key, $secret),
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                json_encode($payload, JSON_UNESCAPED_SLASHES)
            );
        } catch (\RuntimeException $e) {
            return $this->failed($e->getMessage());
        }

        $data = json_decode($response['body'], true);
        if (! is_array($data)) {
            $data = ['error' => $response['body']];
        }

        if ($response['status'] >= 200 && $response['status'] < 300 && isset($data['message_uuid'])) {
            $this->writeLog(['to' => $to, 'message_id' => $data['message_uuid'], 'provider' => 'vonage', 'status' => 'accepted']);

            return [
                'success'    => true,
                'message_id' => $data['message_uuid'],
                'status'     => 'accepted',
                'provider'   => 'vonage',
                'raw'        => $data,
            ];
        }

        return $this->failed(($data['error'] ?? 'Vonage request failed.') . ' (HTTP ' . $response['status'] . ')', $data);
    }

    public function getBalance(): array
    {
        $key = $this->config['api_key'] ?? '';
        $secret = $this->config['api_secret'] ?? '';
        if ($key === '' || $secret === '') {
            return ['amount' => 0.0, 'currency' => 'EUR'];
        }

        try {
            $response = $this->httpRequest(
                'GET',
                rtrim($this->config['api_base'] ?? 'https://api.nexmo.com', '/') . '/account/get-balance',
                [$this->basicAuthHeader($key, $secret), 'Accept: application/json']
            );
        } catch (\RuntimeException $e) {
            return ['amount' => 0.0, 'currency' => 'EUR'];
        }

        $data = json_decode($response['body'], true);

        return [
            'amount'   => (float) ($data['value'] ?? 0.0),
            'currency' => (string) ($data['currency'] ?? 'EUR'),
        ];
    }

    public function getStatus(string $messageId): array
    {
        return [
            'status'       => 'accepted',
            'message_id'   => $messageId,
            'provider'     => 'vonage',
            'note'         => 'Delivery receipts arrive asynchronously via webhook.',
        ];
    }

    /**
     * @param  mixed  $raw
     * @return array{success: bool, message_id: ?string, status: string, provider: string, error?: string, raw?: mixed}
     */
    private function failed(string $error, $raw = null): array
    {
        $this->writeLog(['to' => null, 'error' => $error, 'provider' => 'vonage']);

        $result = [
            'success'    => false,
            'message_id' => null,
            'status'     => 'failed',
            'provider'   => 'vonage',
            'error'      => $error,
        ];

        if ($raw !== null) {
            $result['raw'] = $raw;
        }

        return $result;
    }
}