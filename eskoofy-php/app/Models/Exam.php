<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Exam extends Model
{

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';

    protected static string $table = 'exams';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'name', 'code', 'description', 'type', 'status', 'start_date',
        'end_date', 'duration', 'total_marks', 'passing_marks',
        'grading_type', 'grading_scale', 'weightage', 'is_published',
        'publish_date', 'publish_remarks', 'academic_session_id', 'batch_id',
        'section_id', 'subject_id', 'created_by', 'updated_by', 'metadata',
    ];

    protected array $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'publish_date' => 'datetime',
        'total_marks' => 'float',
        'passing_marks' => 'float',
        'weightage' => 'float',
        'is_published' => 'boolean',
        'grading_scale' => 'json',
        'metadata' => 'json',
    ];

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

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

    public function results()
    {
        return $this->hasMany(ExamResult::class);
    }
}
