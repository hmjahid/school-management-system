<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FeePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardFeePaymentController extends Controller
{
    public function index(Request $request): View
    {
        $query = FeePayment::with(['student.user', 'fee', 'creator']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('student.user', fn($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($paymentMethod = $request->string('payment_method')->toString()) {
            $query->where('payment_method', $paymentMethod);
        }

        $payments = $query->latest()->paginate(20)->withQueryString();
        return view('dashboard.fee-payments.index', compact('payments'));
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
