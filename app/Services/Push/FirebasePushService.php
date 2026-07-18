<?php

namespace App\Services\Push;

use App\Contracts\PushNotificationService;
use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\Core\ExponentialBackoff;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\WebPushConfig;
use Psr\SimpleCache\InvalidArgumentException;

class FirebasePushService implements PushNotificationService
{
    /**
     * The Firebase Messaging instance.
     *
     * @var \Kreait\Firebase\Contract\Messaging
     */
    protected $messaging;

    /**
     * The Firebase project ID.
     *
     * @var string
     */
    protected $projectId;

    /**
     * The HTTP client instance.
     *
     * @var \Illuminate\Http\Client\PendingRequest
     */
    protected $http;

    /**
     * The FCM configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Create a new Firebase Push Service instance.
     *
     * @param  array  $config
     * @return void
     *
     * @throws \Google\Cloud\Core\Exception\GoogleException
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge(config('fcm', []), $config);
        $this->projectId = $this->config['project_id'] ?? null;
        
        if (empty($this->projectId)) {
            throw new \InvalidArgumentException('Firebase project ID is not configured.');
        }

        $this->initializeFirebase();
        $this->initializeHttpClient();
    }

    /**
     * Initialize the Firebase Messaging instance.
     *
     * @return void
     *
     * @throws \Google\Cloud\Core\Exception\GoogleException
     */
    protected function initializeFirebase(): void
    {
        try {
            $factory = (new Factory)
                ->withServiceAccount($this->getCredentials())
                ->withDisabledAutoDiscovery();

            if ($this->config['logging']['enabled'] ?? false) {
                $factory = $factory->withEnabledDebugLog();
            }

            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            $this->logError('Failed to initialize Firebase', $e);
            throw $e;
        }
    }

