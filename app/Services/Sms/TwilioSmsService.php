<?php

namespace App\Services\Sms;

use Twilio\Rest\Client as TwilioClient;
use Twilio\Exceptions\TwilioException;

class TwilioSmsService extends BaseSmsService
{
    /**
     * The Twilio client instance.
     *
     * @var \Twilio\Rest\Client
     */
    protected $client;

    /**
     * Create a new Twilio SMS service instance.
     *
     * @param  array  $config
     * @return void
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        
        $this->client = new TwilioClient(
            $this->config['account_sid'] ?? '',
            $this->config['auth_token'] ?? ''
        );
    }

    /**
     * Get the default configuration for the service.
     *
     * @return array
     */
    protected function getDefaultConfig(): array
    {
        return [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_FROM_NUMBER'),
            'status_callback' => url(env('SMS_STATUS_CALLBACK', '/api/sms/status')),
        ];
    }

    /**
     * Send the SMS message to the given number.
     *
     * @param  string  $to
     * @param  string  $message
     * @param  array  $options
     * @return \Twilio\Rest\Api\V2010\Account\MessageInstance
     *
     * @throws \Twilio\Exceptions\TwilioException
     */
    protected function sendSms(string $to, string $message, array $options = [])
    {
        $to = $this->formatPhoneNumber($to);
        $from = $options['from'] ?? $this->getFrom();
        
        $params = [
            'body' => $message,
            'from' => $from,
            'statusCallback' => $options['status_callback'] ?? $this->config['status_callback'] ?? null,
        ];
        
        if (isset($options['media_urls'])) {
            $params['mediaUrl'] = (array) $options['media_urls'];
        }
        
        try {
            $message = $this->client->messages->create($to, $params);
            $this->setLastResponse($message);
            return $message;
        } catch (TwilioException $e) {
            $this->setLastResponse([
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw $e;
        }
    }

    /**
     * Determine if the SMS was sent successfully.
     *
     * @param  mixed  $response
     * @return bool
     */
    protected function wasSuccessful($response): bool
    {
        if (!is_object($response) || !method_exists($response, 'sid')) {
            return false;
        }
        
        return !empty($response->sid);
    }

    /**
     * Get the remaining SMS balance.
     *
     * @return float
     */
    public function getBalance(): float
    {
        try {
            $balance = $this->client->balance->fetch();
            return (float) $balance->balance;
        } catch (TwilioException $e) {
            $this->logError($e);
            return 0.0;
        }
    }

    /**
     * Get the delivery status of a sent message.
     *
     * @param  string  $messageId
     * @return array
     */
    public function getStatus(string $messageId): array
    {
        try {
            $message = $this->client->messages($messageId)->fetch();
            
            return [
                'status' => $message->status,
                'date_created' => $message->dateCreated->format('Y-m-d H:i:s'),
                'date_updated' => $message->dateUpdated->format('Y-m-d H:i:s'),
                'date_sent' => $message->dateSent ? $message->dateSent->format('Y-m-d H:i:s') : null,
                'error_code' => $message->errorCode,
                'error_message' => $message->errorMessage,
                'price' => $message->price,
                'price_unit' => $message->priceUnit,
                'num_segments' => $message->numSegments,
                'num_media' => $message->numMedia,
                'direction' => $message->direction,
                'api_version' => $message->apiVersion,
                'uri' => $message->uri,
            ];
        } catch (TwilioException $e) {
            $this->logError($e);
            
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }

    /**
     * Format the phone number for Twilio.
     *
     * @param  string  $phoneNumber
     * @return string
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        $phoneNumber = parent::formatPhoneNumber($phoneNumber);
        
        // Remove any non-numeric characters except the leading +
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Ensure the number is in E.164 format (e.g., +14155552671)
        if (strpos($phoneNumber, '+') !== 0) {
            $phoneNumber = '+' . ltrim($phoneNumber, '0');
        }
        
        return $phoneNumber;
    }
}
