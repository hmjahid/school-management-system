<?php

namespace Tests\Unit\Services;

use App\Services\Sms\BaseSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BaseSmsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function getProtected(object $obj, string $prop)
    {
        $r = new \ReflectionProperty($obj, $prop);
        $r->setAccessible(true);
        return $r->getValue($obj);
    }

    protected function callProtected(object $obj, string $method, ...$args)
    {
        $r = new \ReflectionMethod($obj, $method);
        $r->setAccessible(true);
        return $r->invokeArgs($obj, $args);
    }

    protected function makeSuccessfulService(): BaseSmsService
    {
        return new class extends BaseSmsService {
            protected function getDefaultConfig(): array
            {
                return ['from' => 'DEF', 'country_code' => '88'];
            }

            protected function sendSms(string $to, string $message, array $options = [])
            {
                $this->setLastResponse(['ok' => true]);
                return (object) ['sid' => 'ABC'];
            }

            protected function wasSuccessful($response): bool
            {
                return is_object($response) && !empty($response->sid);
            }

            public function getBalance(): float
            {
                return 5.0;
            }

            public function getStatus(string $messageId): array
            {
                return ['status' => 'sent'];
            }
        };
    }

    protected function makeFailingService(): BaseSmsService
    {
        return new class extends BaseSmsService {
            protected function getDefaultConfig(): array
            {
                return ['from' => 'DEF', 'country_code' => '88'];
            }

            protected function sendSms(string $to, string $message, array $options = [])
            {
                throw new \RuntimeException('boom');
            }

            protected function wasSuccessful($response): bool
            {
                return false;
            }

            public function getBalance(): float
            {
                return 0.0;
            }

            public function getStatus(string $messageId): array
            {
                return ['status' => 'failed'];
            }
        };
    }

    /** @test */
    public function it_returns_true_when_send_succeeds(): void
    {
        $service = $this->makeSuccessfulService();

        $this->assertTrue($service->send('01700000000', 'Hello'));
    }

    /** @test */
    public function it_returns_false_when_send_throws(): void
    {
        $service = $this->makeFailingService();

        $this->assertFalse($service->send('01700000000', 'Hello'));
    }

    /** @test */
    public function it_formats_a_phone_number_into_e164(): void
    {
        $service = $this->makeSuccessfulService();

        $formatted = $this->callProtected($service, 'formatPhoneNumber', '0123456789');

        $this->assertSame('+88123456789', $formatted);
    }

    /** @test */
    public function it_returns_the_configured_sender(): void
    {
        $service = $this->makeSuccessfulService();

        $this->assertSame('DEF', $this->callProtected($service, 'getFrom'));
    }

    /** @test */
    public function it_exposes_the_last_response(): void
    {
        $service = $this->makeSuccessfulService();
        $service->send('01700000000', 'Hello');

        $this->assertIsArray($service->getLastResponse());
        $this->assertSame(['ok' => true], $service->getLastResponse());
    }

    /** @test */
    public function it_normalizes_arbitrary_responses(): void
    {
        $service = $this->makeSuccessfulService();

        $this->callProtected($service, 'setLastResponse', 'raw-string');
        $this->assertSame(['raw' => 'raw-string'], $service->getLastResponse());

        $this->callProtected($service, 'setLastResponse', ['a' => 1]);
        $this->assertSame(['a' => 1], $service->getLastResponse());
    }

    /** @test */
    public function it_logs_sms_attempts_without_error(): void
    {
        Log::spy();

        $service = $this->makeSuccessfulService();
        $this->callProtected($service, 'logSms', '01700000000', 'Hello', ['ok' => true]);

        Log::shouldHaveReceived('info')->once();
    }
}
