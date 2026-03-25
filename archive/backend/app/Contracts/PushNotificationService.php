<?php

namespace App\Contracts;

interface PushNotificationService
{
    /**
     * Send a push notification to a specific device.
     *
     * @param  string  $deviceToken
     * @param  array  $notification
     * @param  array  $data
     * @param  array  $options
     * @return array
     */
    public function sendToDevice(string $deviceToken, array $notification, array $data = [], array $options = []): array;
    
    /**
     * Send a push notification to multiple devices.
     *
     * @param  array  $deviceTokens
     * @param  array  $notification
     * @param  array  $data
     * @param  array  $options
     * @return array
     */
    public function sendToDevices(array $deviceTokens, array $notification, array $data = [], array $options = []): array;
    
    /**
     * Send a push notification to a topic.
     *
     * @param  string  $topic
     * @param  array  $notification
     * @param  array  $data
     * @param  array  $options
     * @return array
     */
    public function sendToTopic(string $topic, array $notification, array $data = [], array $options = []): array;
    
    /**
     * Subscribe a device to a topic.
     *
     * @param  string|array  $deviceTokens
     * @param  string  $topic
     * @return bool
     */
    public function subscribeToTopic($deviceTokens, string $topic): bool;
    
    /**
     * Unsubscribe a device from a topic.
     *
     * @param  string|array  $deviceTokens
     * @param  string  $topic
     * @return bool
     */
    public function unsubscribeFromTopic($deviceTokens, string $topic): bool;
    
    /**
     * Get information about a specific device token.
     *
     * @param  string  $deviceToken
     * @return array
     */
    public function getDeviceInfo(string $deviceToken): array;
    
    /**
     * Validate a device token.
     *
     * @param  string  $deviceToken
     * @return bool
     */
    public function validateDeviceToken(string $deviceToken): bool;
}
