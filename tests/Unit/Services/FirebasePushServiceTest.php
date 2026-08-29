<?php

namespace Tests\Unit\Services;

use App\Services\Push\FirebasePushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FirebasePushServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $privateKey;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'fake-token'], 200),
        ]);

        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'digest_alg' => 'sha256',
            'bits' => 2048,
        ]);
        openssl_pkey_export($key, $this->privateKey);
    }

    protected function callProtected(object $obj, string $method, ...$args)
    {
        $r = new \ReflectionMethod($obj, $method);
        $r->setAccessible(true);
        return $r->invokeArgs($obj, $args);
    }

    protected function makeConfig(): array
    {
        return [
            'project_id' => 'test-project',
            'credentials' => [
                'json' => [
                    'project_id' => 'test-project',
                    'client_email' => 'firebase@test-project.iam.gserviceaccount.com',
                    'private_key' => $this->privateKey,
                    'private_key_id' => 'kid123',
                ],
            ],
            'logging' => ['enabled' => false],
            'topics' => ['prefix' => 'app_'],
        ];
    }

    protected function makeService(): FirebasePushService
    {
        return new FirebasePushService($this->makeConfig());
    }

    /** @test */
    public function it_sends_a_push_notification_to_a_single_device(): void
    {
        $service = $this->makeService();

        $result = $service->sendToDevice('token123', ['title' => 'Hi', 'body' => 'Body']);

        $this->assertTrue($result['success']);
        $this->assertSame('msg_id_123', $result['message_id']);
        $this->assertSame('token123', $result['device_token']);
    }

    /** @test */
    public function it_sends_a_push_notification_to_multiple_devices_and_counts(): void
    {
        $service = $this->makeService();

        $result = $service->sendToDevices(['t1', 't2'], ['title' => 'Hi', 'body' => 'Body']);

        $this->assertSame(2, $result['success']);
        $this->assertSame(0, $result['failure']);
    }

    /** @test */
    public function it_returns_failure_for_empty_device_tokens(): void
    {
        $service = $this->makeService();

        $result = $service->sendToDevices([], ['title' => 'Hi', 'body' => 'Body']);

        $this->assertFalse($result['success']);
        $this->assertSame('No device tokens provided', $result['error']);
    }

    /** @test */
    public function it_sends_a_push_notification_to_a_topic(): void
    {
        $service = $this->makeService();

        $result = $service->sendToTopic('news', ['title' => 'Hi', 'body' => 'Body']);

        $this->assertTrue($result['success']);
        $this->assertSame('app_news', $result['topic']);
    }

    /** @test */
    public function it_subscribes_and_unsubscribes_from_a_topic(): void
    {
        $service = $this->makeService();

        $this->assertTrue($service->subscribeToTopic('token123', 'news'));
        $this->assertTrue($service->unsubscribeFromTopic('token123', 'news'));
    }

    /** @test */
    public function it_gets_device_info(): void
    {
        $service = $this->makeService();

        $result = $service->getDeviceInfo('token123');

        $this->assertTrue($result['success']);
        $this->assertSame('test', $result['app_instance']['platform']);
    }

    /** @test */
    public function it_validates_a_device_token(): void
    {
        $service = $this->makeService();

        $this->assertTrue($service->validateDeviceToken('token123'));
    }

    /** @test */
    public function it_normalizes_topic_names_with_a_prefix(): void
    {
        $service = $this->makeService();

        $this->assertSame('app_news', $this->callProtected($service, 'normalizeTopicName', 'news'));
        $this->assertSame('app_news', $this->callProtected($service, 'normalizeTopicName', 'app_news'));
    }
}

namespace Kreait\Firebase;

class Factory
{
    public function withServiceAccount($c)
    {
        return $this;
    }

    public function withDisabledAutoDiscovery()
    {
        return $this;
    }

    public function withEnabledDebugLog()
    {
        return $this;
    }

    public function createMessaging()
    {
        return new \Tests\Unit\Services\FcmMessagingStub();
    }
}

namespace Kreait\Firebase\Messaging;

class CloudMessage
{
    public function withNotification($n)
    {
        return $this;
    }

    public function withData($d)
    {
        return $this;
    }

    public function withChangedTarget($t, $v)
    {
        return $this;
    }

    public static function new()
    {
        return new self();
    }
}

class Notification
{
    public static function fromArray($a)
    {
        return new self();
    }
}

class AndroidConfig
{
    public static function new()
    {
        return new self();
    }

    public function withTtl($t)
    {
        return $this;
    }

    public function withPriority($p)
    {
        return $this;
    }

    public function withNotification($n)
    {
        return $this;
    }
}

class ApnsConfig
{
    public static function new()
    {
        return new self();
    }

    public function withHeaders($h)
    {
        return $this;
    }

    public function withPayload($p)
    {
        return $this;
    }
}

class WebPushConfig
{
    public static function new()
    {
        return new self();
    }

    public function withHeaders($h)
    {
        return $this;
    }

    public function withData($d)
    {
        return $this;
    }

    public function withNotification($n)
    {
        return $this;
    }
}

namespace Tests\Unit\Services;

class FcmMessagingStub
{
    public function send($message, $validate = false)
    {
        return 'msg_id_123';
    }

    public function sendMulticast($message, $tokens, $validate = false)
    {
        return new FcmMulticastResponseStub();
    }

    public function subscribeToTopic($topic, $tokens)
    {
    }

    public function unsubscribeFromTopic($topic, $tokens)
    {
    }

    public function getAppInstance($token)
    {
        return new FcmAppInstanceStub();
    }

    public function validateRegistrationTokens($token)
    {
    }
}

class FcmMulticastResponseStub
{
    public function successes()
    {
        return new class {
            public function count()
            {
                return 2;
            }
        };
    }

    public function failures()
    {
        return new class {
            public function count()
            {
                return 0;
            }

            public function getItems()
            {
                return [];
            }
        };
    }
}

class FcmAppInstanceStub
{
    public function appInstanceId()
    {
        return 'id';
    }

    public function platform()
    {
        return 'test';
    }

    public function appId()
    {
        return 'app';
    }

    public function authToken()
    {
        return 'tok';
    }

    public function appInstanceIdToken()
    {
        return 't';
    }

    public function appInstanceIdTimestamp()
    {
        return null;
    }

    public function appInstanceIdAuthenticatedAt()
    {
        return null;
    }

    public function appInstanceIdAuthenticated()
    {
        return true;
    }

    public function appInstanceIdScope()
    {
        return 's';
    }

    public function appInstanceIdAuthorizedEntity()
    {
        return 'e';
    }

    public function appInstanceIdStatus()
    {
        return 'active';
    }
}
