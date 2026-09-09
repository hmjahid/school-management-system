<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Attendance extends Model
{
    protected static string $table = 'attendances';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'date', 'status', 'type', 'school_class_id', 'batch_id',
        'section_id', 'subject_id', 'student_id', 'teacher_id',
        'academic_session_id', 'period', 'remarks', 'recorded_by',
        'updated_by', 'marked_by', 'metadata',
    ];

    protected array $casts = [
        'date' => 'date',
        'metadata' => 'json',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }
}
