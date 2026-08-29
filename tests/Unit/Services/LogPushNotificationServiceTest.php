<?php

namespace Tests\Unit\Services;

use App\Contracts\PushNotificationService;
use App\Services\LogPushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogPushNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_implements_push_notification_service_contract(): void
    {
        $this->assertInstanceOf(PushNotificationService::class, new LogPushNotificationService);
    }

    /** @test */
    public function send_to_device_returns_success(): void
    {
        $service = new LogPushNotificationService;
        $result = $service->sendToDevice('token-1', ['title' => 'Hi'], ['k' => 'v']);

        $this->assertTrue($result['success']);
        $this->assertEquals('token-1', $result['device_token']);
        $this->assertArrayHasKey('message_id', $result);
    }

    /** @test */
    public function send_to_devices_returns_success(): void
    {
        $service = new LogPushNotificationService;
        $result = $service->sendToDevices(['t1', 't2'], ['title' => 'Hi']);

        $this->assertTrue($result['success']);
        $this->assertEquals(['t1', 't2'], $result['device_tokens']);
    }

    /** @test */
    public function send_to_topic_returns_success(): void
    {
        $service = new LogPushNotificationService;
        $result = $service->sendToTopic('news', ['title' => 'Hi']);

        $this->assertTrue($result['success']);
        $this->assertEquals('news', $result['topic']);
    }

    /** @test */
    public function subscribe_and_unsubscribe_return_true(): void
    {
        $service = new LogPushNotificationService;

        $this->assertTrue($service->subscribeToTopic('token-1', 'news'));
        $this->assertTrue($service->unsubscribeFromTopic('token-1', 'news'));
        $this->assertTrue($service->unsubscribeFromAllTopics('token-1'));
    }

    /** @test */
    public function validate_device_token_checks_length(): void
    {
        $service = new LogPushNotificationService;

        $this->assertTrue($service->validateDeviceToken('abcdefghijk'));
        $this->assertFalse($service->validateDeviceToken('short'));
    }

    /** @test */
    public function get_device_info_returns_mock_data(): void
    {
        $service = new LogPushNotificationService;
        $info = $service->getDeviceInfo('token-1');

        $this->assertEquals('token-1', $info['device_token']);
        $this->assertTrue($info['is_active']);
    }
}
