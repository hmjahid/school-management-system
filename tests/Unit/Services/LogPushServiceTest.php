<?php

namespace Tests\Unit\Services;

use App\Services\Push\LogPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogPushServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeService(): LogPushService
    {
        return new LogPushService(['log_channel' => 'stack']);
    }

    /** @test */
    public function it_sends_a_push_notification_to_a_single_device(): void
    {
        $service = $this->makeService();

        $result = $service->sendToDevice('token123', ['title' => 'Hi', 'body' => 'Body']);

        $this->assertTrue($result['success']);
        $this->assertSame('token123', $result['device_token']);
        $this->assertArrayHasKey('message_id', $result);
    }

    /** @test */
    public function it_sends_to_multiple_devices_and_counts_successes(): void
    {
        $service = $this->makeService();

        $result = $service->sendToDevices(['t1', 't2'], ['title' => 'Hi', 'body' => 'Body']);

        $this->assertSame(2, $result['success']);
        $this->assertSame(0, $result['failure']);
        $this->assertCount(2, $result['responses']);
    }

    /** @test */
    public function it_sends_a_push_notification_to_a_topic(): void
    {
        $service = $this->makeService();

        $result = $service->sendToTopic('news', ['title' => 'Hi', 'body' => 'Body']);

        $this->assertTrue($result['success']);
        $this->assertSame('news', $result['topic']);
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
        $this->assertSame('token123', $result['device_token']);
    }

    /** @test */
    public function it_validates_any_device_token(): void
    {
        $service = $this->makeService();

        $this->assertTrue($service->validateDeviceToken('token123'));
    }

    /** @test */
    public function it_logs_push_attempts(): void
    {
        $handler = new \Monolog\Handler\TestHandler();
        Log::channel('stack')->getLogger()->pushHandler($handler);

        $service = $this->makeService();
        $service->sendToDevice('token123', ['title' => 'Hi', 'body' => 'Body']);

        $this->assertNotEmpty($handler->getRecords());
    }
}
