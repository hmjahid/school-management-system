<?php

namespace Tests\Unit\Services;

use App\Events\PaymentProcessed;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\Payment\GatewayAdapterFactory;
use App\Services\Payment\TestGatewayAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestGatewayAdapterTest extends TestCase
{
    use RefreshDatabase;

    private function gateway(array $attributes = []): PaymentGateway
    {
        return PaymentGateway::create(array_merge([
            'name' => 'Test / Sandbox',
            'code' => PaymentGateway::GATEWAY_TEST_GATEWAY,
            'type' => PaymentGateway::TYPE_ONLINE_PAYMENT,
            'is_active' => true,
            'is_online' => true,
            'has_api' => false,
            'test_mode' => true,
            'currency' => 'BDT',
        ], $attributes));
    }

    private function payment(array $attributes = []): Payment
    {
        return Payment::create(array_merge([
            'paymentable_type' => Payment::PURPOSE_TUITION,
            'paymentable_id' => 1,
            'invoice_number' => 'INV-000456',
            'amount' => 100,
            'paid_amount' => 0,
            'due_amount' => 100,
            'total_amount' => 100,
            'payment_method' => PaymentGateway::GATEWAY_TEST_GATEWAY,
            'payment_status' => Payment::STATUS_PENDING,
        ], $attributes));
    }

    #[Test]
    public function it_resolves_the_test_gateway_from_the_factory(): void
    {
        $adapter = GatewayAdapterFactory::make(PaymentGateway::GATEWAY_TEST_GATEWAY);

        $this->assertInstanceOf(TestGatewayAdapter::class, $adapter);
    }

    #[Test]
    public function it_initializes_with_a_local_sandbox_redirect_and_no_external_call(): void
    {
        Http::fake();

        $payment = $this->payment();

        $result = (new TestGatewayAdapter)->initialize($payment, $this->gateway());

        $this->assertTrue($result['success']);
        $this->assertSame(PaymentGateway::GATEWAY_TEST_GATEWAY, $result['gateway']);
        $this->assertSame(route('payments.sandbox', ['payment' => $payment->id]), $result['redirect_url']);
        $this->assertSame('TEST-INV-000456', $result['transaction_id']);
        $this->assertSame($result['redirect_url'], $payment->fresh()->payment_details['gateway_checkout_url']);
        Http::assertNothingSent();
    }

    #[Test]
    public function it_marks_the_payment_paid_on_a_successful_simulation(): void
    {
        Event::fake([PaymentProcessed::class]);
        Http::fake();

        $gateway = $this->gateway();
        $payment = $this->payment();

        $result = (new TestGatewayAdapter)->processCallback([
            'simulate' => TestGatewayAdapter::SIMULATE_SUCCESS,
            'payment_id' => $payment->id,
        ], $gateway);

        $this->assertSame(Payment::STATUS_COMPLETED, $result->payment_status);
        $this->assertSame(100.0, (float) $result->paid_amount);
        $this->assertSame(0.0, (float) $result->due_amount);
        $this->assertSame('TEST-INV-000456', $result->transaction_id);
        $this->assertNotNull($result->payment_date);
        $this->assertSame('COMPLETED', $result->payment_details['gateway_status']);
        Event::assertDispatchedTimes(PaymentProcessed::class, 1);
        Http::assertNothingSent();
    }

    #[Test]
    public function it_marks_the_payment_failed_on_a_failed_simulation(): void
    {
        $gateway = $this->gateway();
        $payment = $this->payment();

        $result = (new TestGatewayAdapter)->processCallback([
            'simulate' => TestGatewayAdapter::SIMULATE_FAILURE,
            'payment_id' => $payment->id,
        ], $gateway);

        $this->assertSame(Payment::STATUS_FAILED, $result->payment_status);
        $this->assertSame(0.0, (float) $result->paid_amount);
        $this->assertSame('FAILED', $result->payment_details['gateway_status']);
    }

    #[Test]
    public function it_marks_the_payment_cancelled_on_a_cancelled_simulation(): void
    {
        $gateway = $this->gateway();
        $payment = $this->payment();

        $result = (new TestGatewayAdapter)->processCallback([
            'simulate' => TestGatewayAdapter::SIMULATE_CANCEL,
            'payment_id' => $payment->id,
        ], $gateway);

        $this->assertSame(Payment::STATUS_CANCELLED, $result->payment_status);
        $this->assertSame('CANCELLED', $result->payment_details['gateway_status']);
    }

    #[Test]
    public function it_resolves_the_payment_by_invoice_number_when_no_id_is_given(): void
    {
        $gateway = $this->gateway();
        $payment = $this->payment();

        $result = (new TestGatewayAdapter)->processCallback([
            'simulate' => TestGatewayAdapter::SIMULATE_SUCCESS,
            'invoice_number' => $payment->invoice_number,
        ], $gateway);

        $this->assertSame($payment->id, $result->id);
        $this->assertSame(Payment::STATUS_COMPLETED, $result->payment_status);
    }

    #[Test]
    public function verifying_a_completed_payment_is_a_no_op(): void
    {
        Event::fake([PaymentProcessed::class]);

        $gateway = $this->gateway();
        $payment = $this->payment();
        $adapter = new TestGatewayAdapter;

        $adapter->processCallback([
            'simulate' => TestGatewayAdapter::SIMULATE_SUCCESS,
            'payment_id' => $payment->id,
        ], $gateway);

        $verified = $adapter->verifyPayment($payment->fresh(), $gateway);

        $this->assertSame(Payment::STATUS_COMPLETED, $verified->payment_status);
        $this->assertSame('TEST-INV-000456', $verified->transaction_id);
        Event::assertDispatchedTimes(PaymentProcessed::class, 1);
    }

    #[Test]
    public function verifying_replays_the_recorded_simulation(): void
    {
        $gateway = $this->gateway();
        $payment = $this->payment();
        $adapter = new TestGatewayAdapter;

        $payment->update([
            'payment_details' => ['test_simulation' => TestGatewayAdapter::SIMULATE_FAILURE],
        ]);

        $verified = $adapter->verifyPayment($payment->fresh(), $gateway);

        $this->assertSame(Payment::STATUS_FAILED, $verified->payment_status);
    }

    #[Test]
    public function it_reports_refunds_unsupported(): void
    {
        $result = (new TestGatewayAdapter)->refund($this->gateway(), 'TEST-INV-000456', 100.0, 'Not supported');

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['message']);
    }

    #[Test]
    public function its_webhook_signature_check_is_fail_closed(): void
    {
        $adapter = new TestGatewayAdapter;
        $request = Request::create('/payments/webhook/test_gateway', 'POST', [], [], [], [], '{"status":"COMPLETED"}');

        $this->assertFalse($adapter->verifyWebhookSignature($request, $this->gateway()));
    }
}
