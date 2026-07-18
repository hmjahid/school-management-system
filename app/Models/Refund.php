<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'payment_id',
        'user_id',
        'processed_by',
        'amount',
        'currency',
        'transaction_id',
        'status',
        'reason',
        'processed_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'formatted_amount',
        'status_label',
    ];

    /**
     * Get the payment that owns the refund.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the user that owns the refund.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who processed the refund.
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the formatted amount with currency.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . strtoupper($this->currency);
    }

    /**
     * Get the human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Scope a query to only include completed refunds.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include pending refunds.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include failed refunds.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Mark the refund as completed.
     */
    public function markAsCompleted(string $transactionId = null): bool
    {
        return $this->update([
            'status' => 'completed',
            'transaction_id' => $transactionId ?? $this->transaction_id,
            'processed_at' => $this->processed_at ?? now(),
        ]);
    }

    /**
     * Mark the refund as failed.
     */
    public function markAsFailed(string $error = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'metadata' => array_merge($this->metadata ?? [], [
                'error' => $error,
                'failed_at' => now()->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * Check if the refund is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the refund is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the refund is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get the refund's metadata value.
     */
    public function getMeta(string $key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }
}
