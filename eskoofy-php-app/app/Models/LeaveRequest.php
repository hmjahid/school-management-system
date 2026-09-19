<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class LeaveRequest extends Model
{
    protected static string $table = 'leave_requests';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'teacher_id', 'leave_type_id', 'from_date', 'to_date', 'reason',
        'status', 'approver_id', 'approver_note', 'decided_at',
    ];

    protected array $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'decided_at' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function type()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function days(): int
    {
        $from = $this->from_date;
        $to = $this->to_date;
        if (!$from instanceof \App\Core\Support\Carbon || !$to instanceof \App\Core\Support\Carbon) {
            return 0;
        }
        return (int) $from->diffInDays($to) + 1;
    }
}
