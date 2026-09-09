<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class RecurringPaymentProfile extends Model
{
    protected static string $table = 'recurring_payment_profiles';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'profile_id', 'user_id', 'gateway', 'gateway_profile_id', 'amount',
        'currency', 'billing_period', 'billing_frequency', 'start_date',
        'next_billing_date', 'end_date', 'status', 'payment_method_token',
        'card_last4', 'card_brand', 'card_expiry', 'max_failures',
        'failure_count', 'metadata',
    ];

    protected array $casts = [
        'amount' => 'float',
        'start_date' => 'datetime',
        'next_billing_date' => 'datetime',
        'end_date' => 'datetime',
        'metadata' => 'json',
        'billing_frequency' => 'integer',
        'max_failures' => 'integer',
        'failure_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentable()
    {
        return $this->morphTo();
    }
}
