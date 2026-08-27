<?php

namespace App\Http\Controllers;

use App\Http\Resources\RefundResource;
use App\Jobs\ProcessRefundJob;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:'.$this->refundService->getRefundableAmount($payment),
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

        if (! $result['success']) {
            abort(422, $result['message']);
        }

        return (new RefundResource($result['refund']))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified refund.
     *
     * @return \App\Http\Resources\RefundResource
     */
    public function show(Refund $refund)
    {
        $user = request()->user();

        if (! $user->hasRole('admin') && $refund->user_id !== $user->id) {
            abort(403, 'You are not authorized to view this refund.');
        }

        $refund->load(['payment', 'user', 'processor']);

        return new RefundResource($refund);
    }

    /**
     * Process a pending refund.
     *
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

        // Hand off to a queue job; in testing QUEUE_CONNECTION=sync so the
        // refund is completed before the response is returned.
        ProcessRefundJob::dispatch($refund, $validated['transaction_id'] ?? null);

        return new RefundResource($refund->fresh());
    }

    /**
     * Cancel a pending refund.
     *
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

        $idempotencyKey = $this->webhookIdempotencyKey($gateway, $payload);
        if (Cache::has($idempotencyKey)) {
            return response()->json(['success' => true, 'idempotent' => true]);
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
                        ?? ('R-'.strtoupper(uniqid())),
                    'processed_at' => now(),
                ]);
            }
        }

        Cache::put($idempotencyKey, true, now()->addDay());

        return response()->json(['success' => true]);
    }

    /**
     * Build a cache key used to de-duplicate identical webhook deliveries.
     */
    protected function webhookIdempotencyKey(string $gateway, array $payload): string
    {
        $signature = $this->getWebhookSignatureValue($gateway, $payload);
        $txn = $payload['paymentID'] ?? $payload['paymentRefId'] ?? $payload['transaction_id'] ?? '';
        $refundId = $payload['refundTrxID'] ?? $payload['refund_id'] ?? '';

        return 'webhook:refund:'.$gateway.':'.md5($signature.':'.$txn.':'.$refundId.':'.json_encode($payload));
    }

    /**
     * Verify the webhook signature for the given gateway.
     */
    protected function verifyWebhookSignature(string $gateway, array $payload): bool
    {
        if (! in_array($gateway, ['bkash', 'nagad', 'rocket'], true)) {
            return false;
        }

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
        $header = match ($gateway) {
            'nagad' => 'X-Nagad-Signature',
            'rocket' => 'X-Rocket-Signature',
            default => 'X-Webhook-Signature',
        };

        return (string) request()->header($header);
    }

    /**
     * Compute the expected webhook signature for the given gateway.
     *
     * Uses the server-side webhook secret from config/payment.php and the
     * raw request body. Never echoes the client-supplied signature.
     */
    protected function computeWebhookSignature(string $gateway, array $payload): string
    {
        $secret = config("payment.gateways.{$gateway}.webhook_secret");

        if (empty($secret)) {
            // Without a configured secret we cannot verify the signature.
            return '';
        }

        $body = request()->getContent();

        if ($body === '' && $payload !== []) {
            $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        }

        return hash_hmac('sha256', (string) $body, $secret);
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
