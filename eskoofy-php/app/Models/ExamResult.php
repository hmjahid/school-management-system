<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class ExamResult extends Model
{
    protected static string $table = 'exam_results';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'exam_id', 'student_id', 'obtained_marks', 'grade', 'grade_point',
        'remarks', 'status', 'submitted_by', 'submitted_at', 'reviewed_by',
        'reviewed_at', 'review_remarks', 'is_published', 'publish_remarks',
        'published_at', 'published_by', 'unpublish_remarks', 'unpublished_at',
        'unpublished_by',
    ];

    protected array $casts = [
        'obtained_marks' => 'float',
        'grade_point' => 'float',
        'is_published' => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
        'unpublished_at' => 'datetime',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
