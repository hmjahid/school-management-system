<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /**
     * @var PaymentService
     */
    protected $paymentService;

    /**
     * Create a new controller instance.
     *
     * @param PaymentService $paymentService
     * @return void
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
        
        // Apply middleware based on the route
        $this->middleware('auth:sanctum')->except([
            'gateways', 'initiate', 'callback', 'status', 'webhook'
        ]);
        
        // Apply rate limiting to public endpoints
        $this->middleware('throttle:60,1')->only(['gateways', 'initiate', 'status']);
    }

    /**
     * Get list of available payment gateways
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function gateways(Request $request)
    {
        $gateways = PaymentGateway::active()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($gateway) {
                return [
                    'id' => $gateway->id,
                    'name' => $gateway->name,
                    'code' => $gateway->code,
                    'type' => $gateway->type,
                    'type_label' => $gateway->type_label,
                    'is_online' => $gateway->is_online,
                    'logo_url' => $gateway->logo_url,
                    'description' => $gateway->description,
                    'fee_percentage' => $gateway->fee_percentage,
                    'fee_fixed' => $gateway->fee_fixed,
                    'min_amount' => $gateway->min_amount,
                    'max_amount' => $gateway->max_amount,
                    'currency' => $gateway->currency,
                    'supported_currencies' => $gateway->supported_currencies,
                    'is_configured' => $gateway->is_configured,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $gateways,
        ]);
    }

    /**
     * Initiate a new payment
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gateway' => ['required', 'string', 'exists:payment_gateways,code'],
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'paymentable_type' => ['required', 'string', 'in:admission,tuition,exam,library,transport,hostel,other'],
            'paymentable_id' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'return_url' => ['nullable', 'url'],
            'cancel_url' => ['nullable', 'url'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get the payment gateway
        $gateway = PaymentGateway::where('code', $request->gateway)->firstOrFail();
        
        // Check if gateway is active and configured
        if (!$gateway->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'The selected payment gateway is currently unavailable.',
            ], 400);
        }
        
        if ($gateway->is_online && !$gateway->is_configured) {
            return response()->json([
                'success' => false,
                'message' => 'The selected payment gateway is not properly configured.',
            ], 400);
        }
        
        // Check amount against gateway limits
        if ($gateway->min_amount !== null && $request->amount < $gateway->min_amount) {
            return response()->json([
                'success' => false,
                'message' => "Minimum payment amount is {$gateway->currency} {$gateway->min_amount} for {$gateway->name}.",
            ], 400);
        }
        
        if ($gateway->max_amount !== null && $request->amount > $gateway->max_amount) {
            return response()->json([
                'success' => false,
                'message' => "Maximum payment amount is {$gateway->currency} {$gateway->max_amount} for {$gateway->name}.",
            ], 400);
        }
        
        // Check if currency is supported
        if (!in_array($request->currency, $gateway->supported_currencies ?? [$gateway->currency])) {
            return response()->json([
                'success' => false,
                'message' => "The selected currency is not supported by {$gateway->name}.",
            ], 400);
        }
        
        // Calculate fees
        $fee = $gateway->fee_fixed + ($request->amount * $gateway->fee_percentage / 100);
        $totalAmount = $request->amount + $fee;
        
        try {
            // Create a new payment record
            $payment = new Payment([
                'paymentable_type' => $request->paymentable_type,
                'paymentable_id' => $request->paymentable_id,
                'amount' => $request->amount,
                'paid_amount' => 0,
                'due_amount' => $totalAmount,
                'discount_amount' => 0,
                'fine_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'payment_method' => $gateway->code,
                'payment_status' => Payment::STATUS_PENDING,
                'payment_date' => null,
                'reference_number' => null,
                'transaction_id' => null,
                'payment_details' => [
                    'description' => $request->description,
                    'metadata' => $request->metadata,
                    'fee_percentage' => $gateway->fee_percentage,
                    'fee_fixed' => $gateway->fee_fixed,
                    'fee_amount' => $fee,
                    'return_url' => $request->return_url,
                    'cancel_url' => $request->cancel_url,
                ],
                'notes' => $request->description,
                'metadata' => $request->metadata,
            ]);
            
            // Associate with the authenticated user if available
            if (auth()->check()) {
                $payment->created_by = auth()->id();
                $payment->updated_by = auth()->id();
            }
            
            $payment->save();
            
            // Initialize payment with the selected gateway
            $result = $this->paymentService->initializePayment(
                $payment, 
                $gateway->code,
                [
                    'return_url' => $request->return_url,
                    'cancel_url' => $request->cancel_url,
                ]
            );
            
            // Update payment with gateway response
            $payment->update([
                'payment_details' => array_merge($payment->payment_details, [
                    'init_response' => $result,
                ]),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment initiated successfully',
                'data' => [
                    'payment' => new PaymentResource($payment),
                    'gateway' => $result,
                ],
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Payment initiation failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment. Please try again or contact support.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Handle payment callback from gateway
     *
     * @param string $gateway
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function callback($gateway, Request $request)
    {
        try {
            $payment = $this->paymentService->processCallback($gateway, $request->all());
            
            // Redirect to success or failure URL based on payment status
            $redirectUrl = $payment->payment_details['return_url'] ?? null;
            
            if ($payment->payment_status === Payment::STATUS_COMPLETED) {
                $redirectUrl = $payment->payment_details['success_url'] ?? $redirectUrl;
                $message = 'Payment completed successfully';
            } else {
                $redirectUrl = $payment->payment_details['cancel_url'] ?? $redirectUrl;
                $message = 'Payment failed or was cancelled';
            }
            
            // If no redirect URL is set, return a JSON response
            if (!$redirectUrl) {
                return response()->json([
                    'success' => $payment->payment_status === Payment::STATUS_COMPLETED,
                    'message' => $message,
                    'payment' => new PaymentResource($payment),
                ]);
            }
            
            // Redirect to the appropriate URL
            return redirect()->away($redirectUrl . (parse_url($redirectUrl, PHP_URL_QUERY) ? '&' : '?') . http_build_query([
                'payment_id' => $payment->id,
                'status' => $payment->payment_status,
                'transaction_id' => $payment->transaction_id,
                'invoice_number' => $payment->invoice_number,
            ]));
            
        } catch (\Exception $e) {
            \Log::error('Payment callback failed: ' . $e->getMessage(), [
                'gateway' => $gateway,
                'params' => $request->all(),
                'exception' => $e,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed. ' . $e->getMessage(),
            ], 400);
        }
    }
    
    /**
     * Handle payment webhook from gateway
     *
     * @param string $gateway
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function webhook($gateway, Request $request)
    {
        try {
            // Log the webhook request
            \Log::info("Received webhook from {$gateway}", [
                'headers' => $request->headers->all(),
                'payload' => $request->all(),
            ]);
            
            // Process the webhook
            $payment = $this->paymentService->processCallback($gateway, $request->all());
            
            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'payment_id' => $payment->id,
                'status' => $payment->payment_status,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Webhook processing failed: ' . $e->getMessage(), [
                'gateway' => $gateway,
                'payload' => $request->all(),
                'exception' => $e,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed: ' . $e->getMessage(),
            ], 400);
        }
    }
    
    /**
     * Check payment status
     *
     * @param string $paymentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function status($paymentId)
    {
        $payment = Payment::where('id', $paymentId)
            ->orWhere('invoice_number', $paymentId)
            ->firstOrFail();
        
        // If payment is not completed, try to verify with the gateway
        if (!in_array($payment->payment_status, [Payment::STATUS_COMPLETED, Payment::STATUS_REFUNDED])) {
            try {
                $this->paymentService->verifyPayment($payment);
                $payment->refresh(); // Refresh to get updated status
            } catch (\Exception $e) {
                \Log::error('Payment verification failed: ' . $e->getMessage(), [
                    'payment_id' => $paymentId,
                    'exception' => $e,
                ]);
                
                // Continue with the current payment data even if verification fails
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment),
        ]);
    }
    
    /**
     * List payments with filters
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'search' => 'nullable|string|max:255',
            'status' => ['nullable', 'string', Rule::in([
                'pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled', 'expired'
            ])],
            'gateway' => 'nullable|string|exists:payment_gateways,code',
            'paymentable_type' => 'nullable|string|in:admission,tuition,exam,library,transport,hostel,other',
            'paymentable_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'sort_by' => 'nullable|string|in:created_at,updated_at,payment_date,amount',
            'sort_order' => 'nullable|string|in:asc,desc',
        ]);
        
        $query = Payment::query()
            ->with(['createdBy', 'updatedBy', 'paymentable'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhere('reference_number', 'like', "%{$search}%")
                      ->orWhere('transaction_id', 'like', "%{$search}%")
                      ->orWhere('payment_details', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('payment_status', $request->input('status'));
            })
            ->when($request->filled('gateway'), function ($q) use ($request) {
                $q->where('payment_method', $request->input('gateway'));
            })
            ->when($request->filled('paymentable_type'), function ($q) use ($request) {
                $q->where('paymentable_type', $request->input('paymentable_type'));
            })
            ->when($request->filled('paymentable_id'), function ($q) use ($request) {
                $q->where('paymentable_id', $request->input('paymentable_id'));
            })
            ->when($request->filled('date_from') && $request->filled('date_to'), function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->input('date_from') . ' 00:00:00',
                    $request->input('date_to') . ' 23:59:59',
                ]);
            });
        
        // Apply sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Paginate results
        $perPage = $request->input('per_page', 15);
        $payments = $query->paginate($perPage);
        
        return PaymentResource::collection($payments);
    }
    
    /**
     * Get payment details
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $payment = Payment::with(['createdBy', 'updatedBy', 'paymentable'])
            ->where('id', $id)
            ->orWhere('invoice_number', $id)
            ->firstOrFail();
            
        // Check if user has permission to view this payment
        $this->authorize('view', $payment);
        
        return new PaymentResource($payment);
    }
    
    /**
     * Update payment status manually (admin only)
     *
     * @param string $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus($id, Request $request)
    {
        $payment = Payment::findOrFail($id);
        
        // Check if user has permission to update this payment
        $this->authorize('update', $payment);
        
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in([
                'pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled', 'expired'
            ])],
            'notes' => 'nullable|string|max:1000',
        ]);
        
        // Update payment status
        $payment->update([
            'payment_status' => $validated['status'],
            'notes' => $validated['notes'] ?? $payment->notes,
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'status_updated_by' => auth()->id(),
                'status_updated_at' => now(),
                'status_update_notes' => $validated['notes'] ?? null,
            ]),
        ]);
        
        // If status is completed, update paid amount and date
        if ($validated['status'] === 'completed') {
            $payment->update([
                'paid_amount' => $payment->total_amount,
                'due_amount' => 0,
                'payment_date' => $payment->payment_date ?? now(),
            ]);
            
            // Trigger payment success event
            event(new \App\Events\PaymentProcessed($payment));
        }
        
        return new PaymentResource($payment);
    }
    
    /**
     * Record an offline payment
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordOfflinePayment(Request $request)
    {
        $validated = $request->validate([
            'gateway' => ['required', 'string', 'exists:payment_gateways,code'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'paymentable_type' => ['required', 'string', 'in:admission,tuition,exam,library,transport,hostel,other'],
            'paymentable_id' => ['required', 'numeric'],
            'payment_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ]);
        
        // Get the payment gateway
        $gateway = PaymentGateway::where('code', $validated['gateway'])->firstOrFail();
        
        // Create a new payment record
        $payment = new Payment([
            'paymentable_type' => $validated['paymentable_type'],
            'paymentable_id' => $validated['paymentable_id'],
            'amount' => $validated['amount'],
            'paid_amount' => $validated['amount'],
            'due_amount' => 0,
            'discount_amount' => 0,
            'fine_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => $validated['amount'],
            'payment_method' => $gateway->code,
            'payment_status' => Payment::STATUS_COMPLETED,
            'payment_date' => $validated['payment_date'],
            'reference_number' => $validated['reference_number'] ?? null,
            'transaction_id' => $validated['transaction_id'] ?? null,
            'payment_details' => [
                'description' => $validated['description'] ?? null,
                'metadata' => $validated['metadata'] ?? [],
                'recorded_by' => auth()->id(),
                'recorded_at' => now(),
            ],
            'notes' => $validated['notes'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
        
        $payment->save();
        
        // Trigger payment success event
        event(new \App\Events\PaymentProcessed($payment));
        
        return response()->json([
            'success' => true,
            'message' => 'Offline payment recorded successfully',
            'data' => new PaymentResource($payment),
        ], 201);
    }
    
    /**
     * Refund a payment
     *
     * @param string $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refund($id, Request $request)
    {
        $payment = Payment::findOrFail($id);
        
        // Check if user has permission to refund this payment
        $this->authorize('refund', $payment);
        
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . $payment->paid_amount],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        
        try {
            // Process refund with the payment gateway if it's an online payment
            $refunded = false;
            $refundId = null;
            
            if ($payment->payment_method !== 'cash' && $payment->payment_method !== 'cheque') {
                // In a real implementation, you would call the gateway's refund API here
                // For example: $refundResponse = $this->paymentService->refund($payment, $validated['amount']);
                // $refundId = $refundResponse['refund_id'];
                
                // For this example, we'll just generate a random refund ID
                $refundId = 'RFND' . strtoupper(Str::random(8));
            }
            
            // Create a refund record
            $refund = new \App\Models\Refund([
                'payment_id' => $payment->id,
                'amount' => $validated['amount'],
                'currency' => $payment->currency,
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'refund_id' => $refundId,
                'status' => 'completed', // In a real implementation, this would depend on the gateway response
                'processed_by' => auth()->id(),
                'processed_at' => now(),
                'metadata' => [
                    'original_payment' => $payment->toArray(),
                    'refunded_by' => auth()->user()->toArray(),
                ],
            ]);
            
            $refund->save();
            
            // Update payment status and amounts
            $payment->update([
                'paid_amount' => $payment->paid_amount - $validated['amount'],
                'due_amount' => $payment->total_amount - ($payment->paid_amount - $validated['amount']),
                'payment_status' => $payment->paid_amount - $validated['amount'] <= 0 ? 'refunded' : 'partially_refunded',
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'refunds' => array_merge($payment->payment_details['refunds'] ?? [], [
                        [
                            'id' => $refund->id,
                            'amount' => $validated['amount'],
                            'currency' => $payment->currency,
                            'reason' => $validated['reason'],
                            'refund_id' => $refundId,
                            'processed_at' => now(),
                            'processed_by' => auth()->id(),
                        ]
                    ]),
                ]),
            ]);
            
            // Trigger refund processed event
            event(new \App\Events\PaymentRefunded($payment, $refund));
            
            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully',
                'data' => [
                    'payment' => new PaymentResource($payment->fresh()),
                    'refund' => $refund,
                ],
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Refund failed: ' . $e->getMessage(), [
                'payment_id' => $id,
                'exception' => $e,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process refund. ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Export payments to CSV/Excel
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'format' => 'nullable|string|in:csv,xlsx',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => ['nullable', 'string', Rule::in([
                'pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled', 'expired'
            ])],
            'gateway' => 'nullable|string|exists:payment_gateways,code',
        ]);
        
        $query = Payment::query()
            ->with(['createdBy', 'paymentable'])
            ->when($request->filled('start_date') && $request->filled('end_date'), function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->input('start_date') . ' 00:00:00',
                    $request->input('end_date') . ' 23:59:59',
                ]);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('payment_status', $request->input('status'));
            })
            ->when($request->filled('gateway'), function ($q) use ($request) {
                $q->where('payment_method', $request->input('gateway'));
            })
            ->orderBy('created_at', 'desc');
        
        $payments = $query->get();
        
        // Format data for export
        $data = $payments->map(function ($payment) {
            return [
                'Invoice Number' => $payment->invoice_number,
                'Date' => $payment->created_at->format('Y-m-d H:i:s'),
                'Payment Method' => $payment->payment_method,
                'Status' => $payment->payment_status,
                'Amount' => $payment->amount,
                'Fee' => $payment->total_amount - $payment->amount,
                'Total' => $payment->total_amount,
                'Paid' => $payment->paid_amount,
                'Due' => $payment->due_amount,
                'Currency' => $payment->currency,
                'Reference' => $payment->reference_number,
                'Transaction ID' => $payment->transaction_id,
                'Description' => $payment->payment_details['description'] ?? '',
                'Payment Date' => $payment->payment_date?->format('Y-m-d H:i:s'),
                'Created By' => $payment->createdBy?->name,
                'Payment For' => $payment->paymentable_type . ' #' . $payment->paymentable_id,
            ];
        });
        
        $format = $request->input('format', 'csv');
        $filename = 'payments-export-' . now()->format('Y-m-d-H-i-s') . '.' . $format;
        
        if ($format === 'xlsx') {
            return (new \Maatwebsite\Excel\Collections\PaymentExport($data))->download($filename);
        }
        
        // Default to CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            if (count($data) > 0) {
                fputcsv($file, array_keys($data[0]));
            }
            
            // Add data rows
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
