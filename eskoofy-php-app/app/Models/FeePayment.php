<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class FeePayment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    public const METHOD_CASH = 'cash';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_CHECK = 'check';
    public const METHOD_ONLINE_PAYMENT = 'online_payment';
    public const METHOD_MOBILE_BANKING = 'mobile_banking';
    public const METHOD_OTHER = 'other';

    protected static string $table = 'fee_payments';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'invoice_number', 'student_id', 'fee_id', 'amount', 'discount_amount',
        'fine_amount', 'paid_amount', 'balance', 'payment_date', 'month',
        'year', 'payment_method', 'transaction_id', 'bank_name',
        'check_number', 'status', 'notes', 'metadata', 'created_by',
        'approved_by', 'approved_at',
    ];

    protected array $casts = [
        'amount' => 'float',
        'discount_amount' => 'float',
        'fine_amount' => 'float',
        'paid_amount' => 'float',
        'balance' => 'float',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function fee()
    {
        return $this->belongsTo(Fee::class);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        $methods = [
            self::METHOD_CASH          => 'Cash',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_CHECK         => 'Check',
            self::METHOD_ONLINE_PAYMENT => 'Online Payment',
            self::METHOD_MOBILE_BANKING => 'Mobile Banking',
            self::METHOD_OTHER         => 'Other',
        ];

        return $methods[$this->attributes['payment_method'] ?? ''] ?? ucfirst(str_replace('_', ' ', (string) ($this->attributes['payment_method'] ?? '')));
    }
}
