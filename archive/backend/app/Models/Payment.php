<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{

    // Payment statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    // Payment methods
    public const METHOD_CASH = 'cash';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_CHEQUE = 'cheque';
    public const METHOD_BKASH = 'bkash';
    public const METHOD_NAGAD = 'nagad';
    public const METHOD_ROCKET = 'rocket';
    public const METHOD_STRIPE = 'stripe';
    public const METHOD_PAYPAL = 'paypal';
    public const METHOD_OTHER = 'other';

    // Payment purposes
    public const PURPOSE_ADMISSION = 'admission';
    public const PURPOSE_TUITION = 'tuition';
    public const PURPOSE_EXAM = 'exam';
    public const PURPOSE_LIBRARY = 'library';
    public const PURPOSE_TRANSPORT = 'transport';
    public const PURPOSE_HOSTEL = 'hostel';
    public const PURPOSE_OTHER = 'other';

    protected $fillable = [
        'paymentable_type',
        'paymentable_id',
        'invoice_number',
        'amount',
        'paid_amount',
        'due_amount',
        'discount_amount',
        'fine_amount',
        'tax_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'payment_date',
        'due_date',
        'reference_number',
        'transaction_id',
        'payment_details',
        'notes',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'fine_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payment_date' => 'date',
        'due_date' => 'date',
        'payment_details' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = [
        'status_label',
        'method_label',
        'is_fully_paid',
        'is_overdue',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->invoice_number)) {
                $payment->invoice_number = static::generateInvoiceNumber();
            }
            
            if (auth()->check()) {
                $payment->created_by = auth()->id();
                $payment->updated_by = auth()->id();
            }
        });

        static::updating(function ($payment) {
            if (auth()->check()) {
                $payment->updated_by = auth()->id();
            }
        });
    }

    /**
     * Generate a unique invoice number.
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV' . date('Ymd');
        $lastInvoice = static::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastInvoice 
            ? (int) substr($lastInvoice->invoice_number, 11) + 1 
            : 1;

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the parent paymentable model.
     */
    public function paymentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created the payment.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the payment.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the payment status label.
     */
    public function getStatusLabelAttribute(): string
    {
        $statuses = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_REFUNDED => 'Refunded',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_EXPIRED => 'Expired',
        ];

        return $statuses[$this->payment_status] ?? 'Unknown';
    }

    /**
     * Get the payment method label.
     */
    public function getMethodLabelAttribute(): string
    {
        $methods = [
            self::METHOD_CASH => 'Cash',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_CHEQUE => 'Cheque',
            self::METHOD_BKASH => 'bKash',
            self::METHOD_NAGAD => 'Nagad',
            self::METHOD_ROCKET => 'Rocket',
            self::METHOD_STRIPE => 'Stripe',
            self::METHOD_PAYPAL => 'PayPal',
            self::METHOD_OTHER => 'Other',
        ];

        return $methods[$this->payment_method] ?? 'Unknown';
    }

    /**
     * Check if the payment is fully paid.
     */
    public function getIsFullyPaidAttribute(): bool
    {
        return $this->paid_amount >= $this->total_amount && $this->payment_status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the payment is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->isFullyPaid;
    }

    /**
     * Scope a query to only include pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('payment_status', self::STATUS_COMPLETED);
    }

    /**
     * Scope a query to only include failed payments.
     */
    public function scopeFailed($query)
    {
        return $query->where('payment_status', self::STATUS_FAILED);
    }

    /**
     * Scope a query to only include overdue payments.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('payment_status', '!=', self::STATUS_COMPLETED)
            ->where('payment_status', '!=', self::STATUS_REFUNDED)
            ->where('payment_status', '!=', self::STATUS_CANCELLED);
    }

    /**
     * Mark the payment as completed.
     */
    public function markAsCompleted(array $details = []): bool
    {
        $this->payment_status = self::STATUS_COMPLETED;
        $this->paid_amount = $this->total_amount;
        $this->due_amount = 0;
        $this->payment_date = now();
        $this->payment_details = array_merge($this->payment_details ?? [], $details);
        
        return $this->save();
    }

    /**
     * Mark the payment as failed.
     */
    public function markAsFailed(string $reason = null): bool
    {
        $this->payment_status = self::STATUS_FAILED;
        $this->payment_details = array_merge($this->payment_details ?? [], [
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
        
        return $this->save();
    }

    /**
     * Record a partial payment.
     */
    public function recordPayment(float $amount, array $details = []): bool
    {
        $this->paid_amount += $amount;
        $this->due_amount = max(0, $this->total_amount - $this->paid_amount);
        
        if ($this->paid_amount >= $this->total_amount) {
            $this->payment_status = self::STATUS_COMPLETED;
            $this->paid_amount = $this->total_amount;
            $this->due_amount = 0;
        } else {
            $this->payment_status = self::STATUS_PROCESSING;
        }
        
        $this->payment_date = now();
        $this->payment_details = array_merge($this->payment_details ?? [], [
            'payments' => array_merge(
                $this->payment_details['payments'] ?? [],
                [
                    'date' => now(),
                    'amount' => $amount,
                    'details' => $details,
                ]
            ),
        ]);
        
        return $this->save();
    }

    /**
     * Get the payment gateway configuration.
     */
    public function getGatewayConfig(string $gateway): array
    {
        $config = config("payment.gateways.{$gateway}", []);
        
        return array_merge([
            'enabled' => false,
            'test_mode' => true,
            'api_key' => null,
            'api_secret' => null,
            'webhook_secret' => null,
            'currency' => 'BDT',
            'fee_percentage' => 0,
            'fee_fixed' => 0,
        ], $config);
    }
}
