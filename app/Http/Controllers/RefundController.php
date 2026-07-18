<?php

namespace App\Http\Controllers;

use App\Http\Resources\RefundResource;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RefundController extends Controller
{
    /**
     * The refund service instance.
     *
     * @var \App\Services\RefundService
     */
    protected $refundService;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\RefundService  $refundService
     * @return void
     */
    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
        
        // Apply policy for all methods
        $this->authorizeResource(Refund::class, 'refund');
    }

    /**
     * Display a listing of refunds.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(['pending', 'processing', 'completed', 'failed', 'cancelled'])],
            'payment_id' => 'nullable|exists:payments,id',
            'user_id' => 'nullable|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|string|in:created_at,amount,status',
            'sort_order' => 'nullable|string|in:asc,desc',
        ]);

        $refunds = $this->refundService->listRefunds([
            'status' => $validated['status'] ?? null,
            'payment_id' => $validated['payment_id'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'per_page' => $validated['per_page'] ?? 15,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_order' => $validated['sort_order'] ?? 'desc',
        ]);

        return RefundResource::collection($refunds);
    }

    /**
     * Store a newly created refund in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $this->refundService->getRefundableAmount($payment),
            'reason' => 'required|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        $refund = DB::transaction(function () use ($payment, $validated) {
            $result = $this->refundService->initiateRefund(
                $payment,
                $validated['amount'],
                $validated['reason'],
                $request->user(),
                $validated['metadata'] ?? []
            );

            if (!$result['success']) {
                abort(400, $result['message']);
            }

            return $result['refund'];
        });

        return (new RefundResource($refund))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified refund.
     *
     * @param  \App\Models\Refund  $refund
     * @return \App\Http\Resources\RefundResource
     */
    public function show(Refund $refund)
    {
        $refund->load(['payment', 'user', 'processor']);
        return new RefundResource($refund);
    }

    /**
     * Process a pending refund.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Refund  $refund
     * @return \App\Http\Resources\RefundResource
     */
    public function process(Request $request, Refund $refund)
    {
        $this->authorize('process', $refund);

        if ($refund->status !== 'pending') {
            abort(400, 'Only pending refunds can be processed');
        }

        $validated = $request->validate([
            'transaction_id' => 'nullable|string|max:255',
        ]);

        // In a real application, this would involve calling the payment gateway
        // For this example, we'll just mark it as completed
        $refund->update([
            'status' => 'processing',
        ]);

        // Simulate processing delay
        // In a real application, this would be handled by a queue job
        sleep(2);

        $refund->update([
            'status' => 'completed',
            'transaction_id' => $validated['transaction_id'] ?? ('R-' . strtoupper(uniqid())),
            'processed_at' => now(),
        ]);

        return new RefundResource($refund->fresh());
    }

    /**
     * Cancel a pending refund.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Refund  $refund
     * @return \App\Http\Resources\RefundResource
     */
    public function cancel(Request $request, Refund $refund)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $this->refundService->cancelRefund($refund, $validated['reason']);

        return new RefundResource($refund->fresh());
    }

    /**
     * Get refund statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $this->authorize('viewAny', Refund::class);

        $stats = [
            'total_refunded' => Refund::completed()->sum('amount'),
            'pending_count' => Refund::pending()->count(),
            'completed_count' => Refund::completed()->count(),
            'failed_count' => Refund::failed()->count(),
            'refunds_by_month' => $this->getRefundsByMonth(),
            'refunds_by_status' => $this->getRefundsByStatus(),
            'refunds_by_payment_method' => $this->getRefundsByPaymentMethod(),
        ];

        return response()->json($stats);
    }

    /**
     * Get refunds grouped by month.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getRefundsByMonth()
    {
        return Refund::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total_amount')
        )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->month => [
                        'count' => $item->count,
                        'total_amount' => (float) $item->total_amount,
                    ],
                ];
            });
    }

    /**
     * Get refunds grouped by status.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getRefundsByStatus()
    {
        return Refund::select(
            'status',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total_amount')
        )
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->status => [
                        'count' => $item->count,
                        'total_amount' => (float) $item->total_amount,
                    ],
                ];
            });
    }

    /**
     * Get refunds grouped by payment method.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getRefundsByPaymentMethod()
    {
        return Refund::select(
            'payments.payment_method',
            DB::raw('COUNT(refunds.id) as count'),
            DB::raw('SUM(refunds.amount) as total_amount')
        )
            ->join('payments', 'refunds.payment_id', '=', 'payments.id')
            ->groupBy('payments.payment_method')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->payment_method => [
                        'count' => $item->count,
                        'total_amount' => (float) $item->total_amount,
                    ],
                ];
            });
    }
}
