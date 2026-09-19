<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Attendance extends Model
{

    public const TYPE_DAILY = 'daily';
    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LATE = 'late';
    public const STATUS_HALF_DAY = 'half_day';
    public const STATUS_HOLIDAY = 'holiday';
    public const STATUS_LEAVE = 'on_leave';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PRESENT  => 'Present',
            self::STATUS_ABSENT   => 'Absent',
            self::STATUS_LATE     => 'Late',
            self::STATUS_HALF_DAY => 'Half Day',
            self::STATUS_HOLIDAY  => 'Holiday',
            self::STATUS_LEAVE    => 'On Leave',
        ];
    }

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
