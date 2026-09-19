<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function gateway(string $code = 'bkash'): PaymentGateway
    {
        return PaymentGateway::create([
            'name' => ucfirst($code),
            'code' => $code,
            'type' => PaymentGateway::TYPE_MOBILE_FINANCIAL_SERVICE,
            'is_active' => true,
            'is_online' => true,
            'has_api' => true,
            'test_mode' => true,
            'sandbox_url' => "https://sandbox.{$code}.com",
            'live_url' => "https://live.{$code}.com",
            'api_key' => 'app_key',
            'api_secret' => 'app_secret',
            'api_username' => 'user',
            'api_password' => 'pass',
            'callback_url' => 'https://example.com/callback',
            'currency' => 'BDT',
        ]);
    }

    private function payment(string $method = 'bkash', array $details = []): Payment
    {
        return Payment::create(array_merge([
            'invoice_number' => 'INV'.uniqid(),
            'amount' => 1000.00,
            'total_amount' => 1000.00,
            'paid_amount' => 0,
            'due_amount' => 1000.00,
            'payment_method' => $method,
            'payment_status' => Payment::STATUS_PENDING,
            'currency' => 'BDT',
            'paymentable_type' => 'App\Models\Student',
            'paymentable_id' => 1,
            'payment_details' => [],
        ], $details));
    }

    private function postWebhook(string $gateway, array $payload, ?string $signature): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload);

        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($signature !== null) {
            $header = match ($gateway) {
                'nagad' => 'HTTP_X_NAGAD_SIGNATURE',
                'rocket' => 'HTTP_X_ROCKET_SIGNATURE',
                default => 'HTTP_X_BKASH_SIGNATURE',
            };
            $server[$header] = $signature;
        }

        return $this->call('POST', "/api/v1/payments/webhook/{$gateway}", [], [], [], $server, $body);
    }

    #[Test]
    public function valid_signed_bkash_webhook_processes_and_completes_payment(): void
    {
        Event::fake();
        Http::fake([
            '*/checkout/execute' => Http::response([
                'transactionStatus' => 'Completed',
                'trxID' => 'TRX987',
            ], 200),
            '*' => Http::response(['statusCode' => '0000'], 200),
        ]);

        $gateway = $this->gateway('bkash');
        $payment = $this->payment('bkash', [
            'payment_details' => ['bkash_payment_id' => 'BKPAY123', 'bkash_token' => 'tok'],
        ]);

        $payload = ['merchantInvoiceNumber' => $payment->invoice_number, 'paymentID' => 'BKPAY123'];
        $signature = hash_hmac('sha256', json_encode($payload), $gateway->api_secret);

        $response = $this->postWebhook('bkash', $payload, $signature);

        $response->assertStatus(200);
        $this->assertEquals(Payment::STATUS_COMPLETED, $payment->fresh()->payment_status);
    }

    #[Test]
    public function missing_signature_is_rejected_and_payment_unchanged(): void
    {
        Http::fake();
        $gateway = $this->gateway('bkash');
        $payment = $this->payment('bkash');

        $response = $this->postWebhook('bkash', [
            'merchantInvoiceNumber' => $payment->invoice_number,
            'paymentID' => 'BKPAY123',
        ], null);

        $response->assertStatus(403);
        $this->assertEquals(Payment::STATUS_PENDING, $payment->fresh()->payment_status);
        Http::assertNothingSent();
    }

    #[Test]
    public function invalid_signature_is_rejected_and_payment_unchanged(): void
    {
        Http::fake();
        $gateway = $this->gateway('bkash');
        $payment = $this->payment('bkash');

        $response = $this->postWebhook('bkash', [
            'merchantInvoiceNumber' => $payment->invoice_number,
            'paymentID' => 'BKPAY123',
        ], 'forged-signature');

        $response->assertStatus(403);
        $this->assertEquals(Payment::STATUS_PENDING, $payment->fresh()->payment_status);
        Http::assertNothingSent();
    }

    #[Test]
    public function valid_signed_nagad_webhook_processes_and_completes_payment(): void
    {
        Event::fake();
        Http::fake([
            '*/verify/payment/*' => Http::response(['status' => 'Success', 'paymentId' => 'NAGTXN123'], 200),
            '*' => Http::response(['status' => 'Success'], 200),
        ]);

        $gateway = $this->gateway('nagad');
        $payment = $this->payment('nagad', [
            'payment_details' => ['nagad_payment_id' => 'NAGREF123', 'nagad_order_id' => 'NAGORD123'],
        ]);

        $payload = ['orderId' => 'NAGORD123', 'paymentRefId' => 'NAGREF123'];
        $signature = hash_hmac('sha256', json_encode($payload), $gateway->api_secret);

        $response = $this->postWebhook('nagad', $payload, $signature);

        $response->assertStatus(200);
        $this->assertEquals(Payment::STATUS_COMPLETED, $payment->fresh()->payment_status);
    }

    #[Test]
    public function invalid_nagad_signature_is_rejected_and_payment_unchanged(): void
    {
        Http::fake();
        $gateway = $this->gateway('nagad');
        $payment = $this->payment('nagad');

        $response = $this->postWebhook('nagad', [
            'orderId' => 'NAGORD123',
            'paymentRefId' => 'NAGREF123',
        ], 'forged');

        $response->assertStatus(403);
        $this->assertEquals(Payment::STATUS_PENDING, $payment->fresh()->payment_status);
        Http::assertNothingSent();
    }

    #[Test]
    public function rocket_webhook_requires_valid_signature(): void
    {
        Http::fake();
        $gateway = $this->gateway('rocket');
        $payment = $this->payment('rocket');

        $response = $this->postWebhook('rocket', ['foo' => 'bar'], null);

        $response->assertStatus(403);
        $this->assertEquals(Payment::STATUS_PENDING, $payment->fresh()->payment_status);
        Http::assertNothingSent();
    }
}
