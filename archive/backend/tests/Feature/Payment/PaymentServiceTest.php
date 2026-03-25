<?php

namespace Tests\Feature\Payment;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = app(PaymentService::class);
        
        // Seed payment gateways
        $this->seed(\Database\Seeders\PaymentGatewaySeeder::class);
    }

    /** @test */
    public function it_can_initialize_offline_payment()
    {
        $payment = Payment::factory()->create([
            'total_amount' => 1000,
            'payment_status' => Payment::STATUS_PENDING,
        ]);

        $result = $this->paymentService->initializePayment($payment, 'cash');

        $this->assertTrue($result['success']);
        $this->assertEquals('cash', $result['gateway']);
        $this->assertArrayHasKey('offline_instructions', $result);
    }

    /** @test */
    public function it_throws_exception_for_invalid_gateway()
    {
        $payment = Payment::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment gateway is not active');
        
        $this->paymentService->initializePayment($payment, 'invalid_gateway');
    }

    /** @test */
    public function it_can_process_bkash_callback()
    {
        $payment = Payment::factory()->create([
            'payment_method' => 'bkash',
            'payment_status' => Payment::STATUS_PENDING,
            'payment_details' => [
                'bkash_payment_id' => 'TRX12345',
                'bkash_token' => 'test_token',
            ],
        ]);

        $callbackData = [
            'paymentID' => 'TRX12345',
            'merchantInvoiceNumber' => $payment->invoice_number,
            'transactionStatus' => 'Completed',
            'amount' => $payment->total_amount,
            'currency' => 'BDT',
        ];

        $result = $this->paymentService->processCallback('bkash', $callbackData);

        $this->assertEquals(Payment::STATUS_COMPLETED, $result->payment_status);
        $this->assertEquals($payment->total_amount, $result->paid_amount);
    }

    /** @test */
    public function it_can_verify_payment_status()
    {
        $payment = Payment::factory()->create([
            'payment_method' => 'bkash',
            'payment_status' => Payment::STATUS_PENDING,
            'payment_details' => [
                'bkash_payment_id' => 'TRX12345',
            ],
        ]);

        // In a real test, you would mock the HTTP client to return a specific response
        // For this example, we'll just test that the method runs without errors
        $result = $this->paymentService->verifyPayment($payment);
        
        $this->assertInstanceOf(Payment::class, $result);
    }

    /** @test */
    public function it_can_handle_webhook_events()
    {
        $payment = Payment::factory()->create([
            'payment_method' => 'bkash',
            'payment_status' => Payment::STATUS_PENDING,
            'payment_details' => [
                'bkash_payment_id' => 'TRX12345',
            ],
        ]);

        $webhookData = [
            'paymentID' => 'TRX12345',
            'status' => 'COMPLETED',
            'amount' => $payment->total_amount,
            'currency' => 'BDT',
        ];

        $result = $this->paymentService->processCallback('bkash', $webhookData);

        $this->assertEquals(Payment::STATUS_COMPLETED, $result->payment_status);
    }
}
