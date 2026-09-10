<?php

namespace Tests\Unit\Services;

use App\Services\Sms\VonageSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VonageSmsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeService(array $config = []): VonageSmsService
    {
        return new VonageSmsService(array_merge([
            'api_key' => 'key123',
            'api_secret' => 'secret',
            'from' => 'Eskoofy',
        ], $config));
    }

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

    #[Test]
    public function it_sends_successfully_via_messages_api(): void
    {
        Http::fake([
            'api.nexmo.com/v1/messages' => Http::response(['message_uuid' => 'MSG-1'], 202),
        ]);

        $service = $this->makeService();

        $this->assertTrue($service->send('+14155552671', 'Hello INT'));

        Http::assertSent(function (Request $request) {
            $payload = $request->data();

            return str_contains($request->url(), '/v1/messages')
                && $payload['to'] === '+14155552671'
                && $payload['text'] === 'Hello INT'
                && $payload['message_type'] === 'text'
                && $payload['channel'] === 'sms'
                && $payload['from'] === 'Eskoofy';
        });
    }

    #[Test]
    public function it_uses_basic_auth_with_api_credentials(): void
    {
        Http::fake([
            'api.nexmo.com/v1/messages' => Http::response(['message_uuid' => 'MSG-1'], 202),
        ]);

        $service = $this->makeService();
        $service->send('+14155552671', 'Hi');

        Http::assertSent(function (Request $request) {
            return $request->hasHeader('Authorization')
                && str_contains($request->header('Authorization')[0], 'Basic');
        });
    }

    #[Test]
    public function it_returns_false_when_the_api_rejects(): void
    {
        Http::fake([
            'api.nexmo.com/v1/messages' => Http::response(['error' => 'sending_failed'], 400),
        ]);

        $service = $this->makeService();

        $this->assertFalse($service->send('+14155552671', 'Hi'));
    }

    #[Test]
    public function it_returns_false_when_no_message_uuid_is_present(): void
    {
        Http::fake([
            'api.nexmo.com/v1/messages' => Http::response([], 200),
        ]);

        $service = $this->makeService();

        $this->assertFalse($service->send('+14155552671', 'Hi'));
    }

    #[Test]
    public function it_returns_false_when_the_api_throws(): void
    {
        Http::fake([
            'api.nexmo.com/v1/messages' => Http::response(fn () => throw new \RuntimeException('boom'), 500),
        ]);

        $service = $this->makeService();

        $this->assertFalse($service->send('+14155552671', 'Hi'));
    }

    #[Test]
    public function it_passes_client_ref_and_callback_when_provided(): void
    {
        Http::fake([
            'api.nexmo.com/v1/messages' => Http::response(['message_uuid' => 'MSG-1'], 202),
        ]);

        $service = $this->makeService();

        $service->send('+14155552671', 'Hi', [
            'client_ref' => 'order-42',
            'callback_url' => 'https://x.test/sms/delivery',
        ]);

        Http::assertSent(function (Request $request) {
            $payload = $request->data();

            return $payload['client_ref'] === 'order-42'
                && ($payload['webhooks']['delivery']['address'] ?? null) === 'https://x.test/sms/delivery';
        });
    }

    #[Test]
    public function it_returns_the_balance(): void
    {
        Http::fake([
            'api.nexmo.com/account/get-balance' => Http::response(['value' => 42.75]),
        ]);

        $service = $this->makeService();

        $this->assertSame(42.75, $service->getBalance());
    }

    #[Test]
    public function it_returns_zero_balance_on_error(): void
    {
        Http::fake([
            'api.nexmo.com/account/get-balance' => Http::response(['error' => 'forbidden'], 403),
        ]);

        $service = $this->makeService();

        $this->assertSame(0.0, $service->getBalance());
    }

    #[Test]
    public function it_exposes_message_status(): void
    {
        $service = $this->makeService();

        $status = $service->getStatus('MSG-1');

        $this->assertSame('accepted', $status['status']);
        $this->assertSame('MSG-1', $status['message_uuid']);
    }

    #[Test]
    public function it_formats_numbers_into_e164(): void
    {
        $service = $this->makeService();

        $formatted = $this->callProtected($service, 'formatPhoneNumber', '0123456789');
        $this->assertSame('+1123456789', $formatted);
    }

    #[Test]
    public function it_accepts_the_nexmo_alias(): void
    {
        $service = $this->makeService(['api_key' => 'nx', 'api_secret' => 'ns']);

        $this->assertSame('nx', $this->getProtected($service, 'config')['api_key']);
    }
}