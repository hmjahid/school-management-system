<?php

namespace Tests\Unit\Models;

use App\Models\PaymentWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookEventTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_required_columns(): void
    {
        $event = PaymentWebhookEvent::create([
            'gateway' => 'bkash',
            'payload_hash' => 'hash'.uniqid(),
        ]);

        $this->assertDatabaseHas('payment_webhook_events', [
            'gateway' => 'bkash',
            'payload_hash' => $event->payload_hash,
        ]);

        $this->assertNotNull($event->id);
    }

    /** @test */
    public function payload_hash_is_unique(): void
    {
        $hash = 'dup'.uniqid();

        PaymentWebhookEvent::create(['gateway' => 'bkash', 'payload_hash' => $hash]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        PaymentWebhookEvent::create(['gateway' => 'bkash', 'payload_hash' => $hash]);
    }

    /** @test */
    public function it_casts_json_and_datetime_columns(): void
    {
        $event = PaymentWebhookEvent::create([
            'gateway' => 'nagad',
            'payload_hash' => 'hash'.uniqid(),
            'headers' => ['X-Signature' => 'abc'],
            'payload' => ['status' => 'success'],
            'processed_at' => '2026-03-01 10:00:00',
            'result_status' => 'completed',
        ]);

        $this->assertIsArray($event->headers);
        $this->assertIsArray($event->payload);
        $this->assertEquals('abc', $event->headers['X-Signature']);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $event->processed_at);
    }
}
