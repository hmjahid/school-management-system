<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Grade extends Model
{
    protected static string $table = 'grades';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'student_id', 'class_id', 'subject_id', 'exam_id',
        'marks_obtained', 'total_marks', 'grade', 'remarks',
    ];

    protected array $casts = [
        'marks_obtained' => 'float',
        'total_marks'    => 'float',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}