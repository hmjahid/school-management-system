<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    public const STATUS_PAID = 'paid';

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_OVERDUE = 'overdue';

    protected $fillable = [
        'student_id',
        'fee_id',
        'amount',
        'due_date',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === self::STATUS_OVERDUE
            || ($this->status === self::STATUS_UNPAID && $this->due_date?->isPast());
    }
}
