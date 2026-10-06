<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\Payment\GatewayAdapterFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentSandboxController extends Controller
{
    public function show(Request $request, ?Payment $payment = null): View|RedirectResponse
    {
        if (! $payment) {
            return redirect()->route('payments.sandbox', ['payment' => $this->demoPayment($request)]);
        }

        $this->guard($request, $payment);

        return view('payments.sandbox', [
            'payment' => $payment,
            'gateway' => $this->gateway(),
        ]);
    }

    public function simulate(Request $request, Payment $payment): RedirectResponse
    {
        $this->guard($request, $payment);

        $validated = $request->validate([
            'simulate' => ['required', 'string', 'in:success,failure,cancel'],
        ]);

        $payment = GatewayAdapterFactory::make(PaymentGateway::GATEWAY_TEST_GATEWAY)
            ->processCallback([
                'simulate' => $validated['simulate'],
                'payment_id' => $payment->id,
            ], $this->gateway());

        $flash = $validated['simulate'] === 'success' ? 'status' : 'error';
        $message = match ($validated['simulate']) {
            'success' => __('Test payment completed. No money moved.'),
            'failure' => __('Test payment marked as failed.'),
            'cancel' => __('Test payment cancelled.'),
        };

        return redirect()->to($this->destination($payment))->with($flash, $message);
    }

    protected function guard(Request $request, Payment $payment): void
    {
        $this->authorize('view', $payment);

        abort_unless(
            $payment->payment_method === PaymentGateway::GATEWAY_TEST_GATEWAY,
            403,
            'This payment is not using the test gateway.'
        );
    }

    protected function destination(Payment $payment): string
    {
        $details = $payment->payment_details ?? [];
        $key = $payment->payment_status === Payment::STATUS_COMPLETED ? 'return_url' : 'cancel_url';
        $url = (string) ($details[$key] ?? $details['return_url'] ?? '');

        if ($url === '' || str_contains($url, '__PAYMENT__')) {
            return route('payments.sandbox', ['payment' => $payment->id]);
        }

        return $url;
    }

    protected function demoPayment(Request $request): Payment
    {
        return Payment::create([
            'paymentable_type' => Payment::PURPOSE_OTHER,
            'paymentable_id' => 0,
            'amount' => 100,
            'paid_amount' => 0,
            'due_amount' => 100,
            'discount_amount' => 0,
            'fine_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 100,
            'payment_method' => PaymentGateway::GATEWAY_TEST_GATEWAY,
            'payment_status' => Payment::STATUS_PENDING,
            'payment_details' => ['description' => 'Sandbox test payment'],
            'metadata' => ['sandbox' => true],
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);
    }

    protected function gateway(): PaymentGateway
    {
        return PaymentGateway::where('code', PaymentGateway::GATEWAY_TEST_GATEWAY)->firstOrFail();
    }
}
