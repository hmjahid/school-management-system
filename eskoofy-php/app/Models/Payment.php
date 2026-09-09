<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Payment extends Model
{
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
}
