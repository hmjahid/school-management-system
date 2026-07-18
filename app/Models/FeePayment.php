<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeePayment extends Model
{
    use SoftDeletes;

    // Payment statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    // Payment methods
    public const METHOD_CASH = 'cash';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_CHECK = 'check';
    public const METHOD_ONLINE_PAYMENT = 'online_payment';
    public const METHOD_MOBILE_BANKING = 'mobile_banking';
    public const METHOD_OTHER = 'other';

    protected $fillable = [
        'invoice_number',
        'student_id',
        'fee_id',
        'amount',
        'discount_amount',
        'fine_amount',
        'paid_amount',
        'balance',
        'payment_date',
        'month',
        'year',
        'payment_method',
        'transaction_id',
        'bank_name',
        'check_number',
        'status',
        'notes',
        'metadata',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'fine_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'formatted_amount',
        'formatted_discount_amount',
        'formatted_fine_amount',
        'formatted_paid_amount',
        'formatted_balance',
        'status_badge',
        'payment_method_label',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($feePayment) {
            if (empty($feePayment->invoice_number)) {
                $feePayment->invoice_number = static::generateInvoiceNumber();
            }
            
            if (auth()->check()) {
                $feePayment->created_by = $feePayment->created_by ?? auth()->id();
            }
        });
    }

    /**
     * Generate a unique invoice number.
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Ymd') . '-';
        $lastInvoice = static::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastInvoice 
            ? (int) str_replace($prefix, '', $lastInvoice->invoice_number) + 1 
            : 1;

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the student that owns the fee payment.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the fee that owns the fee payment.
     */
    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }

    /**
     * Get the user who created the fee payment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved the fee payment.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the formatted amount attribute.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2);
    }

    /**
     * Get the formatted discount amount attribute.
     */
    public function getFormattedDiscountAmountAttribute(): string
    {
        return number_format($this->discount_amount, 2);
    }

    /**
     * Get the formatted fine amount attribute.
     */
    public function getFormattedFineAmountAttribute(): string
    {
        return number_format($this->fine_amount, 2);
    }

    /**
     * Get the formatted paid amount attribute.
     */
    public function getFormattedPaidAmountAttribute(): string
    {
        return number_format($this->paid_amount, 2);
    }

    /**
     * Get the formatted balance attribute.
     */
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance, 2);
    }

    /**
     * Get the status badge HTML.
     */
    public function getStatusBadgeAttribute(): string
    {
        $statuses = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_PAID => 'success',
            self::STATUS_PARTIAL => 'info',
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_REFUNDED => 'secondary',
        ];

        $color = $statuses[$this->status] ?? 'secondary';
        return "<span class='badge bg-{$color}'>" . ucfirst($this->status) . "</span>";
    }

    /**
     * Get the payment method label.
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        $methods = [
            self::METHOD_CASH => 'Cash',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_CHECK => 'Check',
            self::METHOD_ONLINE_PAYMENT => 'Online Payment',
            self::METHOD_MOBILE_BANKING => 'Mobile Banking',
            self::METHOD_OTHER => 'Other',
        ];

        return $methods[$this->payment_method] ?? ucfirst(str_replace('_', ' ', $this->payment_method));
    }

    /**
     * Get all payment statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PAID => 'Paid',
            self::STATUS_PARTIAL => 'Partial',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REFUNDED => 'Refunded',
        ];
    }

    /**
     * Get all payment methods.
     */
    public static function getPaymentMethods(): array
    {
        return [
            self::METHOD_CASH => 'Cash',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_CHECK => 'Check',
            self::METHOD_ONLINE_PAYMENT => 'Online Payment',
            self::METHOD_MOBILE_BANKING => 'Mobile Banking',
            self::METHOD_OTHER => 'Other',
        ];
    }
}
