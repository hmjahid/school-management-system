<?php

namespace Tests\Feature;

use App\Http\Middleware\StandardizeApiResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiResponseEnvelopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_response_is_wrapped_in_standard_envelope(): void
    {
        $response = $this->getJson('/api/v1/payments/gateways');

        $response->assertOk();
        $response->assertJsonStructure(['success', 'message', 'data']);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'OK');
        $response->assertJsonIsArray('data');
    }

    public function test_webhook_payloads_are_not_rewrapped(): void
    {
        $request = Request::create('/api/v1/payments/webhook/bkash', 'POST', [], [], [], [], json_encode([]));
        $request->headers->set('Accept', 'application/json');

        $next = fn ($r) => response()->json(['ack' => 'ok']);

        $response = (new StandardizeApiResponse)->handle($request, $next);

        $this->assertSame(['ack' => 'ok'], $response->getData(true));
    }
}
