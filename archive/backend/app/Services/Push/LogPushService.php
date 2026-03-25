<?php

namespace App\Services\Push;

use App\Contracts\PushNotificationService;
use Illuminate\Support\Facades\Log;

class LogPushService implements PushNotificationService
{
    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config;

    /**
     * Create a new log push service instance.
     *
     * @param  array  $config
     * @return void
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Send a push notification to a specific device.
     *
     * @param  string  $deviceToken
     * @param  array  $notification
     * @param  array  $data
     * @param  array  $options
     * @return array
     */
    public function sendToDevice(string $deviceToken, array $notification, array $data = [], array $options = []): array
    {
        $logMessage = sprintf(
            "[Push] To: %s, Title: %s, Body: %s, Data: %s",
            $deviceToken,
            $notification['title'] ?? 'No Title',
            $notification['body'] ?? 'No Body',
            json_encode($data)
        );

        Log::channel($this->config['log_channel'] ?? 'stack')->info($logMessage);

        return [
            'success' => true,
            'message_id' => uniqid('push_', true),
            'device_token' => $deviceToken,
            'notification' => $notification,
            'data' => $data,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    /**
     * Send a push notification to multiple devices.
     *
     * @param  array  $deviceTokens
     * @param  array  $notification
     * @param  array  $data
     * @param  array  $options
     * @return array
     */
    public function sendToDevices(array $deviceTokens, array $notification, array $data = [], array $options = []): array
    {
        $results = [
            'success' => 0,
            'failure' => 0,
            'responses' => [],
        ];

        foreach ($deviceTokens as $token) {
            $response = $this->sendToDevice($token, $notification, $data, $options);
            $results['responses'][] = $response;
            
            if ($response['success']) {
                $results['success']++;
            } else {
                $results['failure']++;
            }
        }

        return $results;
    }

    /**
     * Send a push notification to a topic.
     *
     * @param  string  $topic
     * @param  array  $notification
     * @param  array  $data
     * @param  array  $options
     * @return array
     */
    public function sendToTopic(string $topic, array $notification, array $data = [], array $options = []): array
    {
        $logMessage = sprintf(
            "[Push] Topic: %s, Title: %s, Body: %s, Data: %s",
            $topic,
            $notification['title'] ?? 'No Title',
            $notification['body'] ?? 'No Body',
            json_encode($data)
        );

        Log::channel($this->config['log_channel'] ?? 'stack')->info($logMessage);

        return [
            'success' => true,
            'message_id' => uniqid('topic_push_', true),
            'topic' => $topic,
            'notification' => $notification,
            'data' => $data,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    /**
     * Subscribe a device to a topic.
     *
     * @param  string|array  $deviceTokens
     * @param  string  $topic
     * @return bool
     */
    public function subscribeToTopic($deviceTokens, string $topic): bool
    {
        $deviceTokens = (array) $deviceTokens;
        
        Log::channel($this->config['log_channel'] ?? 'stack')->info(
            'Subscribed devices to topic',
            ['devices' => $deviceTokens, 'topic' => $topic]
        );
        
        return true;
    }

    /**
     * Unsubscribe a device from a topic.
     *
     * @param  string|array  $deviceTokens
     * @param  string  $topic
     * @return bool
     */
    public function unsubscribeFromTopic($deviceTokens, string $topic): bool
    {
        $deviceTokens = (array) $deviceTokens;
        
        Log::channel($this->config['log_channel'] ?? 'stack')->info(
            'Unsubscribed devices from topic',
            ['devices' => $deviceTokens, 'topic' => $topic]
        );
        
        return true;
    }

    /**
     * Get information about a specific device token.
     *
     * @param  string  $deviceToken
     * @return array
     */
    public function getDeviceInfo(string $deviceToken): array
    {
        Log::channel($this->config['log_channel'] ?? 'stack')->info(
            'Fetching device info',
            ['device_token' => $deviceToken]
        );
        
        return [
            'success' => true,
            'device_token' => $deviceToken,
            'platform' => 'test',
            'app_id' => 'test-app',
            'authenticated' => true,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    /**
     * Validate a device token.
     *
     * @param  string  $deviceToken
     * @return bool
     */
    public function validateDeviceToken(string $deviceToken): bool
    {
        // In development, all tokens are considered valid
        return true;
    }
}
