<?php

namespace Tests\Feature;

use App\Contracts\PushNotificationService;
use App\Contracts\SmsService;
use App\Services\Push\LogPushService;
use App\Services\Sms\LogSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationProviderSelectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function default_sms_driver_is_log_in_test_environment(): void
    {
        $this->assertSame('log', config('sms.default'));

        $service = app(SmsService::class);
        $this->assertInstanceOf(LogSmsService::class, $service);
    }

    #[Test]
    public function default_push_driver_is_log_in_test_environment(): void
    {
        $this->assertSame('log', config('fcm.driver'));

        $service = app(PushNotificationService::class);
        $this->assertInstanceOf(LogPushService::class, $service);
    }

    #[Test]
    public function sms_service_is_a_singleton(): void
    {
        $first = app(SmsService::class);
        $second = app(SmsService::class);

        $this->assertSame($first, $second);
    }

    #[Test]
    public function log_sms_service_returns_synthetic_success(): void
    {
        $service = app(SmsService::class);

        $this->assertTrue($service->send('01700000000', 'Test message'));
        $this->assertGreaterThan(0, $service->getBalance());
        $this->assertIsArray($service->getStatus('test-id'));
    }
}
