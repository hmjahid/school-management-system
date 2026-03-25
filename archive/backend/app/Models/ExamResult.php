<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExamResult extends Model
{
    use SoftDeletes;

    // Result statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_PASSED = 'passed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_MALPRACTICE = 'malpractice';

    protected $fillable = [
        'exam_id',
        'student_id',
        'obtained_marks',
        'grade',
        'grade_point',
        'remarks',
        'status',
        'submitted_by',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_remarks',
        'is_published',
        'published_at',
        'published_by',
    ];

    protected $casts = [
        'obtained_marks' => 'float',
        'grade_point' => 'float',
        'is_published' => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    /**
     * Get the exam that owns the result.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the student that owns the result.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the staff who submitted the result.
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'submitted_by');
    }

    /**
     * Get the staff who reviewed the result.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by');
    }

    /**
     * Get the staff who published the result.
     */
    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'published_by');
    }

    /**
     * Get the result details.
     */
    public function details(): HasMany
    {
        return $this->hasMany(ExamResultDetail::class);
    }

    /**
     * Get the result remarks.
     */
    public function remarks(): HasMany
    {
        return $this->hasMany(ExamRemark::class);
    }

    /**
     * Get the latest remark.
     */
    public function latestRemark(): HasOne
    {
        return $this->hasOne(ExamRemark::class)->latest();
    }

    /**
     * Scope a query to only include published results.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope a query to only include pending results.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include passed results.
     */
    public function scopePassed($query)
    {
        return $query->where('status', self::STATUS_PASSED);
    }

    /**
     * Scope a query to only include failed results.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Get the status badge class.
     */
    public function getStatusBadgeAttribute(): string
    {
        $classes = [
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_PASSED => 'bg-green-100 text-green-800',
            self::STATUS_FAILED => 'bg-red-100 text-red-800',
            self::STATUS_ABSENT => 'bg-gray-100 text-gray-800',
            self::STATUS_MALPRACTICE => 'bg-purple-100 text-purple-800',
        ];

        return $classes[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        $statuses = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PASSED => 'Passed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_ABSENT => 'Absent',
            self::STATUS_MALPRACTICE => 'Malpractice',
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }

    /**
     * Calculate the result status based on obtained marks and passing marks.
     */
    public function calculateStatus(): string
    {
        if ($this->status === self::STATUS_ABSENT || $this->status === self::STATUS_MALPRACTICE) {
            return $this->status;
        }

        $passingMarks = $this->exam->passing_marks;
        
        if ($this->obtained_marks >= $passingMarks) {
            return self::STATUS_PASSED;
        }
        
        return self::STATUS_FAILED;
    }

    /**
     * Publish the result.
     */
    public function publish($staffId, string $remarks = null): bool
    {
        return $this->update([
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $staffId,
            'publish_remarks' => $remarks,
        ]);
    }

    /**
     * Unpublish the result.
     */
    public function unpublish($staffId, string $remarks = null): bool
    {
        return $this->update([
            'is_published' => false,
            'published_at' => null,
            'published_by' => null,
            'unpublish_remarks' => $remarks,
            'unpublished_by' => $staffId,
            'unpublished_at' => now(),
        ]);
    }
}
