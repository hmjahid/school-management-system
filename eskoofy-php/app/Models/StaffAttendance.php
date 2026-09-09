<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class StaffAttendance extends Model
{
    protected static string $table = 'staff_attendances';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'teacher_id', 'date', 'status', 'check_in_at', 'check_out_at',
        'note', 'recorded_by',
    ];

    protected array $casts = [
        'date' => 'date',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
