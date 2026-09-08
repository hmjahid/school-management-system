<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FeePayment;
use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardFeePaymentController extends Controller
{
    public function index(Request $request): View
    {
        $query = FeePayment::with(['student.user', 'fee', 'creator']);

        $method = $request->string('method')->toString() ?: $request->string('payment_method')->toString();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('student.user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($method) {
            $this->applyMethodFilter($query, $method);
        }

        $payments = $query->latest()->paginate(20)->withQueryString();

        $methods = FeePayment::getPaymentMethods();
        foreach (PaymentGateway::query()->where('is_active', true)->orderBy('name')->pluck('name', 'code') as $code => $name) {
            $methods[$code] = $name;
        }

        return view('dashboard.fee-payments.index', compact('payments', 'methods'));
    }

    /**
     * Filter payments by method. Online gateway codes (bKash/Nagad/Rocket) are
     * stored on the linked Payment record and in FeePayment metadata, so those
     * match either the column value or the metadata gateway.
     */
    protected function applyMethodFilter(Builder $query, string $method): Builder
    {
        if (in_array($method, array_keys(FeePayment::getPaymentMethods()), true)) {
            return $query->where('payment_method', $method);
        }

        return $query->where(function (Builder $q) use ($method) {
            $q->where('payment_method', $method)
                ->orWhere(function (Builder $q) use ($method) {
                    $q->where('payment_method', FeePayment::METHOD_ONLINE_PAYMENT)
                        ->where('metadata->gateway', $method);
                });
        });
    }

    public function show(FeePayment $feePayment): View
    {
        $feePayment->load(['student.user', 'fee', 'creator', 'approver']);

        return view('dashboard.fee-payments.show', ['payment' => $feePayment]);
    }

    public function approve(FeePayment $feePayment): RedirectResponse
    {
        $feePayment->update([
            'status' => 'paid',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('status', __('Payment approved.'));
    }

    public function cancel(FeePayment $feePayment): RedirectResponse
    {
        $feePayment->update(['status' => 'cancelled']);

        return back()->with('status', __('Payment cancelled.'));
    }
}
