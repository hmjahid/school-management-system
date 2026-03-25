<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Exam extends Model
{
    use SoftDeletes;

    // Exam types
    public const TYPE_QUIZ = 'quiz';
    public const TYPE_MID_TERM = 'mid_term';
    public const TYPE_FINAL = 'final';
    public const TYPE_ASSIGNMENT = 'assignment';
    public const TYPE_PROJECT = 'project';
    public const TYPE_PRACTICAL = 'practical';
    public const TYPE_ORAL = 'oral';
    public const TYPE_OTHER = 'other';

    // Exam statuses
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ONGOING = 'ongoing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PUBLISHED = 'published';

    // Grading types
    public const GRADING_PERCENTAGE = 'percentage';
    public const GRADING_GRADE = 'grade';
    public const GRADING_PASS_FAIL = 'pass_fail';
    public const GRADING_CUSTOM = 'custom';

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'status',
        'start_date',
        'end_date',
        'duration', // in minutes
        'total_marks',
        'passing_marks',
        'grading_type',
        'grading_scale',
        'weightage',
        'is_published',
        'publish_date',
        'publish_remarks',
        'academic_session_id',
        'batch_id',
        'section_id',
        'subject_id',
        'created_by',
        'updated_by',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'publish_date' => 'datetime',
        'total_marks' => 'float',
        'passing_marks' => 'float',
        'weightage' => 'float',
        'is_published' => 'boolean',
        'grading_scale' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = [
        'status_badge',
        'is_upcoming',
        'is_ongoing',
        'is_completed',
        'is_published',
        'duration_formatted',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($exam) {
            if (auth()->check()) {
                $exam->created_by = auth()->id();
                $exam->updated_by = auth()->id();
            }
        });

        static::updating(function ($exam) {
            if (auth()->check()) {
                $exam->updated_by = auth()->id();
            }
        });
    }

    /**
     * Get the academic session that owns the exam.
     */
    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    /**
     * Get the batch that owns the exam.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the section that owns the exam.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the subject that owns the exam.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the teacher who created the exam.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the teacher who last updated the exam.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the exam schedule for the exam.
     */
    public function schedule(): HasOne
    {
        return $this->hasOne(ExamSchedule::class);
    }

    /**
     * Get the exam results for the exam.
     */
    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * Get the exam questions for the exam.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class);
    }

    /**
     * The teachers that are assigned to this exam.
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'exam_teacher')
            ->withPivot(['is_chief_examiner', 'is_observer', 'responsibilities'])
            ->withTimestamps();
    }

    /**
     * Get the status badge HTML.
     */
    public function getStatusBadgeAttribute(): string
    {
        $badgeClasses = [
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_SCHEDULED => 'info',
            self::STATUS_ONGOING => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_PUBLISHED => 'success',
        ];

        $status = $this->status ?? self::STATUS_DRAFT;
        $badgeClass = $badgeClasses[$status] ?? 'secondary';
        $statusLabel = str_replace('_', ' ', ucfirst($status));

        return "<span class='badge bg-{$badgeClass}'>{$statusLabel}</span>";
    }

    /**
     * Check if the exam is upcoming.
     */
    public function getIsUpcomingAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED]) && 
               $this->start_date > now();
    }

    /**
     * Check if the exam is ongoing.
     */
    public function getIsOngoingAttribute(): bool
    {
        return $this->status === self::STATUS_ONGOING || 
               ($this->start_date <= now() && $this->end_date >= now());
    }

    /**
     * Check if the exam is completed.
     */
    public function getIsCompletedAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_PUBLISHED]) || 
               $this->end_date < now();
    }

    /**
     * Check if the exam is published.
     */
    public function getIsPublishedAttribute(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->is_published;
    }

    /**
     * Get the formatted duration.
     */
    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration) {
            return 'N/A';
        }

        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;

        $result = [];
        if ($hours > 0) {
            $result[] = $hours . ' ' . str_plural('hour', $hours);
        }
        if ($minutes > 0) {
            $result[] = $minutes . ' ' . str_plural('minute', $minutes);
        }

        return implode(' ', $result);
    }

    /**
     * Scope a query to only include upcoming exams.
     */
    public function scopeUpcoming($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_SCHEDULED])
                    ->where('start_date', '>', now());
    }

    /**
     * Scope a query to only include ongoing exams.
     */
    public function scopeOngoing($query)
    {
        return $query->where(function($q) {
            $q->where('status', self::STATUS_ONGOING)
              ->orWhere(function($q) {
                  $q->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
              });
        });
    }

    /**
     * Scope a query to only include completed exams.
     */
    public function scopeCompleted($query)
    {
        return $query->where(function($q) {
            $q->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_PUBLISHED])
              ->orWhere('end_date', '<', now());
        });
    }

    /**
     * Scope a query to only include published exams.
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
                    ->where('is_published', true);
    }

    /**
     * Scope a query to only include exams for a specific batch.
     */
    public function scopeForBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope a query to only include exams for a specific section.
     */
    public function scopeForSection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    /**
     * Scope a query to only include exams for a specific subject.
     */
    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    /**
     * Scope a query to only include exams created by a specific teacher.
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Get all possible exam types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_QUIZ => 'Quiz',
            self::TYPE_MID_TERM => 'Mid Term',
            self::TYPE_FINAL => 'Final Exam',
            self::TYPE_ASSIGNMENT => 'Assignment',
            self::TYPE_PROJECT => 'Project',
            self::TYPE_PRACTICAL => 'Practical',
            self::TYPE_ORAL => 'Oral Exam',
            self::TYPE_OTHER => 'Other',
        ];
    }

    /**
     * Get all possible exam statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_ONGOING => 'Ongoing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_PUBLISHED => 'Published',
        ];
    }

    /**
     * Get all possible grading types.
     */
    public static function getGradingTypes(): array
    {
        return [
            self::GRADING_PERCENTAGE => 'Percentage',
            self::GRADING_GRADE => 'Grade',
            self::GRADING_PASS_FAIL => 'Pass/Fail',
            self::GRADING_CUSTOM => 'Custom Scale',
        ];
    }

    /**
     * Get the default grading scale.
     */
    public static function getDefaultGradingScale(): array
    {
        return [
            ['min' => 80, 'max' => 100, 'grade' => 'A+', 'points' => 4.0, 'remark' => 'Excellent'],
            ['min' => 70, 'max' => 79, 'grade' => 'A', 'points' => 3.7, 'remark' => 'Very Good'],
            ['min' => 65, 'max' => 69, 'grade' => 'A-', 'points' => 3.3, 'remark' => 'Good'],
            ['min' => 60, 'max' => 64, 'grade' => 'B+', 'points' => 3.0, 'remark' => 'Above Average'],
            ['min' => 55, 'max' => 59, 'grade' => 'B', 'points' => 2.7, 'remark' => 'Average'],
            ['min' => 50, 'max' => 54, 'grade' => 'B-', 'points' => 2.3, 'remark' => 'Satisfactory'],
            ['min' => 45, 'max' => 49, 'grade' => 'C+', 'points' => 2.0, 'remark' => 'Below Average'],
            ['min' => 40, 'max' => 44, 'grade' => 'C', 'points' => 1.7, 'remark' => 'Pass'],
            ['min' => 0, 'max' => 39, 'grade' => 'F', 'points' => 0.0, 'remark' => 'Fail'],
        ];
    }

    /**
     * Calculate the grade based on the score.
     */
    public function calculateGrade(float $score): array
    {
        $gradingScale = $this->grading_scale ?? self::getDefaultGradingScale();
        
        foreach ($gradingScale as $grade) {
            if ($score >= $grade['min'] && $score <= $grade['max']) {
                return [
                    'grade' => $grade['grade'],
                    'points' => $grade['points'],
                    'remark' => $grade['remark'] ?? '',
                ];
            }
        }

        // Default to fail if no grade matches
        return [
            'grade' => 'F',
            'points' => 0.0,
            'remark' => 'Fail',
        ];
    }

    /**
     * Check if a student has taken the exam.
     */
    public function hasStudentTakenExam($studentId): bool
    {
        return $this->results()->where('student_id', $studentId)->exists();
    }

    /**
     * Get the exam result for a student.
     */
    public function getStudentResult($studentId)
    {
        return $this->results()->where('student_id', $studentId)->first();
    }

    /**
     * Get the exam statistics.
     */
    public function getStatistics(): array
    {
        $results = $this->results()->get();
        $totalStudents = $results->count();
        
        if ($totalStudents === 0) {
            return [
                'total_students' => 0,
                'participated' => 0,
                'not_participated' => 0,
                'passed' => 0,
                'failed' => 0,
                'average_score' => 0,
                'highest_score' => 0,
                'lowest_score' => 0,
                'grade_distribution' => [],
                'participation_rate' => 0,
                'pass_rate' => 0,
            ];
        }

        $participated = $results->where('status', 'submitted')->count();
        $passed = $results->where('is_passed', true)->count();
        $scores = $results->where('status', 'submitted')->pluck('obtained_marks')->filter()->toArray();
        
        $averageScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
        $highestScore = count($scores) > 0 ? max($scores) : 0;
        $lowestScore = count($scores) > 0 ? min($scores) : 0;
        
        // Calculate grade distribution
        $gradeDistribution = [];
        $gradingScale = $this->grading_scale ?? self::getDefaultGradingScale();
        
        foreach ($gradingScale as $grade) {
            $count = $results->filter(function($result) use ($grade) {
                return $result->obtained_marks >= $grade['min'] && $result->obtained_marks <= $grade['max'];
            })->count();
            
            $gradeDistribution[] = [
                'grade' => $grade['grade'],
                'count' => $count,
                'percentage' => $participated > 0 ? round(($count / $participated) * 100, 2) : 0,
            ];
        }

        return [
            'total_students' => $totalStudents,
            'participated' => $participated,
            'not_participated' => $totalStudents - $participated,
            'passed' => $passed,
            'failed' => $participated - $passed,
            'average_score' => round($averageScore, 2),
            'highest_score' => round($highestScore, 2),
            'lowest_score' => round($lowestScore, 2),
            'grade_distribution' => $gradeDistribution,
            'participation_rate' => round(($participated / $totalStudents) * 100, 2),
            'pass_rate' => $participated > 0 ? round(($passed / $participated) * 100, 2) : 0,
        ];
    }
}
