<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class FeePayment extends Model
{
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
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function fee()
    {
        return $this->belongsTo(Fee::class);
    }
}
