<?php
declare(strict_types=1);

namespace App\Services\Sms;

class TwilioSmsService extends AbstractSmsService
{
    protected function defaultConfig(): array
    {
        return [
            'driver'       => 'twilio',
            'account_sid'  => $_ENV['TWILIO_ACCOUNT_SID'] ?? '',
            'auth_token'   => $_ENV['TWILIO_AUTH_TOKEN'] ?? '',
            'from'         => $_ENV['TWILIO_FROM_NUMBER'] ?? '',
            'country_code' => $_ENV['SMS_COUNTRY_CODE'] ?? '1',
            'api_base'     => $_ENV['TWILIO_API_BASE'] ?? 'https://api.twilio.com',
            'timeout'      => $_ENV['SMS_HTTP_TIMEOUT'] ?? 30,
        ];
    }

    public function send(string $to, string $message, array $options = []): array
    {
        $sid = $this->config['account_sid'] ?? '';
        $token = $this->config['auth_token'] ?? '';
        $from = $options['from'] ?? $this->config['from'] ?? '';

        if ($sid === '' || $token === '') {
            return $this->failed('Twilio account SID / auth token not configured.');
        }

        $url = rtrim($this->config['api_base'] ?? 'https://api.twilio.com', '/')
            . '/2010-04-01/Accounts/' . rawurlencode($sid) . '/Messages.json';

        $payload = [
            'To'   => $this->formatPhoneNumber($to),
            'From' => $from,
            'Body' => $message,
        ];

        if (isset($options['status_callback'])) {
            $payload['StatusCallback'] = $options['status_callback'];
        }

        try {
            $response = $this->httpRequest(
                'POST',
                $url,
                [
                    $this->basicAuthHeader($sid, $token),
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json',
                ],
                http_build_query($payload)
            );
        } catch (\RuntimeException $e) {
            return $this->failed($e->getMessage());
        }

        $data = json_decode($response['body'], true);
        if (! is_array($data)) {
            $data = ['error_message' => $response['body']];
        }

        if ($response['status'] >= 200 && $response['status'] < 300 && isset($data['sid'])) {
            $this->writeLog(['to' => $to, 'message_id' => $data['sid'], 'provider' => 'twilio', 'status' => $data['status'] ?? 'created']);

            return [
                'success'    => true,
                'message_id' => $data['sid'],
                'status'     => $data['status'] ?? 'created',
                'provider'   => 'twilio',
                'raw'        => $data,
            ];
        }

        return $this->failed(($data['error_message'] ?? $data['message'] ?? 'Twilio request failed.') . ' (HTTP ' . $response['status'] . ')', $data);
    }

    public function getBalance(): array
    {
        $sid = $this->config['account_sid'] ?? '';
        $token = $this->config['auth_token'] ?? '';
        if ($sid === '' || $token === '') {
            return ['amount' => 0.0, 'currency' => 'USD'];
        }

        $url = rtrim($this->config['api_base'] ?? 'https://api.twilio.com', '/')
            . '/2010-04-01/Accounts/' . rawurlencode($sid) . '/Balance.json';

        try {
            $response = $this->httpRequest('GET', $url, [$this->basicAuthHeader($sid, $token), 'Accept: application/json']);
        } catch (\RuntimeException $e) {
            return ['amount' => 0.0, 'currency' => 'USD'];
        }

        $data = json_decode($response['body'], true);

        return [
            'amount'   => (float) ($data['balance'] ?? 0.0),
            'currency' => (string) ($data['currency'] ?? 'USD'),
        ];
    }

    public function getStatus(string $messageId): array
    {
        $sid = $this->config['account_sid'] ?? '';
        $token = $this->config['auth_token'] ?? '';
        if ($sid === '' || $token === '' || $messageId === '') {
            return ['status' => 'unknown', 'provider' => 'twilio', 'error' => 'Not configured.'];
        }

        $url = rtrim($this->config['api_base'] ?? 'https://api.twilio.com', '/')
            . '/2010-04-01/Accounts/' . rawurlencode($sid) . '/Messages/' . rawurlencode($messageId) . '.json';

        try {
            $response = $this->httpRequest('GET', $url, [$this->basicAuthHeader($sid, $token), 'Accept: application/json']);
        } catch (\RuntimeException $e) {
            return ['status' => 'failed', 'provider' => 'twilio', 'error' => $e->getMessage()];
        }

        $data = json_decode($response['body'], true);

        return is_array($data) ? $data : ['status' => 'unknown', 'raw' => $response['body']];
    }

    /**
     * @param  mixed  $raw
     * @return array{success: bool, message_id: ?string, status: string, provider: string, error?: string, raw?: mixed}
     */
    private function failed(string $error, $raw = null): array
    {
        $this->writeLog(['to' => null, 'error' => $error, 'provider' => 'twilio']);

        $result = [
            'success'    => false,
            'message_id' => null,
            'status'     => 'failed',
            'provider'   => 'twilio',
            'error'      => $error,
        ];

        if ($raw !== null) {
            $result['raw'] = $raw;
        }

        return $result;
    }
}