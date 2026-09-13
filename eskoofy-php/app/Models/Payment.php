<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Payment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    public const METHOD_CASH = 'cash';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_CHEQUE = 'cheque';
    public const METHOD_BKASH = 'bkash';
    public const METHOD_NAGAD = 'nagad';
    public const METHOD_ROCKET = 'rocket';
    public const METHOD_STRIPE = 'stripe';
    public const METHOD_PAYPAL = 'paypal';
    public const METHOD_OTHER = 'other';

    public const PURPOSE_ADMISSION = 'admission';
    public const PURPOSE_TUITION = 'tuition';
    public const PURPOSE_EXAM = 'exam';
    public const PURPOSE_LIBRARY = 'library';
    public const PURPOSE_TRANSPORT = 'transport';
    public const PURPOSE_HOSTEL = 'hostel';
    public const PURPOSE_OTHER = 'other';

    protected static string $table = 'payments';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'paymentable_type', 'paymentable_id', 'invoice_number', 'amount',
        'paid_amount', 'due_amount', 'discount_amount', 'fine_amount',
        'tax_amount', 'total_amount', 'payment_method', 'payment_status',
        'refund_status', 'payment_date', 'due_date', 'reference_number',
        'transaction_id', 'payment_details', 'notes', 'metadata',
        'created_by', 'updated_by',
    ];

    protected array $casts = [
        'amount' => 'float',
        'paid_amount' => 'float',
        'due_amount' => 'float',
        'discount_amount' => 'float',
        'fine_amount' => 'float',
        'tax_amount' => 'float',
        'total_amount' => 'float',
        'payment_date' => 'date',
        'due_date' => 'date',
        'payment_details' => 'json',
        'metadata' => 'json',
    ];

    public function paymentable()
    {
        return $this->morphTo();
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function getStatusLabelAttribute(): string
    {
        $statuses = [
            self::STATUS_PENDING    => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED  => 'Completed',
            self::STATUS_FAILED     => 'Failed',
            self::STATUS_REFUNDED   => 'Refunded',
            self::STATUS_CANCELLED  => 'Cancelled',
            self::STATUS_EXPIRED    => 'Expired',
        ];

        return $statuses[$this->attributes['payment_status'] ?? ''] ?? 'Unknown';
    }

    public function getMethodLabelAttribute(): string
    {
        $methods = [
            self::METHOD_CASH          => 'Cash',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_CHEQUE        => 'Cheque',
            self::METHOD_BKASH         => 'bKash',
            self::METHOD_NAGAD         => 'Nagad',
            self::METHOD_ROCKET        => 'Rocket',
            self::METHOD_STRIPE        => 'Stripe',
            self::METHOD_PAYPAL        => 'PayPal',
            self::METHOD_OTHER         => 'Other',
        ];

        return $methods[$this->attributes['payment_method'] ?? ''] ?? 'Unknown';
    }
}
