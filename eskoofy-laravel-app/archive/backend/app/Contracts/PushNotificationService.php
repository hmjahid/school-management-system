<?php

namespace App\Contracts;

interface PushNotificationService
{
    /**
     * Send a push notification to a specific device.
     */
    public function sendToDevice(string $deviceToken, array $notification, array $data = [], array $options = []): array;

    /**
     * Send a push notification to multiple devices.
     */
    public function sendToDevices(array $deviceTokens, array $notification, array $data = [], array $options = []): array;

    /**
     * Send a push notification to a topic.
     */
    public function sendToTopic(string $topic, array $notification, array $data = [], array $options = []): array;

    /**
     * Subscribe a device to a topic.
     *
     * @param  string|array  $deviceTokens
     */
    public function subscribeToTopic($deviceTokens, string $topic): bool;

    /**
     * Unsubscribe a device from a topic.
     *
     * @param  string|array  $deviceTokens
     */
    public function unsubscribeFromTopic($deviceTokens, string $topic): bool;

    /**
     * Get information about a specific device token.
     */
    public function getDeviceInfo(string $deviceToken): array;

    /**
     * Validate a device token.
     */
    public function validateDeviceToken(string $deviceToken): bool;
}
