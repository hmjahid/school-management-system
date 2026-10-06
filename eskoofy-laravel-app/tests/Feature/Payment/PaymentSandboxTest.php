<?php

namespace Tests\Feature\Payment;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\User;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentSandboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PaymentGatewaySeeder::class);
    }

    private function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->givePermissionTo(Permission::findOrCreate('manage_school_settings', 'web'));

        return $user;
    }

    private function demoPayment(User $user): Payment
    {
        $this->actingAs($user)->get(route('payments.sandbox'))->assertRedirect();

        return Payment::firstOrFail();
    }

    #[Test]
    public function guests_cannot_open_the_sandbox(): void
    {
        $this->get(route('payments.sandbox'))->assertStatus(401);
    }

    #[Test]
    public function it_creates_a_demo_payment_and_renders_the_sandbox_page(): void
    {
        $admin = $this->admin();

        $payment = $this->demoPayment($admin);

        $this->assertSame(PaymentGateway::GATEWAY_TEST_GATEWAY, $payment->payment_method);
        $this->assertSame(Payment::STATUS_PENDING, $payment->payment_status);

        $this->actingAs($admin)
            ->get(route('payments.sandbox', ['payment' => $payment->id]))
            ->assertOk()
            ->assertSee('Test / Sandbox')
            ->assertSee('100.00')
            ->assertSee($payment->invoice_number)
            ->assertSee('Simulate success')
            ->assertSee('Simulate failure')
            ->assertSee('Simulate cancellation')
            ->assertSee('free test payment');
    }

    #[Test]
    public function simulating_success_completes_the_payment(): void
    {
        $admin = $this->admin();
        $payment = $this->demoPayment($admin);

        $this->actingAs($admin)
            ->post(route('payments.sandbox.simulate', ['payment' => $payment->id]), [
                'simulate' => 'success',
            ])
            ->assertRedirect(route('payments.sandbox', ['payment' => $payment->id]))
            ->assertSessionHas('status');

        $payment->refresh();

        $this->assertSame(Payment::STATUS_COMPLETED, $payment->payment_status);
        $this->assertSame('TEST-'.$payment->invoice_number, $payment->transaction_id);
        $this->assertSame(0.0, (float) $payment->due_amount);
    }

    #[Test]
    public function simulating_failure_marks_the_payment_failed(): void
    {
        $admin = $this->admin();
        $payment = $this->demoPayment($admin);

        $this->actingAs($admin)
            ->post(route('payments.sandbox.simulate', ['payment' => $payment->id]), [
                'simulate' => 'failure',
            ])
            ->assertRedirect(route('payments.sandbox', ['payment' => $payment->id]))
            ->assertSessionHas('error');

        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->payment_status);
    }

    #[Test]
    public function simulating_a_cancellation_marks_the_payment_cancelled(): void
    {
        $admin = $this->admin();
        $payment = $this->demoPayment($admin);

        $this->actingAs($admin)
            ->post(route('payments.sandbox.simulate', ['payment' => $payment->id]), [
                'simulate' => 'cancel',
            ])
            ->assertRedirect(route('payments.sandbox', ['payment' => $payment->id]))
            ->assertSessionHas('error');

        $this->assertSame(Payment::STATUS_CANCELLED, $payment->fresh()->payment_status);
    }

    #[Test]
    public function it_rejects_an_unknown_simulation_outcome(): void
    {
        $admin = $this->admin();
        $payment = $this->demoPayment($admin);

        $this->actingAs($admin)
            ->post(route('payments.sandbox.simulate', ['payment' => $payment->id]), [
                'simulate' => 'refund',
            ])
            ->assertStatus(422);

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->payment_status);
    }

    #[Test]
    public function it_refuses_payments_that_are_not_on_the_test_gateway(): void
    {
        $admin = $this->admin();
        $payment = Payment::factory()->create([
            'payment_method' => 'bkash',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('payments.sandbox', ['payment' => $payment->id]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('payments.sandbox.simulate', ['payment' => $payment->id]), [
                'simulate' => 'success',
            ])
            ->assertForbidden();

        $this->assertSame(Payment::STATUS_COMPLETED, $payment->fresh()->payment_status);
    }

    #[Test]
    public function the_seeded_test_gateway_is_active_and_configured(): void
    {
        $gateway = PaymentGateway::where('code', PaymentGateway::GATEWAY_TEST_GATEWAY)->firstOrFail();

        $this->assertTrue($gateway->is_active);
        $this->assertTrue($gateway->is_online);
        $this->assertTrue($gateway->is_configured);
        $this->assertNull($gateway->api_key);
        $this->assertTrue($gateway->test_mode);
    }

    #[Test]
    public function the_diagnostics_table_lists_every_gateway_with_its_adapter(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard.payment-gateways.index'))
            ->assertOk()
            ->assertSee('Gateway diagnostics')
            ->assertSee('TestGatewayAdapter')
            ->assertSee('BkashGatewayAdapter')
            ->assertSee('GenericHostedGatewayAdapter')
            ->assertSee('Run test payment')
            ->assertSee(route('payments.sandbox'));
    }
}
