<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Batch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'start_date',
        'end_date',
        'academic_session_id',
        'course_id',
        'max_students',
        'fee_amount',
        'is_active',
        'is_featured',
        'registration_starts_at',
        'registration_ends_at',
        'class_days',
        'class_timing',
        'venue',
        'teacher_id',
        'assistant_teacher_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_starts_at' => 'datetime',
        'registration_ends_at' => 'datetime',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'max_students' => 'integer',
        'fee_amount' => 'decimal:2',
        'class_days' => 'array',
        'class_timing' => 'array',
    ];

    protected $appends = [
        'duration_weeks',
        'is_registration_open',
        'available_seats',
        'student_count',
        'progress_percentage',
        'status_badge',
    ];

    // Status constants
    public const STATUS_UPCOMING = 'upcoming';
    public const STATUS_ONGOING = 'ongoing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('is_active', true);
        });
    }

    /**
     * Get the academic session that owns the batch.
     */
    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    /**
     * Get the course that owns the batch.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Get the main teacher for the batch.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    /**
     * Get the assistant teacher for the batch.
     */
    public function assistantTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'assistant_teacher_id');
    }

    /**
     * The students that belong to the batch.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'batch_student', 'batch_id', 'student_id')
            ->withPivot([
                'enrollment_date',
                'completion_date',
                'status',
                'fee_amount',
                'discount_amount',
                'final_fee_amount',
                'payment_status',
                'attendance_percentage',
                'created_by',
                'notes',
            ])
            ->withTimestamps();
    }

    /**
     * Get the attendances for the batch.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get the exams for the batch.
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Get the study materials for the batch.
     */
    public function studyMaterials(): HasMany
    {
        return $this->hasMany(StudyMaterial::class);
    }

    /**
     * Get the assignments for the batch.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * Get the notices for the batch.
     */
    public function notices(): HasMany
    {
        return $this->hasMany(Notice::class);
    }

    /**
     * Get the payments for the batch.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the duration of the batch in weeks.
     */
    public function getDurationWeeksAttribute(): ?int
    {
        if (!$this->start_date || !$this->end_date) {
            return null;
        }

        return $this->start_date->diffInWeeks($this->end_date);
    }

    /**
     * Check if registration is open for this batch.
     */
    public function getIsRegistrationOpenAttribute(): bool
    {
        $now = now();
        
        return $this->is_active 
            && $this->registration_starts_at 
            && $this->registration_ends_at
            && $now->between(
                $this->registration_starts_at, 
                $this->registration_ends_at
            );
    }

    /**
     * Get the number of available seats in the batch.
     */
    public function getAvailableSeatsAttribute(): ?int
    {
        if ($this->max_students === null) {
            return null;
        }

        return max(0, $this->max_students - $this->students()->count());
    }

    /**
     * Get the number of students in the batch.
     */
    public function getStudentCountAttribute(): int
    {
        if (isset($this->students_count)) {
            return $this->students_count;
        }

        return $this->students()->count();
    }

    /**
     * Get the progress percentage of the batch.
     */
    public function getProgressPercentageAttribute(): ?float
    {
        if (!$this->start_date || !$this->end_date) {
            return null;
        }

        $totalDays = $this->start_date->diffInDays($this->end_date);
        $elapsedDays = $this->start_date->diffInDays(now());
        
        return min(100, max(0, ($elapsedDays / $totalDays) * 100));
    }

    /**
     * Get the status badge for the batch.
     */
    public function getStatusBadgeAttribute(): string
    {
        $status = $this->status ?? 'draft';
        $badgeClass = [
            'upcoming' => 'info',
            'ongoing' => 'success',
            'completed' => 'primary',
            'cancelled' => 'danger',
            'draft' => 'secondary',
        ][$status] ?? 'secondary';

        return "<span class='badge bg-{$badgeClass}'>" . ucfirst($status) . "</span>";
    }

    /**
     * Scope a query to only include active batches.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include featured batches.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include batches with available seats.
     */
    public function scopeWithAvailableSeats(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('max_students')
              ->orWhereRaw('max_students > (SELECT COUNT(*) FROM batch_student WHERE batch_student.batch_id = batches.id)');
        });
    }

    /**
     * Scope a query to only include batches with registration open.
     */
    public function scopeWithRegistrationOpen(Builder $query): Builder
    {
        $now = now();
        
        return $query->where('is_active', true)
            ->whereNotNull('registration_starts_at')
            ->whereNotNull('registration_ends_at')
            ->where('registration_starts_at', '<=', $now)
            ->where('registration_ends_at', '>=', $now);
    }

    /**
     * Scope a query to only include batches for a specific course.
     */
    public function scopeForCourse(Builder $query, $courseId): Builder
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope a query to only include batches for a specific teacher.
     */
    public function scopeTaughtBy(Builder $query, $teacherId): Builder
    {
        return $query->where(function ($q) use ($teacherId) {
            $q->where('teacher_id', $teacherId)
              ->orWhere('assistant_teacher_id', $teacherId);
        });
    }

    /**
     * Get the current status of the batch based on dates.
     */
    public function getCurrentStatus(): string
    {
        $now = now();
        
        if ($this->status === self::STATUS_CANCELLED) {
            return self::STATUS_CANCELLED;
        }
        
        if ($this->end_date && $this->end_date->isPast()) {
            return self::STATUS_COMPLETED;
        }
        
        if ($this->start_date && $this->start_date->isFuture()) {
            return self::STATUS_UPCOMING;
        }
        
        if ($this->start_date && $this->start_date->isPast() && 
            (!$this->end_date || $this->end_date->isFuture())) {
            return self::STATUS_ONGOING;
        }
        
        return 'unknown';
    }

    /**
     * Check if the batch is full.
     */
    public function isFull(): bool
    {
        if ($this->max_students === null) {
            return false;
        }
        
        return $this->students_count >= $this->max_students;
    }

    /**
     * Check if a student is enrolled in this batch.
     */
    public function hasStudent($studentId): bool
    {
        return $this->students()->where('student_id', $studentId)->exists();
    }

    /**
     * Get the batch schedule as a human-readable string.
     */
    public function getScheduleString(): string
    {
        if (empty($this->class_days) || empty($this->class_timing)) {
            return 'Schedule not set';
        }

        $days = collect($this->class_days)->map(function ($day) {
            return ucfirst($day);
        })->join(', ');

        $timing = '';
        if (isset($this->class_timing['start_time'], $this->class_timing['end_time'])) {
            $timing = ' from ' . $this->class_timing['start_time'] . ' to ' . $this->class_timing['end_time'];
        }

        return $days . $timing;
    }
}
