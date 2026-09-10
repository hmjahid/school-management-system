<?php
declare(strict_types=1);

namespace Tests\Unit\Services\Sms;

use App\Services\Sms\VonageSmsService;
use Tests\TestCase;

class FakeVonageSmsService extends VonageSmsService
{
    public array $requests = [];

    public array $responses = [];

    public function __construct(array $config = [], array $responses = [])
    {
        $this->responses = $responses;
        parent::__construct($config);
    }

    protected function httpRequest(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $this->requests[] = compact('method', 'url', 'headers', 'body');

        $response = array_shift($this->responses) ?? ['status' => 202, 'body' => '{"message_uuid":"MSG-1"}'];

        return $response;
    }
}

class VonageSmsServiceTest extends TestCase
{
    public function test_send_success_returns_message_uuid(): void
    {
        $service = new FakeVonageSmsService([
            'api_key' => 'key',
            'api_secret' => 'secret',
            'from' => 'Eskoofy',
        ]);

        $result = $service->send('+14155552671', 'Hello INT');

        $this->assertTrue($result['success']);
        $this->assertSame('MSG-1', $result['message_id']);
        $this->assertSame('accepted', $result['status']);
        $this->assertSame('vonage', $result['provider']);
    }

    public function test_send_posts_json_to_messages_api(): void
    {
        $service = new FakeVonageSmsService([
            'api_key' => 'key',
            'api_secret' => 'secret',
            'from' => 'Eskoofy',
        ]);

        $service->send('+14155552671', 'Hello', ['client_ref' => 'order-9']);

        $this->assertCount(1, $service->requests);
        $request = $service->requests[0];

        $this->assertSame('POST', $request['method']);
        $this->assertStringContainsString('/v1/messages', $request['url']);
        $this->assertStringContainsString('Authorization: Basic ', implode("\r\n", $request['headers']));

        $payload = json_decode((string) $request['body'], true);
        $this->assertSame('+14155552671', $payload['to']);
        $this->assertSame('Hello', $payload['text']);
        $this->assertSame('sms', $payload['channel']);
        $this->assertSame('text', $payload['message_type']);
        $this->assertSame('order-9', $payload['client_ref']);
    }

    public function test_send_adds_delivery_webhook_when_provided(): void
    {
        $service = new FakeVonageSmsService([
            'api_key' => 'key',
            'api_secret' => 'secret',
            'from' => 'Eskoofy',
        ]);

        $service->send('+14155552671', 'Hi', ['callback_url' => 'https://x.test/delivery']);

        $payload = json_decode((string) $service->requests[0]['body'], true);
        $this->assertSame('https://x.test/delivery', $payload['webhooks']['delivery']['address']);
    }

    public function test_send_fails_when_credentials_missing(): void
    {
        $service = new FakeVonageSmsService(['api_key' => '', 'api_secret' => '']);

        $result = $service->send('+14155552671', 'Hello');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not configured', $result['error']);
    }

    public function test_send_fails_on_non_2xx(): void
    {
        $service = new FakeVonageSmsService(
            ['api_key' => 'key', 'api_secret' => 'secret', 'from' => 'Eskoofy'],
            [['status' => 401, 'body' => '{"error":"Bad credentials"}']]
        );

        $result = $service->send('+14155552671', 'Hello');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Bad credentials', $result['error']);
    }

    public function test_balance_parses_amount(): void
    {
        $service = new FakeVonageSmsService(
            ['api_key' => 'key', 'api_secret' => 'secret'],
            [['status' => 200, 'body' => '{"value":12.75,"currency":"EUR"}']]
        );

        $this->assertSame(['amount' => 12.75, 'currency' => 'EUR'], $service->getBalance());
    }

    public function test_status_is_async_acceptance(): void
    {
        $service = new FakeVonageSmsService(['api_key' => 'key', 'api_secret' => 'secret']);

        $status = $service->getStatus('MSG-1');

        $this->assertSame('accepted', $status['status']);
        $this->assertSame('MSG-1', $status['message_id']);
    }

    public function test_defaults_read_nexmo_fallback_keys(): void
    {
        $prevKey = $_ENV['NEXMO_KEY'] ?? null;
        $prevSecret = $_ENV['NEXMO_SECRET'] ?? null;
        $_ENV['NEXMO_KEY'] = 'fallback-key';
        $_ENV['NEXMO_SECRET'] = 'fallback-secret';

        try {
            $service = new FakeVonageSmsService();
            $result = $service->send('+14155552671', 'Hi');
            $this->assertTrue($result['success']);
        } finally {
            if ($prevKey === null) {
                unset($_ENV['NEXMO_KEY']);
            } else {
                $_ENV['NEXMO_KEY'] = $prevKey;
            }
            if ($prevSecret === null) {
                unset($_ENV['NEXMO_SECRET']);
            } else {
                $_ENV['NEXMO_SECRET'] = $prevSecret;
            }
        }
    }
}