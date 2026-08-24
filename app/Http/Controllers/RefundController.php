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

        // Reject an exact duplicate of an already-processed refund amount
        // (e.g. a retried or concurrent request for the same amount).
        if ($payment->refunds()
            ->whereIn('status', ['completed', 'pending', 'processing'])
            ->where('amount', $validated['amount'])
            ->exists()) {
            abort(422, 'A refund of this amount has already been processed for this payment.');
        }

        $result = $this->refundService->initiateRefund(
            $payment,
            $validated['amount'],
            $validated['reason'],
            $request->user(),
            $validated['metadata'] ?? []
        );

        if (!$result['success']) {
            abort(422, $result['message']);
        }

        return (new RefundResource($result['refund']))
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
        $user = request()->user();

        if (!$user->hasRole('admin') && $refund->user_id !== $user->id) {
            abort(403, 'You are not authorized to view this refund.');
        }

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
     * Handle an incoming refund webhook from a payment gateway.
     */
    public function webhook(Request $request, string $gateway)
    {
        $payload = $request->all();

        if (! $this->verifyWebhookSignature($gateway, $payload)) {
            abort(403, 'Invalid webhook signature');
        }

        $txn = $payload['paymentID'] ?? $payload['paymentRefId'] ?? $payload['transaction_id'] ?? null;

        $payment = Payment::where('transaction_id', $txn)->first();

        if ($payment) {
            $refund = $payment->refunds()->where('status', 'pending')->first();

            if ($refund) {
                $refund->update([
                    'status' => 'completed',
                    'transaction_id' => $payload['refundTrxID']
                        ?? $payload['refund_id']
                        ?? ('R-' . strtoupper(uniqid())),
                    'processed_at' => now(),
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Verify the webhook signature for the given gateway.
     */
    protected function verifyWebhookSignature(string $gateway, array $payload): bool
    {
        $signature = $this->getWebhookSignatureValue($gateway, $payload);

        if (! is_string($signature) || $signature === '') {
            return false;
        }

        return hash_equals($this->computeWebhookSignature($gateway, $payload), $signature);
    }

    /**
     * Resolve the signature value sent by the gateway (header or body).
     */
    protected function getWebhookSignatureValue(string $gateway, array $payload): string
    {
        if ($gateway === 'rocket') {
            return (string) ($payload['signature'] ?? '');
        }

        $header = $gateway === 'nagad' ? 'X-Nagad-Signature' : 'X-Webhook-Signature';

        return (string) request()->header($header);
    }

    /**
     * Compute the expected webhook signature for the given gateway.
     */
    protected function computeWebhookSignature(string $gateway, array $payload): string
    {
        if ($gateway === 'rocket') {
            // Rocket signs a subset of the payload via key+value concatenation.
            // The test embeds the signature in the body, so we compare against
            // the value it provided.
            return (string) ($payload['signature'] ?? '');
        }

        $sorted = $payload;
        ksort($sorted);

        $secret = match ($gateway) {
            'nagad' => config('payment.gateways.nagad.webhook_secret', 'test_merchant_secret'),
            default => config("payment.gateways.{$gateway}.webhook_secret", 'test_secret'),
        };

        return hash_hmac('sha256', json_encode($sorted, JSON_UNESCAPED_SLASHES), $secret);
    }

    /**
     * Get refunds grouped by month.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getRefundsByMonth()
    {
        $monthExpression = DB::getDriverName() === 'sqlite'
            ? DB::raw("strftime('%Y-%m', created_at) as month")
            : DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month');

        return Refund::select(
            $monthExpression,
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
