<?php
declare(strict_types=1);

namespace App\Services\Push;

use App\Core\Database;
use App\Core\DatabaseInterface;

/**
 * Firebase Cloud Messaging push via the legacy HTTP v1-ish endpoint.
 *
 * Dependency-free (no Composer SDK): reads FCM config from environment and
 * posts to https://fcm.googleapis.com/fcm/send (legacy) or v1 when a bearer
 * token + project id are configured. Falls back to LogPushService when FCM
 * is not configured.
 */
class FirebasePushService implements PushNotificationService
{
    private const LEGACY_URL = 'https://fcm.googleapis.com/fcm/send';

    private DatabaseInterface $db;
    private ?string $serverKey;
    private ?string $projectId;
    private ?string $oauthToken;
    private LogPushService $fallback;

    public function __construct(?DatabaseInterface $db = null)
    {
        $this->db = $db ?? Database::getInstance();
        $this->serverKey  = trim((string) ($_ENV['FCM_SERVER_KEY'] ?? ''));
        $this->projectId  = trim((string) ($_ENV['FCM_PROJECT_ID'] ?? ''));
        $this->oauthToken = trim((string) ($_ENV['FCM_OAUTH_TOKEN'] ?? ''));
        $this->fallback   = new LogPushService();
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): array
    {
        if ($this->serverKey === '' && $this->oauthToken === '') {
            return $this->fallback->sendToToken($token, $title, $body, $data);
        }

        $payload = [
            'to'  => $token,
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'data' => $data,
        ];

        $headers = ['Content-Type' => 'application/json'];
        if ($this->serverKey !== '') {
            $headers['Authorization'] = 'key=' . $this->serverKey;
        } elseif ($this->oauthToken !== '') {
            $headers['Authorization'] = 'Bearer ' . $this->oauthToken;
        }

        $response = $this->post(self::LEGACY_URL, $headers, json_encode($payload));

        if ($response['ok'] && ! empty($response['decoded']['message_id'])) {
            return ['success' => true, 'message_id' => (string) $response['decoded']['message_id']];
        }
        return ['success' => false, 'message_id' => null, 'error' => $response['error'] ?? 'FCM request failed'];
    }

    /**
     * @param  array<string,string>  $headers
     * @return array{ok: bool, error?: string, decoded?: mixed}
     */
    private function post(string $url, array $headers, ?string $body): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => array_map(
                static fn (string $k, string $v): string => $k . ': ' . $v,
                array_keys($headers),
                array_values($headers)
            ),
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw    = curl_exec($ch);
        $errno  = curl_errno($ch);
        $error  = curl_error($ch);
        $code   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            return ['ok' => false, 'error' => $error ?: 'curl error ' . $errno];
        }
        return [
            'ok'      => $code >= 200 && $code < 300,
            'decoded' => json_decode((string) $raw, true),
            'error'   => $code >= 200 && $code < 300 ? null : 'HTTP ' . $code,
        ];
    }

    public function sendToUser(int $userId, string $title, string $body, array $data = []): array
    {
        $tokens = [];
        try {
            if ($this->db->hasTable('device_tokens')) {
                $tokens = $this->db->fetchAll(
                    "SELECT token FROM device_tokens WHERE user_id = ?",
                    [$userId]
                );
            }
        } catch (\Throwable) {
            $tokens = [];
        }

        if ($tokens === []) {
            return ['success' => true, 'sent' => 0, 'failed' => 0];
        }

        $sent = 0;
        $failed = 0;
        foreach ($tokens as $row) {
            $result = $this->sendToToken((string) $row['token'], $title, $body, $data);
            $result['success'] ? ++$sent : ++$failed;
        }
        return ['success' => true, 'sent' => $sent, 'failed' => $failed];
    }
}