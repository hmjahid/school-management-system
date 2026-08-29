<?php

namespace Tests\Feature\Payment;

use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\PaymentGatewaySeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    /** @test */
    public function unexpected_fields_are_not_persisted_on_payment_initiation()
    {
        Http::fake([
            '*checkout/token/grant*' => Http::response(['id_token' => 'TEST_TOKEN'], 200),
            '*checkout/create*' => Http::response([
                'paymentID' => 'PID123',
                'bkashURL' => 'https://bkash.com/pay/123',
                'createTime' => now()->toIso8601String(),
                'orgLogo' => 'logo.png',
            ], 200),
        ]);

        // An attacker attempts to set privileged/payment fields directly.
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/payments/initiate', [
                'gateway' => 'bkash',
                'amount' => 1000,
                'currency' => 'BDT',
                'paymentable_type' => 'tuition',
                'paymentable_id' => 1,
                'description' => 'Test payment',
                // Attacker-controlled fields that must be ignored:
                'payment_status' => 'completed',
                'paid_amount' => 9999,
                'due_amount' => 0,
                'transaction_id' => 'HACKED',
                'is_admin' => 1,
                'role_id' => 1,
            ]);

        $response->assertStatus(200);

        $payment = Payment::latest()->first();

        // The Form Request only exposes the declared fields, so the model is
        // built from validated input with a computed total and a forced pending
        // status — never the attacker-supplied values.
        $this->assertSame(Payment::STATUS_PENDING, $payment->payment_status);
        $this->assertSame(0.0, (float) $payment->paid_amount);
        $this->assertSame(1000.0, (float) $payment->amount);
        $this->assertNull($payment->transaction_id);
        $this->assertNotSame('completed', $payment->payment_status);
    }

    /** @test */
    public function initiate_rejects_unknown_gateway_without_persisting_anything()
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/payments/initiate', [
                'gateway' => 'nonexistent',
                'amount' => 1000,
                'currency' => 'BDT',
                'paymentable_type' => 'tuition',
                'paymentable_id' => 1,
                'payment_status' => 'completed',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['gateway']);

        $this->assertDatabaseCount('payments', 0);
    }
}
