<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Routine extends Model
{
    protected static string $table = 'routines';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'school_class_id', 'section_id', 'subject_id', 'teacher_id',
        'day_of_week', 'start_time', 'end_time', 'room_number', 'batch_id',
        'academic_session_id', 'is_active', 'type',
    ];

    protected array $casts = [
        'is_active' => 'boolean',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }
}
