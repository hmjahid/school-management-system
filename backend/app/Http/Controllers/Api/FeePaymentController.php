<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeePaymentResource;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeePaymentController extends Controller
{
    /**
     * Display a listing of the fee payments.
     */
    public function index(Request $request)
    {
        $query = FeePayment::with(['student', 'fee', 'creator', 'approver']);

        // Filter by student
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        // Filter by fee
        if ($request->has('fee_id')) {
            $query->where('fee_id', $request->fee_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by date range
        if ($request->has(['start_date', 'end_date'])) {
            $query->whereBetween('payment_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        // Search by invoice number or transaction ID
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('student', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('admission_number', 'like', "%{$search}%");
                  });
            });
        }

        // Order and paginate
        $payments = $query->latest('payment_date')
            ->paginate($request->per_page ?? 15);

        return FeePaymentResource::collection($payments);
    }

    /**
     * Store a newly created payment in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validatePayment($request);
        
        // Start database transaction
        return DB::transaction(function () use ($validated) {
            // Create payment
            $payment = FeePayment::create($validated);
            
            // Update fee balance if needed
            if ($payment->fee) {
                $this->updateFeeBalance($payment);
            }
            
            // TODO: Trigger payment notification
            
            return new FeePaymentResource($payment->load(['student', 'fee', 'creator']));
        });
    }

    /**
     * Display the specified payment.
     */
    public function show(FeePayment $payment)
    {
        return new FeePaymentResource($payment->load([
            'student', 'fee', 'creator', 'approver'
        ]));
    }

    /**
     * Update the specified payment in storage.
     */
    public function update(Request $request, FeePayment $payment)
    {
        $validated = $this->validatePayment($request, $payment->id);
        
        // Start database transaction
        return DB::transaction(function () use ($payment, $validated) {
            // Store old fee_id for balance adjustment
            $oldFeeId = $payment->fee_id;
            
            // Update payment
            $payment->update($validated);
            
            // Update fee balances
            if ($oldFeeId && $oldFeeId != $payment->fee_id) {
                $this->updateFeeBalance($payment, true); // Reverse old fee balance
            }
            
            if ($payment->fee_id) {
                $this->updateFeeBalance($payment); // Update new fee balance
            }
            
            return new FeePaymentResource($payment->load(['student', 'fee', 'creator', 'approver']));
        });
    }

    /**
     * Approve the specified payment.
     */
    public function approve(Request $request, FeePayment $payment)
    {
        if ($payment->status !== FeePayment::STATUS_PENDING) {
            return response()->json([
                'message' => 'Only pending payments can be approved.'
            ], 422);
        }

        $payment->update([
            'status' => FeePayment::STATUS_PAID,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // TODO: Trigger payment approved notification

        return new FeePaymentResource($payment->load(['student', 'fee', 'creator', 'approver']));
    }

    /**
     * Cancel the specified payment.
     */
    public function cancel(Request $request, FeePayment $payment)
    {
        if (!in_array($payment->status, [FeePayment::STATUS_PENDING, FeePayment::STATUS_PAID])) {
            return response()->json([
                'message' => 'Only pending or paid payments can be cancelled.'
            ], 422);
        }

        // Start database transaction
        return DB::transaction(function () use ($payment) {
            // Update payment status
            $payment->update([
                'status' => FeePayment::STATUS_CANCELLED,
                'notes' => $payment->notes . "\nCancelled by " . auth()->user()->name . " on " . now()->toDateTimeString()
            ]);
            
            // Update fee balance if needed
            if ($payment->fee) {
                $this->updateFeeBalance($payment, true); // Reverse the balance
            }
            
            // TODO: Trigger payment cancelled notification
            
            return new FeePaymentResource($payment->load(['student', 'fee', 'creator', 'approver']));
        });
    }

    /**
     * Get payment statuses.
     */
    public function getStatuses()
    {
        return response()->json([
            'data' => FeePayment::getStatuses()
        ]);
    }

    /**
     * Get payment methods.
     */
    public function getPaymentMethods()
    {
        return response()->json([
            'data' => FeePayment::getPaymentMethods()
        ]);
    }

    /**
     * Get student payment summary.
     */
    public function getStudentSummary(Student $student)
    {
        $payments = FeePayment::where('student_id', $student->id)
            ->selectRaw('status, count(*) as count, sum(paid_amount) as total')
            ->groupBy('status')
            ->get();

        $totalPaid = $payments->where('status', FeePayment::STATUS_PAID)->sum('total');
        $totalPending = $payments->where('status', FeePayment::STATUS_PENDING)->sum('total');
        $totalPartial = $payments->where('status', FeePayment::STATUS_PARTIAL)->sum('total');
        $totalRefunded = $payments->where('status', FeePayment::STATUS_REFUNDED)->sum('total');

        $fees = Fee::where(function($query) use ($student) {
                $query->where('class_id', $student->class_id)
                    ->whereNull('student_id')
                    ->where('status', 'active');
                
                if ($student->section_id) {
                    $query->orWhere('section_id', $student->section_id)
                        ->whereNull('student_id')
                        ->where('status', 'active');
                }
                
                $query->orWhere('student_id', $student->id)
                    ->where('status', 'active');
            })
            ->get();

        $totalFees = $fees->sum('amount');
        $totalDiscounts = $fees->sum('discount_amount');
        $totalFines = $fees->sum('fine_amount');

        $outstanding = $totalFees - $totalPaid - $totalDiscounts + $totalFines;

        return response()->json([
            'total_fees' => $totalFees,
            'total_paid' => $totalPaid,
            'total_pending' => $totalPending,
            'total_partial' => $totalPartial,
            'total_refunded' => $totalRefunded,
            'total_discounts' => $totalDiscounts,
            'total_fines' => $totalFines,
            'outstanding_balance' => $outstanding > 0 ? $outstanding : 0,
            'payment_status' => $outstanding <= 0 ? 'Paid' : ($outstanding < $totalFees ? 'Partial' : 'Unpaid'),
        ]);
    }

    /**
     * Validate payment data.
     */
    protected function validatePayment(Request $request, $id = null)
    {
        $rules = [
            'student_id' => ['required', 'exists:students,id'],
            'fee_id' => ['nullable', 'exists:fees,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'fine_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'month' => ['nullable', 'string', 'max:20'],
            'year' => ['nullable', 'integer', 'digits:4'],
            'payment_method' => ['required', 'string', Rule::in(array_keys(FeePayment::getPaymentMethods()))],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'check_number' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', Rule::in(array_keys(FeePayment::getStatuses()))],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];

        // For new payments, generate invoice number if not provided
        if (is_null($id) && !$request->has('invoice_number')) {
            $rules['invoice_number'] = ['nullable', 'string', 'max:50', 'unique:fee_payments,invoice_number'];
        } else {
            $rules['invoice_number'] = ['nullable', 'string', 'max:50', Rule::unique('fee_payments', 'invoice_number')->ignore($id)];
        }

        $validated = $request->validate($rules);

        // Set created_by if not provided
        if (auth()->check() && !isset($validated['created_by'])) {
            $validated['created_by'] = auth()->id();
        }

        // Generate invoice number if not provided
        if (empty($validated['invoice_number'])) {
            $validated['invoice_number'] = FeePayment::generateInvoiceNumber();
        }

        return $validated;
    }

    /**
     * Update fee balance based on payment.
     */
    protected function updateFeeBalance(FeePayment $payment, $reverse = false)
    {
        $fee = $payment->fee;
        if (!$fee) return;

        $amount = $reverse ? -$payment->paid_amount : $payment->paid_amount;
        
        // Update fee's paid amount
        $fee->increment('paid_amount', $amount);
        
        // Recalculate balance
        $fee->update([
            'balance' => max(0, $fee->amount - $fee->paid_amount - $fee->discount_amount + $fee->fine_amount)
        ]);
    }
}
