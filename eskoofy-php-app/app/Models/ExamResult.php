<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class ExamResult extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PASSED = 'passed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_MALPRACTICE = 'malpractice';

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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        $classes = [
            self::STATUS_PENDING     => 'bg-yellow-100 text-yellow-800',
            self::STATUS_PASSED      => 'bg-green-100 text-green-800',
            self::STATUS_FAILED      => 'bg-red-100 text-red-800',
            self::STATUS_ABSENT      => 'bg-gray-100 text-gray-800',
            self::STATUS_MALPRACTICE => 'bg-purple-100 text-purple-800',
        ];

        return $classes[$this->attributes['status'] ?? ''] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusLabelAttribute(): string
    {
        $statuses = [
            self::STATUS_PENDING     => 'Pending',
            self::STATUS_PASSED      => 'Passed',
            self::STATUS_FAILED      => 'Failed',
            self::STATUS_ABSENT      => 'Absent',
            self::STATUS_MALPRACTICE => 'Malpractice',
        ];

        return $statuses[$this->attributes['status'] ?? ''] ?? 'Unknown';
    }
}