    /**
     * Initialize the HTTP client.
     *
     * @return void
     */
    protected function initializeHttpClient(): void
    {
        $this->http = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'Content-Type' => 'application/json',
        ])->timeout($this->config['http']['timeout'] ?? 30);
    }

    /**
     * Get the Firebase credentials.
     *
     * @return array|string
     */
    protected function getCredentials()
    {
        if (!empty($this->config['credentials']['json']['project_id'])) {
            return $this->config['credentials']['json'];
        }

        if (file_exists($this->config['credentials']['file'])) {
            return $this->config['credentials']['file'];
        }

        throw new \RuntimeException('Firebase credentials not found.');
    }

    /**
     * Get an access token for the Firebase API.
     *
     * @return string
     *
     * @throws \Google\Cloud\Core\Exception\GoogleException
     */
    protected function getAccessToken(): string
    {
        $cacheKey = 'fcm_access_token';
        $cacheTtl = 3540; // 59 minutes (tokens expire after 1 hour)
        
        return cache()->remember($cacheKey, $cacheTtl, function () {
            $credentials = $this->getCredentials();
            
            if (is_array($credentials)) {
                $credentials = json_encode($credentials);
            }
            
            $response = Http::post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $this->createJwt(),
            ]);
            
            if (!$response->successful()) {
                throw new GoogleException('Failed to get access token: ' . $response->body());
            }
            
            return $response->json('access_token');
        });
    }

    /**
     * Create a JWT for authentication.
     *
     * @return string
     *
     * @throws \Google\Cloud\Core\Exception\GoogleException
     */
    protected function createJwt(): string
    {
        $credentials = $this->getCredentials();
        
        if (is_string($credentials) && file_exists($credentials)) {
            $credentials = json_decode(file_get_contents($credentials), true);
        }
        
        $now = time();
        
        $payload = [
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ];
        
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
            'kid' => $credentials['private_key_id'] ?? null,
        ];
        
        $segments = [];
        $segments[] = $this->urlSafeB64Encode(json_encode($header));
        $segments[] = $this->urlSafeB64Encode(json_encode($payload));
        
        $signingInput = implode('.', $segments);
        $signature = '';
        
        $key = $credentials['private_key'];
        $key = str_replace(['\\n', '\n'], "\n", $key);
        
        if (!openssl_sign($signingInput, $signature, $key, 'sha256')) {
            throw new GoogleException('Failed to sign JWT');
        }
        
        $segments[] = $this->urlSafeB64Encode($signature);
        
        return implode('.', $segments);
    }

    /**
     * URL-safe base64 encode.
     *
     * @param  string  $data
     * @return string
     */
    protected function urlSafeB64Encode(string $data): string
    {
        return str_replace('=', '', strtr(base64_encode($data), '+/', '-_'));
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
        try {
            $message = $this->createMessage($notification, $data, $options);
            $message = $message->withChangedTarget('token', $deviceToken);
            
            $response = $this->messaging->send($message, $options['validate_only'] ?? false);
            
            return [
                'success' => true,
                'message_id' => $response,
                'device_token' => $deviceToken,
            ];
        } catch (\Exception $e) {
            $this->logError('Failed to send push notification to device', $e, [
                'device_token' => $deviceToken,
                'notification' => $notification,
                'data' => $data,
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
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
        if (empty($deviceTokens)) {
            return [
                'success' => false,
                'error' => 'No device tokens provided',
            ];
        }
        
        // Limit batch size to 500 (FCM limit is 500 per batch)
        $batchSize = $options['batch_size'] ?? 500;
        $batches = array_chunk($deviceTokens, $batchSize);
        $results = [
            'success' => 0,
            'failure' => 0,
            'errors' => [],
            'responses' => [],
        ];
        
        foreach ($batches as $batch) {
            try {
                $message = $this->createMessage($notification, $data, $options);
                $response = $this->messaging->sendMulticast($message, $batch, $options['validate_only'] ?? false);;
                
                $results['success'] += $response->successes()->count();
                $results['failure'] += $response->failures()->count();
                $results['responses'][] = $response;
                
                foreach ($response->failures()->getItems() as $failure) {
                    $results['errors'][] = [
                        'device_token' => $failure->target()->value(),
                        'error' => $failure->error()->getMessage(),
                        'code' => $failure->error()->code(),
                    ];
                }
            } catch (\Exception $e) {
                $results['failure'] += count($batch);
                $results['errors'][] = [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                ];
                
                $this->logError('Failed to send multicast push notification', $e, [
                    'batch_size' => count($batch),
                    'notification' => $notification,
                ]);
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
        try {
            $topic = $this->normalizeTopicName($topic);
            $message = $this->createMessage($notification, $data, $options);
            $message = $message->withChangedTarget('topic', $topic);
            
            $response = $this->messaging->send($message, $options['validate_only'] ?? false);
            
            return [
                'success' => true,
                'message_id' => $response,
                'topic' => $topic,
            ];
        } catch (\Exception $e) {
            $this->logError('Failed to send push notification to topic', $e, [
                'topic' => $topic,
                'notification' => $notification,
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
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
        try {
            $deviceTokens = (array) $deviceTokens;
            $topic = $this->normalizeTopicName($topic);
            
            $this->messaging->subscribeToTopic($topic, $deviceTokens);
            
            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to subscribe device to topic', $e, [
                'device_tokens' => $deviceTokens,
                'topic' => $topic,
            ]);
            
            return false;
        }
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
        try {
            $deviceTokens = (array) $deviceTokens;
            $topic = $this->normalizeTopicName($topic);
            
            $this->messaging->unsubscribeFromTopic($topic, $deviceTokens);
            
            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to unsubscribe device from topic', $e, [
                'device_tokens' => $deviceTokens,
                'topic' => $topic,
            ]);
            
            return false;
        }
    }

    /**
     * Get information about a specific device token.
     *
     * @param  string  $deviceToken
     * @return array
     */
    public function getDeviceInfo(string $deviceToken): array
    {
        try {
            $appInstance = $this->messaging->getAppInstance($deviceToken);
            
            return [
                'success' => true,
                'app_instance' => [
                    'app_instance_id' => $appInstance->appInstanceId(),
                    'platform' => $appInstance->platform(),
                    'app_id' => $appInstance->appId(),
                    'auth_token' => $appInstance->authToken(),
                    'app_instance_id_token' => $appInstance->appInstanceIdToken(),
                    'app_instance_id_timestamp' => $appInstance->appInstanceIdTimestamp()?->format('c'),
                    'app_instance_id_authenticated_at' => $appInstance->appInstanceIdAuthenticatedAt()?->format('c'),
                    'app_instance_id_authenticated' => $appInstance->appInstanceIdAuthenticated(),
                    'app_instance_id_scope' => $appInstance->appInstanceIdScope(),
                    'app_instance_id_authorized_entity' => $appInstance->appInstanceIdAuthorizedEntity(),
                    'app_instance_id_status' => $appInstance->appInstanceIdStatus(),
                ],
            ];
        } catch (\Exception $e) {
            $this->logError('Failed to get device info', $e, [
                'device_token' => $deviceToken,
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }

    /**
     * Validate a device token.
     *
     * @param  string  $deviceToken
     * @return bool
     */
    public function validateDeviceToken(string $deviceToken): bool
    {
        try {
            $this->messaging->validateRegistrationTokens($deviceToken);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create a message with the given notification and data.
     *
     * @param  array  $notification
     * @param  array  $data
     * @param  array  $options
     * @return \Kreait\Firebase\Messaging\Message
     */
    protected function createMessage(array $notification, array $data = [], array $options = [])
    {
        $message = CloudMessage::new();
        
        // Set notification
        if (!empty($notification)) {
            $message = $message->withNotification(Notification::fromArray(
                array_merge($this->config['default_notification'] ?? [], $notification)
            ));
        }
        
        // Set data
        if (!empty($data)) {
            $message = $message->withData($data);
        }
        
        // Set Android config
        if ($androidConfig = $this->createAndroidConfig($options)) {
            $message = $message->withAndroidConfig($androidConfig);
        }
        
        // Set APNS config
        if ($apnsConfig = $this->createApnsConfig($options)) {
            $message = $message->withApnsConfig($apnsConfig);
        }
        
        // Set WebPush config
        if ($webPushConfig = $this->createWebPushConfig($options)) {
            $message = $message->withWebPushConfig($webPushConfig);
        }
        
        // Set message options
        if (isset($options['priority'])) {
            $message = $message->withPriority($options['priority']);
        }
        
        if (isset($options['ttl'])) {
            $message = $message->withTimeToLive($options['ttl']);
        }
        
        if (isset($options['collapse_key'])) {
            $message = $message->withCollapseKey($options['collapse_key']);
        }
        
        if (isset($options['mutable_content'])) {
            $message = $message->withMutableContent($options['mutable_content']);
        }
        
        return $message;
    }

    /**
     * Create Android config for the message.
     *
     * @param  array  $options
     * @return \Kreait\Firebase\Messaging\AndroidConfig|null
     */
    protected function createAndroidConfig(array $options = [])
    {
        $androidConfig = $options['android'] ?? [];
        
        if (empty($androidConfig) && empty($this->config['messaging']['android'])) {
            return null;
        }
        
        $config = AndroidConfig::new();
        
        if (isset($androidConfig['ttl'])) {
            $config = $config->withTtl($androidConfig['ttl']);
        }
        
        if (isset($androidConfig['priority'])) {
            $config = $config->withPriority($androidConfig['priority']);
        }
        
        if (isset($androidConfig['notification'])) {
            $notification = $androidConfig['notification'];
            $androidNotification = new \Kreait\Firebase\Messaging\AndroidNotification();
            
            if (isset($notification['icon'])) {
                $androidNotification = $androidNotification->withIcon($notification['icon']);
            }
            
            if (isset($notification['color'])) {
                $androidNotification = $androidNotification->withColor($notification['color']);
            }
            
            if (isset($notification['sound'])) {
                $androidNotification = $androidNotification->withSound($notification['sound']);
            }
            
            if (isset($notification['tag'])) {
                $androidNotification = $androidNotification->withTag($notification['tag']);
            }
            
            if (isset($notification['click_action'])) {
                $androidNotification = $androidNotification->withClickAction($notification['click_action']);
            }
            
            $config = $config->withNotification($androidNotification);
        }
        
        return $config;
    }

    /**
     * Create APNS config for the message.
     *
     * @param  array  $options
     * @return \Kreait\Firebase\Messaging\ApnsConfig|null
     */
    protected function createApnsConfig(array $options = [])
    {
        $apnsConfig = $options['apns'] ?? [];
        
        if (empty($apnsConfig) && empty($this->config['messaging']['ios'])) {
            return null;
        }
        
        $config = ApnsConfig::new();
        
        if (isset($apnsConfig['headers'])) {
            $config = $config->withHeaders($apnsConfig['headers']);
        }
        
        if (isset($apnsConfig['payload'])) {
            $config = $config->withPayload($apnsConfig['payload']);
        }
        
        return $config;
    }

    /**
     * Create WebPush config for the message.
     *
     * @param  array  $options
     * @return \Kreait\Firebase\Messaging\WebPushConfig|null
     */
    protected function createWebPushConfig(array $options = [])
    {
        $webPushConfig = $options['webpush'] ?? [];
        
        if (empty($webPushConfig) && empty($this->config['messaging']['webpush'])) {
            return null;
        }
        
        $config = WebPushConfig::new();
        
        if (isset($webPushConfig['headers'])) {
            $config = $config->withHeaders($webPushConfig['headers']);
        }
        
        if (isset($webPushConfig['data'])) {
            $config = $config->withData($webPushConfig['data']);
        }
        
        if (isset($webPushConfig['notification'])) {
            $config = $config->withNotification($webPushConfig['notification']);
        }
        
        return $config;
    }

    /**
     * Normalize topic name by adding prefix if needed.
     *
     * @param  string  $topic
     * @return string
     */
    protected function normalizeTopicName(string $topic): string
    {
        $prefix = $this->config['topics']['prefix'] ?? '';
        
        if (!empty($prefix) && strpos($topic, $prefix) !== 0) {
            $topic = $prefix . $topic;
        }
        
        return $topic;
    }

    /**
     * Log an error message.
     *
     * @param  string  $message
     * @param  \Throwable  $exception
     * @param  array  $context
     * @return void
     */
    protected function logError(string $message, \Throwable $exception, array $context = []): void
    {
        if ($this->config['logging']['enabled'] ?? false) {
            Log::channel($this->config['logging']['channel'] ?? 'stack')->error(
                $message . ': ' . $exception->getMessage(),
                array_merge($context, [
                    'exception' => $exception,
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ])
            );
        }
    }
}
