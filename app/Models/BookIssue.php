<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookIssue extends Model
{
    use SoftDeletes;

    const STATUS_ISSUED = 'issued';

    const STATUS_RETURNED = 'returned';

    const STATUS_LOST = 'lost';

    const STATUS_DAMAGED = 'damaged';

    protected $fillable = [
        'book_id', 'student_id', 'teacher_id', 'issue_date', 'due_date',
        'return_date', 'status', 'late_fee', 'fine_paid', 'notes', 'issued_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'return_date' => 'date',
        'late_fee' => 'decimal:2',
        'fine_paid' => 'boolean',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function scopeIssued($query)
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_ISSUED)
            ->where('due_date', '<', Carbon::today());
    }

    public function scopeReturned($query)
    {
        return $query->where('status', self::STATUS_RETURNED);
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_ISSUED && Carbon::parse($this->due_date)->isPast();
    }

    public function calculateLateFee(float $lateFeePerDay): float
    {
        if ($this->return_date && $this->return_date > $this->due_date) {
            $days = Carbon::parse($this->due_date)->diffInDays($this->return_date);

            return round($days * $lateFeePerDay, 2);
        }
        if ($this->isOverdue()) {
            $days = Carbon::parse($this->due_date)->diffInDays(Carbon::today());

            return round($days * $lateFeePerDay, 2);
        }

        return 0;
    }
}
