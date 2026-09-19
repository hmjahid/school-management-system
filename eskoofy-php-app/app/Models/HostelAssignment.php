<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class HostelAssignment extends Model
{
    protected static string $table = 'hostel_assignments';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'student_id', 'room_id', 'check_in_date', 'check_out_date',
        'status', 'notes',
    ];

    protected array $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function room()
    {
        return $this->belongsTo(HostelRoom::class, 'room_id');
    }
}
