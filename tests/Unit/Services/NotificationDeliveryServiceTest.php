<?php

namespace Tests\Unit\Services;

use App\Contracts\PushNotificationService;
use App\Contracts\SmsService;
use App\Models\User;
use App\Services\NotificationDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class NotificationDeliveryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([\App\Events\NotificationSent::class]);
        $this->app->instance('request', new \Illuminate\Http\Request);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['email' => 'deliver@example.com'], $attrs));
    }

    protected function makeService(): NotificationDeliveryService
    {
        $sms = Mockery::mock(SmsService::class);
        $push = Mockery::mock(PushNotificationService::class);

        return new TestNotificationDeliveryService($sms, $push);
    }

    /** @test */
    public function it_routes_to_the_database_channel_and_creates_a_notification(): void
    {
        $user = $this->makeUser();
        $service = $this->makeService();

        $results = $service->send($user, 'refund_processed', ['amount' => 10], ['database']);

        $this->assertArrayHasKey('database', $results);
        $this->assertTrue($results['database']['success']);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);
    }

    /** @test */
    public function it_logs_each_delivery_attempt(): void
    {
        $user = $this->makeUser();
        $service = $this->makeService();

        $service->send($user, 'refund_processed', ['amount' => 10], ['database']);

        $this->assertDatabaseHas('notification_logs', [
            'notifiable_id' => $user->id,
            'type' => 'refund_processed',
        ]);
    }

    /** @test */
    public function it_sends_to_multiple_users_keyed_by_id(): void
    {
        $u1 = $this->makeUser(['email' => 'm1@example.com']);
        $u2 = $this->makeUser(['email' => 'm2@example.com']);
        $service = $this->makeService();

        $results = $service->sendToMany([$u1, $u2], 'refund_processed', ['amount' => 5], ['database']);

        $this->assertArrayHasKey($u1->id, $results);
        $this->assertArrayHasKey($u2->id, $results);
        $this->assertTrue($results[$u1->id]['database']['success']);
        $this->assertTrue($results[$u2->id]['database']['success']);
    }

    /** @test */
    public function it_uses_default_channels_when_none_specified(): void
    {
        $user = $this->makeUser();
        $service = $this->makeService();

        $results = $service->send($user, 'refund_processed', ['amount' => 5]);

        // Database is always enabled by default and should succeed.
        $this->assertArrayHasKey('database', $results);
        $this->assertTrue($results['database']['success']);
    }

    /** @test */
    public function it_reports_failure_when_a_channel_handler_throws(): void
    {
        $user = $this->makeUser();
        $sms = Mockery::mock(SmsService::class);
        $push = Mockery::mock(PushNotificationService::class);

        $service = new TestNotificationDeliveryService($sms, $push);

        // Mail channel has no matching template (NotificationTemplate uses `key`, not `type`)
        // so it should be reported as a failure rather than crash.
        $results = $service->send($user, 'refund_processed', ['amount' => 5], ['mail']);

        $this->assertArrayHasKey('mail', $results);
        $this->assertFalse($results['mail']['success']);
        $this->assertArrayHasKey('error', $results['mail']);
    }
}

class TestNotificationDeliveryService extends NotificationDeliveryService
{
    protected function getUserPreferences(\App\Models\User $user, string $type): array
    {
        return ['database' => true, 'mail' => true, 'sms' => true, 'push' => true];
    }
}
