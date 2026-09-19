<?php

namespace Tests\Unit\Services;

use App\Contracts\SmsService;
use App\Services\LogSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogSmsServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_implements_sms_service_contract(): void
    {
        $this->assertInstanceOf(SmsService::class, new LogSmsService);
    }

    #[Test]
    public function send_returns_true_and_logs(): void
    {
        Log::shouldReceive('info')->once();

        $service = new LogSmsService;
        $result = $service->send('01700000000', 'Hello', ['foo' => 'bar']);

        $this->assertTrue($result);
    }

    #[Test]
    public function get_balance_returns_dummy_value(): void
    {
        $service = new LogSmsService;

        $this->assertEquals(100.0, $service->getBalance());
    }

    #[Test]
    public function get_status_returns_delivered(): void
    {
        $service = new LogSmsService;
        $status = $service->getStatus('MSG123');

        $this->assertEquals('delivered', $status['status']);
        $this->assertEquals('MSG123', $status['message_id']);
    }
}
