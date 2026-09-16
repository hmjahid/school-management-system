<?php
declare(strict_types=1);

namespace App\Services\Push;

/**
 * Push notification contract (FCM).
 */
interface PushNotificationService
{
    /**
     * Send a push notification to a single device token.
     *
     * @return array{success: bool, message_id: ?string, error?: string}
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): array;

    /**
     * Send a push notification to all of a user's registered device tokens.
     *
     * @return array{success: bool, sent: int, failed: int}
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): array;
}