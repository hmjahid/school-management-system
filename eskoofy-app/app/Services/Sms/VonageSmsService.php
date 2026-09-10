<?php

namespace App\Services\Sms;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class VonageSmsService extends BaseSmsService
{
    /**
     * The Vonage API base URL.
     *
     * @var string
     */
    protected $apiBase = 'https://api.nexmo.com';

    /**
     * Get the default configuration for the service.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'api_key' => env('VONAGE_API_KEY', env('NEXMO_KEY')),
            'api_secret' => env('VONAGE_API_SECRET', env('NEXMO_SECRET')),
            'from' => env('VONAGE_FROM', env('NEXMO_FROM_NUMBER', 'Eskoofy')),
            'brand' => env('VONAGE_BRAND', 'Eskoofy'),
            'country_code' => env('SMS_COUNTRY_CODE', '1'),
            'api_base' => env('VONAGE_API_BASE', 'https://api.nexmo.com'),
            'channel' => 'sms',
        ];
    }

    /**
     * Build the authenticated HTTP client for Vonage.
     */
    protected function authClient(): PendingRequest
    {
        return $this->http->withBasicAuth(
            $this->config['api_key'] ?? '',
            $this->config['api_secret'] ?? ''
        );
    }

    /**
     * Send the SMS message to the given number.
     *
     * @return mixed
     */
    protected function sendSms(string $to, string $message, array $options = [])
    {
        $to = $this->formatPhoneNumber($to);
        $from = $options['from'] ?? $this->getFrom();

        $payload = [
            'from' => $from,
            'to' => $to,
            'message_type' => 'text',
            'text' => $message,
            'channel' => $this->config['channel'] ?? 'sms',
        ];

        if (isset($options['client_ref'])) {
            $payload['client_ref'] = $options['client_ref'];
        }

        if (isset($options['callback_url'])) {
            $payload['webhooks'] = [
                'delivery' => [
                    'address' => $options['callback_url'],
                ],
            ];
        }

        $response = $this->authClient()
            ->asJson()
            ->retry(2, 300)
            ->post($this->config['api_base'].'/v1/messages', $payload);

        $this->setLastResponse($response);

        return $response;
    }

    /**
     * Determine if the SMS was sent successfully.
     *
     * @param  mixed  $response
     */
    protected function wasSuccessful($response): bool
    {
        if (! $response instanceof \Illuminate\Http\Client\Response) {
            return false;
        }

        return $response->successful()
            && isset($response->json()['message_uuid']);
    }

    /**
     * Get the remaining SMS balance.
     */
    public function getBalance(): float
    {
        try {
            $response = $this->authClient()->get($this->config['api_base'].'/account/get-balance');

            $balance = (float) ($response->json('value') ?? 0.0);

            return $balance;
        } catch (\Exception $e) {
            $this->logError($e);

            return 0.0;
        }
    }

    /**
     * Get the delivery status of a sent message.
     *
     * The Messages API is asynchronous; delivery status arrives via webhooks.
     */
    public function getStatus(string $messageId): array
    {
        return [
            'status' => 'accepted',
            'message_uuid' => $messageId,
            'provider' => 'vonage',
            'note' => 'Delivery receipts are delivered asynchronously via the configured webhook.',
        ];
    }
}