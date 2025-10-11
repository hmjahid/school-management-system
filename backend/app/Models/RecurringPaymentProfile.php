<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class RecurringPaymentProfile extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'profile_id',
        'user_id',
        'gateway',
        'gateway_profile_id',
        'amount',
        'currency',
        'billing_period',
        'billing_frequency',
        'start_date',
        'next_billing_date',
        'end_date',
        'status',
        'payment_method_token',
        'card_last4',
        'card_brand',
        'card_expiry',
        'max_failures',
        'failure_count',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'datetime',
        'next_billing_date' => 'datetime',
        'end_date' => 'datetime',
        'metadata' => 'array',
        'billing_frequency' => 'integer',
        'max_failures' => 'integer',
        'failure_count' => 'integer',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->profile_id)) {
                $model->profile_id = static::generateProfileId();
            }
        });
    }

    /**
     * Generate a unique profile ID.
     *
     * @return string
     */
    public static function generateProfileId(): string
    {
        return 'RPP' . strtoupper(substr(md5(uniqid('', true)), 0, 10)) . time();
    }

    /**
     * Get the user that owns the recurring payment profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent paymentable model.
     */
    public function paymentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the payments for the recurring payment profile.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'recurring_payment_profile_id');
    }

    /**
     * Scope a query to only include active profiles.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('next_billing_date', '<=', now())
                    ->where(function ($query) {
                        $query->whereNull('end_date')
                              ->orWhere('end_date', '>', now());
                    });
    }

    /**
     * Check if the profile is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->next_billing_date <= now() &&
               ($this->end_date === null || $this->end_date > now());
    }

    /**
     * Calculate the next billing date.
     */
    public function calculateNextBillingDate(): Carbon
    {
        $method = 'add' . ucfirst($this->billing_period) . 's';
        
        return $this->next_billing_date->$method($this->billing_frequency);
    }

    /**
     * Record a successful payment.
     */
    public function recordSuccessfulPayment(array $paymentData = []): Payment
    {
        $this->update([
            'next_billing_date' => $this->calculateNextBillingDate(),
            'failure_count' => 0, // Reset failure count on success
        ]);

        return $this->payments()->create([
            'user_id' => $this->user_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->gateway,
            'payment_status' => Payment::STATUS_COMPLETED,
            'transaction_id' => $paymentData['transaction_id'] ?? null,
            'payment_date' => now(),
            'payment_details' => array_merge([
                'recurring' => true,
                'billing_period' => $this->billing_period,
                'billing_frequency' => $this->billing_frequency,
                'profile_id' => $this->profile_id,
            ], $paymentData),
        ]);
    }

    /**
     * Record a failed payment attempt.
     */
    public function recordFailedPayment(string $reason, array $details = []): self
    {
        $this->increment('failure_count');
        
        if ($this->failure_count >= $this->max_failures) {
            $this->suspend($reason);
        }

        // Log the failed payment attempt
        $this->payments()->create([
            'user_id' => $this->user_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->gateway,
            'payment_status' => Payment::STATUS_FAILED,
            'payment_details' => array_merge([
                'recurring' => true,
                'failure_reason' => $reason,
                'failure_count' => $this->failure_count,
                'profile_id' => $this->profile_id,
            ], $details),
        ]);

        return $this;
    }

    /**
     * Suspend the recurring payment profile.
     */
    public function suspend(string $reason = null): self
    {
        $this->update([
            'status' => 'suspended',
            'metadata' => array_merge($this->metadata ?? [], [
                'suspended_at' => now()->toDateTimeString(),
                'suspension_reason' => $reason,
            ]),
        ]);

        // TODO: Trigger suspension event/notification
        
        return $this;
    }

    /**
     * Reactivate a suspended profile.
     */
    public function reactivate(): self
    {
        $this->update([
            'status' => 'active',
            'failure_count' => 0,
            'metadata' => array_merge($this->metadata ?? [], [
                'reactivated_at' => now()->toDateTimeString(),
            ]),
        ]);

        // TODO: Trigger reactivation event/notification
        
        return $this;
    }

    /**
     * Cancel the recurring payment profile.
     */
    public function cancel(string $reason = null): self
    {
        $this->update([
            'status' => 'cancelled',
            'end_date' => now(),
            'metadata' => array_merge($this->metadata ?? [], [
                'cancelled_at' => now()->toDateTimeString(),
                'cancellation_reason' => $reason,
            ]),
        ]);

        // TODO: Trigger cancellation event/notification
        
        return $this;
    }

    /**
     * Update the payment method for the profile.
     */
    public function updatePaymentMethod(array $paymentMethod): self
    {
        $this->update([
            'payment_method_token' => $paymentMethod['token'] ?? null,
            'card_last4' => $paymentMethod['last4'] ?? null,
            'card_brand' => $paymentMethod['brand'] ?? null,
            'card_expiry' => $paymentMethod['expiry'] ?? null,
        ]);

        return $this;
    }

    /**
     * Get the display name for the billing period.
     */
    public function getBillingPeriodNameAttribute(): string
    {
        $periods = [
            'day' => 'Daily',
            'week' => 'Weekly',
            'month' => 'Monthly',
            'year' => 'Yearly',
        ];

        $frequency = $this->billing_frequency > 1 
            ? "Every {$this->billing_frequency} " . str_plural($this->billing_period, $this->billing_frequency)
            : $periods[$this->billing_period] ?? ucfirst($this->billing_period);

        return $frequency;
    }

    /**
     * Get the formatted amount with currency.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . strtoupper($this->currency);
    }
}
