<?php
declare(strict_types=1);

namespace Tests\Unit\Services\Sms;

use App\Services\Sms\LogSmsService;
use Tests\TestCase;

class LogSmsServiceTest extends TestCase
{
    private string $tempLog = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempLog = tempnam(sys_get_temp_dir(), 'esk_sms_') ?: '';
    }

    protected function tearDown(): void
    {
        if ($this->tempLog !== '' && file_exists($this->tempLog)) {
            @unlink($this->tempLog);
        }
        parent::tearDown();
    }

    public function test_send_records_and_reports_success(): void
    {
        $service = new LogSmsService([
            'log_file' => $this->tempLog,
            'country_code' => '1',
        ]);

        $result = $service->send('+14155552671', 'Hello INT');

        $this->assertTrue($result['success']);
        $this->assertSame('logged', $result['status']);
        $this->assertSame('log', $result['provider']);
        $this->assertStringStartsWith('log-', $result['message_id']);
        $this->assertStringContainsString('Hello INT', (string) file_get_contents($this->tempLog));
    }

    public function test_get_balance_is_placeholder(): void
    {
        $service = new LogSmsService(['log_file' => $this->tempLog]);

        $this->assertSame(['amount' => 0.0, 'currency' => 'credit'], $service->getBalance());
    }

    public function test_get_status_indicates_preview_only(): void
    {
        $service = new LogSmsService(['log_file' => $this->tempLog]);

        $status = $service->getStatus('log-1');
        $this->assertSame('logged', $status['status']);
        $this->assertTrue($status['preview_only']);
    }

    public function test_driver_name_is_log(): void
    {
        $service = new LogSmsService(['log_file' => $this->tempLog]);

        $this->assertSame('log', $service->name());
    }
}