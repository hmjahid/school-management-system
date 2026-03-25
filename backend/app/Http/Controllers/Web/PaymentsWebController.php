<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Student;
use App\Models\WebsiteContent;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentsWebController extends Controller
{
    public function index(): View
    {
        $content = WebsiteContent::getContent('payments');

        $feeRows = Fee::query()
            ->where('status', Fee::STATUS_ACTIVE)
            ->orderBy('name')
            ->limit(40)
            ->get();

        $gateways = PaymentGateway::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $students = collect();
        $feePayments = collect();
        if (auth()->check()) {
            $studentIds = $this->studentIdsForUser(auth()->user());
            if ($studentIds->isNotEmpty()) {
                $students = Student::query()->whereIn('id', $studentIds)->orderBy('first_name')->orderBy('last_name')->get();
                $feePayments = FeePayment::query()
                    ->whereIn('student_id', $studentIds)
                    ->orderByDesc('payment_date')
                    ->orderByDesc('id')
                    ->limit(25)
                    ->get();
            }
        }

        return view('site.payments', compact('content', 'feeRows', 'gateways', 'feePayments', 'students'));
    }

    public function initiate(Request $request, PaymentService $paymentService): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasAnyRole(['student', 'parent']), 403);

        $studentIds = $this->studentIdsForUser($user);
        abort_unless($studentIds->isNotEmpty(), 403);

        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'fee_id' => ['required', 'integer', 'exists:fees,id'],
            'gateway' => ['required', 'string', 'exists:payment_gateways,code'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        abort_unless($studentIds->contains((int) $data['student_id']), 403);

        $gateway = PaymentGateway::query()->where('code', $data['gateway'])->where('is_active', true)->firstOrFail();
        $fee = Fee::query()->where('id', $data['fee_id'])->firstOrFail();

        $payment = DB::transaction(function () use ($data, $fee, $gateway, $user) {
            $feePayment = FeePayment::create([
                'student_id' => (int) $data['student_id'],
                'fee_id' => (int) $fee->id,
                'amount' => (float) $data['amount'],
                'paid_amount' => 0,
                'discount_amount' => 0,
                'fine_amount' => 0,
                'balance' => (float) $data['amount'],
                'payment_date' => now(),
                'payment_method' => FeePayment::METHOD_ONLINE_PAYMENT,
                'status' => FeePayment::STATUS_PENDING,
                'metadata' => [
                    'gateway' => $gateway->code,
                ],
                'created_by' => $user->id,
            ]);

            return Payment::create([
                'paymentable_type' => Payment::PURPOSE_TUITION,
                'paymentable_id' => $feePayment->id,
                'amount' => (float) $data['amount'],
                'paid_amount' => 0,
                'due_amount' => (float) $data['amount'],
                'discount_amount' => 0,
                'fine_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => (float) $data['amount'],
                'payment_method' => $gateway->code,
                'payment_status' => Payment::STATUS_PENDING,
                'payment_details' => [
                    'description' => "Fee payment: {$fee->name}",
                    'return_url' => route('site.payments.status', ['payment' => '__PAYMENT__']),
                    'cancel_url' => route('site.payments.status', ['payment' => '__PAYMENT__']),
                ],
                'metadata' => [
                    'fee_payment_id' => $feePayment->id,
                    'student_id' => (int) $data['student_id'],
                    'fee_id' => (int) $fee->id,
                ],
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });

        // Now that we have a Payment ID, patch URLs to concrete status page.
        $payment->update([
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'return_url' => route('site.payments.status', ['payment' => $payment->id]),
                'cancel_url' => route('site.payments.status', ['payment' => $payment->id]),
            ]),
        ]);

        $init = $paymentService->initializePayment($payment, $gateway->code, [
            'return_url' => route('site.payments.status', ['payment' => $payment->id]),
            'cancel_url' => route('site.payments.status', ['payment' => $payment->id]),
        ]);

        if (! empty($init['redirect_url'])) {
            return redirect()->away($init['redirect_url']);
        }

        return redirect()->route('site.payments.status', ['payment' => $payment->id]);
    }

    public function status(Request $request, Payment $payment, PaymentService $paymentService): View
    {
        abort_unless((int) $payment->created_by === (int) $request->user()->id, 403);

        if (! in_array($payment->payment_status, [Payment::STATUS_COMPLETED, Payment::STATUS_REFUNDED], true)) {
            try {
                $paymentService->verifyPayment($payment);
                $payment->refresh();
            } catch (\Throwable) {
                // Show current state even if verification fails.
            }
        }

        $feePayment = null;
        if (! empty($payment->metadata['fee_payment_id'])) {
            $feePayment = FeePayment::query()->find($payment->metadata['fee_payment_id']);
        }

        return view('site.payment-status', compact('payment', 'feePayment'));
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function studentIdsForUser(\App\Models\User $user): \Illuminate\Support\Collection
    {
        if ($user->hasRole('student')) {
            $id = Student::query()->where('user_id', $user->id)->value('id');

            return $id ? collect([$id]) : collect();
        }

        if ($user->hasRole('parent')) {
            $guardian = Guardian::query()->where('user_id', $user->id)->first();
            if (! $guardian) {
                return collect();
            }

            return $guardian->students->pluck('id');
        }

        return collect();
    }
}
