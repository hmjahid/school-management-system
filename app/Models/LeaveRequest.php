<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LeaveRequest extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'from_date', 'to_date', 'reason'])
            ->logOnlyDirty()
            ->useLogName('leaves');
    }

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'teacher_id', 'leave_type_id', 'from_date', 'to_date', 'reason',
        'status', 'approver_id', 'approver_note', 'decided_at',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'decided_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function days(): int
    {
        return $this->from_date->diffInDays($this->to_date) + 1;
    }

    public function approve(int $userId, ?string $note = null): bool
    {
        $this->status = self::STATUS_APPROVED;
        $this->approver_id = $userId;
        $this->approver_note = $note;
        $this->decided_at = now();

        return $this->save();
    }

    public function reject(int $userId, ?string $note = null): bool
    {
        $this->status = self::STATUS_REJECTED;
        $this->approver_id = $userId;
        $this->approver_note = $note;
        $this->decided_at = now();

        return $this->save();
    }

    public function cancel(): bool
    {
        $this->status = self::STATUS_CANCELLED;
        $this->decided_at = now();

        return $this->save();
    }
}
