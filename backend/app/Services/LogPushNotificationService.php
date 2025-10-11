<?php

namespace App\Services;

use App\Contracts\PushNotificationService;
use Illuminate\Support\Facades\Log;

class LogPushNotificationService implements PushNotificationService
{
    /**
     * Send a push notification to a specific device (logs it instead of actually sending).
     */
    public function sendToDevice(string $deviceToken, array $notification, array $data = [], array $options = []): array
    {
        Log::info('Push notification to device', [
            'device_token' => $deviceToken,
            'notification' => $notification,
            'data' => $data,
            'options' => $options,
        ]);

        return [
            'success' => true,
            'message_id' => 'mock-message-' . uniqid(),
            'device_token' => $deviceToken,
        ];
    }
    
    /**
     * Send a push notification to multiple devices (logs it instead of actually sending).
     */
    public function sendToDevices(array $deviceTokens, array $notification, array $data = [], array $options = []): array
    {
        Log::info('Push notification to multiple devices', [
            'device_tokens' => $deviceTokens,
            'notification' => $notification,
            'data' => $data,
            'options' => $options,
        ]);

        return [
            'success' => true,
            'message_id' => 'mock-message-' . uniqid(),
            'device_tokens' => $deviceTokens,
        ];
    }
    
    /**
     * Send a push notification to a topic (logs it instead of actually sending).
     */
    public function sendToTopic(string $topic, array $notification, array $data = [], array $options = []): array
    {
        Log::info('Push notification to topic', [
            'topic' => $topic,
            'notification' => $notification,
            'data' => $data,
            'options' => $options,
        ]);

        return [
            'success' => true,
            'message_id' => 'mock-message-' . uniqid(),
            'topic' => $topic,
        ];
    }
    
    /**
     * Subscribe a device to a topic (logs it instead of actually subscribing).
     */
    public function subscribeToTopic($deviceTokens, string $topic): bool
    {
        $tokens = is_array($deviceTokens) ? $deviceTokens : [$deviceTokens];
        
        Log::info('Subscribing devices to topic', [
            'device_tokens' => $tokens,
            'topic' => $topic,
        ]);

        return true;
    }
    
    /**
     * Unsubscribe a device from a topic (logs it instead of actually unsubscribing).
     */
    public function unsubscribeFromTopic($deviceTokens, string $topic): bool
    {
        $tokens = is_array($deviceTokens) ? $deviceTokens : [$deviceTokens];
        
        Log::info('Unsubscribing devices from topic', [
            'device_tokens' => $tokens,
            'topic' => $topic,
        ]);

        return true;
    }
    
    /**
     * Unsubscribe a device from all topics (logs it instead of actually unsubscribing).
     */
    public function unsubscribeFromAllTopics($deviceTokens): bool
    {
        $tokens = is_array($deviceTokens) ? $deviceTokens : [$deviceTokens];
        
        Log::info('Unsubscribing devices from all topics', [
            'device_tokens' => $tokens,
        ]);

        return true;
    }
    
    /**
     * Get device information (mock implementation).
     *
     * @param  string  $deviceToken
     * @return array
     */
    public function getDeviceInfo(string $deviceToken): array
    {
        Log::info('Getting device info', [
            'device_token' => $deviceToken,
        ]);

        return [
            'device_token' => $deviceToken,
            'platform' => 'mock',
            'app_version' => '1.0.0',
            'last_active' => now()->toDateTimeString(),
            'is_active' => true,
        ];
    }
    
    /**
     * Validate a device token (mock implementation).
     *
     * @param  string  $deviceToken
     * @return bool
     */
    public function validateDeviceToken(string $deviceToken): bool
    {
        // Simple validation - just check if it's not empty and has a minimum length
        return !empty($deviceToken) && strlen($deviceToken) >= 10;
    }
}
