<?php

namespace App\Services\Sms;

use App\Contracts\SmsService as SmsServiceContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

abstract class BaseSmsService implements SmsServiceContract
{
    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config;

    /**
     * The HTTP client instance.
     *
     * @var \Illuminate\Http\Client\PendingRequest
     */
    protected $http;

    /**
     * The last response from the API.
     *
     * @var array|null
     */
    protected $lastResponse;

    /**
     * Create a new SMS service instance.
     *
     * @param  array  $config
     * @return void
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge(
            $this->getDefaultConfig(),
            $config
        );

        $this->http = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->timeout(30);
    }

    /**
     * Get the default configuration for the service.
     *
     * @return array
     */
    abstract protected function getDefaultConfig(): array;

    /**
     * Send an SMS message.
     *
     * @param  string  $to
     * @param  string  $message
     * @param  array  $options
     * @return bool
     */
    public function send(string $to, string $message, array $options = []): bool
    {
        try {
            $response = $this->sendSms($to, $message, $options);
            $this->logSms($to, $message, $response);
            return $this->wasSuccessful($response);
        } catch (\Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * Send the SMS message to the given number.
     *
     * @param  string  $to
     * @param  string  $message
     * @param  array  $options
     * @return mixed
     */
    abstract protected function sendSms(string $to, string $message, array $options = []);

    /**
     * Determine if the SMS was sent successfully.
     *
     * @param  mixed  $response
     * @return bool
     */
    abstract protected function wasSuccessful($response): bool;

    /**
     * Get the remaining SMS balance.
     *
     * @return float
     */
    abstract public function getBalance(): float;

    /**
     * Get the delivery status of a sent message.
     *
     * @param  string  $messageId
     * @return array
     */
    abstract public function getStatus(string $messageId): array;

    /**
     * Log the SMS message.
     *
     * @param  string  $to
     * @param  string  $message
     * @param  mixed  $response
     * @return void
     */
    protected function logSms(string $to, string $message, $response): void
    {
        Log::info('SMS sent', [
            'to' => $to,
            'message' => $message,
            'response' => $response,
            'provider' => get_class($this),
        ]);
    }

    /**
     * Log an error that occurred while sending an SMS.
     *
     * @param  \Exception  $e
     * @return void
     */
    protected function logError(\Exception $e): void
    {
        Log::error('SMS sending failed: ' . $e->getMessage(), [
            'exception' => $e,
            'provider' => get_class($this),
        ]);
    }

    /**
     * Get the sender ID or phone number.
     *
     * @return string
     */
    protected function getFrom(): string
    {
        return $this->config['from'] ?? Config::get('sms.from', 'SchoolMS');
    }

    /**
     * Format the phone number.
     *
     * @param  string  $phoneNumber
     * @return string
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // If the number starts with 0, replace with country code
        if (strlen($phoneNumber) === 10 && $phoneNumber[0] === '0') {
            $countryCode = $this->config['country_code'] ?? '1';
            $phoneNumber = $countryCode . substr($phoneNumber, 1);
        }

        // Ensure the number starts with a plus
        if (strpos($phoneNumber, '+') !== 0) {
            $phoneNumber = '+' . $phoneNumber;
        }

        return $phoneNumber;
    }

    /**
     * Get the last response from the API.
     *
     * @return array|null
     */
    public function getLastResponse(): ?array
    {
        return $this->lastResponse;
    }

    /**
     * Set the last response from the API.
     *
     * @param  mixed  $response
     * @return void
     */
    protected function setLastResponse($response): void
    {
        $this->lastResponse = $this->normalizeResponse($response);
    }

    /**
     * Normalize the response from the API.
     *
     * @param  mixed  $response
     * @return array
     */
    protected function normalizeResponse($response): array
    {
        if ($response instanceof \Illuminate\Http\Client\Response) {
            return [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->json(),
                'headers' => $response->headers(),
            ];
        }

        if (is_array($response) || is_object($response)) {
            return (array) $response;
        }

        return ['raw' => $response];
    }
}
