<?php
declare(strict_types=1);

namespace Tests\Unit\Services\Sms;

use App\Services\Sms\TwilioSmsService;
use Tests\TestCase;

class FakeTwilioSmsService extends TwilioSmsService
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

        $response = array_shift($this->responses) ?? ['status' => 200, 'body' => '{"sid":"SM123","status":"queued"}'];

        return $response;
    }
}

class TwilioSmsServiceTest extends TestCase
{
    public function test_send_success_returns_sid_and_queued_status(): void
    {
        $service = new FakeTwilioSmsService([
            'account_sid' => 'ACtest',
            'auth_token' => 'token',
            'from' => '+15005550006',
        ]);

        $result = $service->send('+14155552671', 'Hello');

        $this->assertTrue($result['success']);
        $this->assertSame('SM123', $result['message_id']);
        $this->assertSame('queued', $result['status']);
        $this->assertSame('twilio', $result['provider']);
    }

    public function test_send_posts_to_messages_endpoint_with_form_payload(): void
    {
        $service = new FakeTwilioSmsService([
            'account_sid' => 'ACtest',
            'auth_token' => 'token',
            'from' => '+15005550006',
        ]);

        $service->send('+14155552671', 'Hello');

        $this->assertCount(1, $service->requests);
        $request = $service->requests[0];
        $this->assertSame('POST', $request['method']);
        $this->assertStringContainsString('/2010-04-01/Accounts/ACtest/Messages.json', $request['url']);
        $this->assertStringContainsString('To=%2B14155552671', $request['body']);
        $this->assertStringContainsString('Body=Hello', $request['body']);
        $this->assertStringContainsString('Basic', implode(',', $request['headers']));
    }

    public function test_send_fails_when_credentials_missing(): void
    {
        $service = new FakeTwilioSmsService(['account_sid' => '', 'auth_token' => '']);

        $result = $service->send('+14155552671', 'Hello');

        $this->assertFalse($result['success']);
        $this->assertSame('failed', $result['status']);
        $this->assertStringContainsString('not configured', $result['error']);
    }

    public function test_send_fails_on_non_2xx(): void
    {
        $service = new FakeTwilioSmsService(
            ['account_sid' => 'ACtest', 'auth_token' => 'token', 'from' => '+15005550006'],
            [['status' => 401, 'body' => '{"code":20003,"message":"Authentication Error"}']]
        );

        $result = $service->send('+14155552671', 'Hello');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Authentication Error', $result['error']);
    }

    public function test_balance_parses_usd_amount(): void
    {
        $service = new FakeTwilioSmsService(
            ['account_sid' => 'ACtest', 'auth_token' => 'token'],
            [['status' => 200, 'body' => '{"balance":"42.50","currency":"USD"}']]
        );

        $this->assertSame(['amount' => 42.5, 'currency' => 'USD'], $service->getBalance());
    }

    public function test_status_returns_message_payload(): void
    {
        $service = new FakeTwilioSmsService(
            ['account_sid' => 'ACtest', 'auth_token' => 'token'],
            [['status' => 200, 'body' => '{"sid":"SM123","status":"delivered"}']]
        );

        $status = $service->getStatus('SM123');

        $this->assertSame('delivered', $status['status']);
        $this->assertSame('SM123', $status['sid']);
    }

    public function test_formats_local_number_with_country_code(): void
    {
        $service = new FakeTwilioSmsService([
            'account_sid' => 'ACtest',
            'auth_token' => 'token',
            'from' => '+15005550006',
            'country_code' => '44',
        ]);

        $result = $service->send('0123456789', 'Hi');
        $request = $service->requests[0];

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('To=%2B44123456789', $request['body']);
    }
}