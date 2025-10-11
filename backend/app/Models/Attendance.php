<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attendance extends Model
{
    use SoftDeletes;

    // Attendance status constants
    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LATE = 'late';
    public const STATUS_HALF_DAY = 'half_day';
    public const STATUS_HOLIDAY = 'holiday';
    public const STATUS_LEAVE = 'on_leave';

    // Attendance types
    public const TYPE_DAILY = 'daily';
    public const TYPE_SUBJECT_WISE = 'subject_wise';
    public const TYPE_SPECIAL_EVENT = 'special_event';

    protected $fillable = [
        'date',
        'status',
        'type',
        'batch_id',
        'section_id',
        'subject_id',
        'student_id',
        'teacher_id',
        'academic_session_id',
        'period',
        'remarks',
        'recorded_by',
        'updated_by',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'metadata' => 'array',
    ];

    protected $appends = [
        'status_badge',
        'is_present',
        'is_absent',
        'is_late',
        'is_half_day',
    ];

    /**
     * Get the attendanceable model (Batch or Section).
     */
    public function attendanceable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the batch that owns the attendance.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the section that owns the attendance.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the subject that owns the attendance.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the student that owns the attendance.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the teacher that owns the attendance.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get the academic session that owns the attendance.
     */
    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    /**
     * Get the staff who recorded the attendance.
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Get the staff who last updated the attendance.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the status badge HTML.
     */
    public function getStatusBadgeAttribute(): string
    {
        $badgeClasses = [
            self::STATUS_PRESENT => 'success',
            self::STATUS_ABSENT => 'danger',
            self::STATUS_LATE => 'warning',
            self::STATUS_HALF_DAY => 'info',
            self::STATUS_HOLIDAY => 'secondary',
            self::STATUS_LEAVE => 'primary',
        ];

        $status = $this->status ?? self::STATUS_ABSENT;
        $badgeClass = $badgeClasses[$status] ?? 'secondary';
        $statusLabel = str_replace('_', ' ', ucfirst($status));

        return "<span class='badge bg-{$badgeClass}'>{$statusLabel}</span>";
    }

    /**
     * Check if the attendance status is present.
     */
    public function getIsPresentAttribute(): bool
    {
        return $this->status === self::STATUS_PRESENT;
    }

    /**
     * Check if the attendance status is absent.
     */
    public function getIsAbsentAttribute(): bool
    {
        return $this->status === self::STATUS_ABSENT;
    }

    /**
     * Check if the attendance status is late.
     */
    public function getIsLateAttribute(): bool
    {
        return $this->status === self::STATUS_LATE;
    }

    /**
     * Check if the attendance status is half day.
     */
    public function getIsHalfDayAttribute(): bool
    {
        return $this->status === self::STATUS_HALF_DAY;
    }

    /**
     * Scope a query to only include attendance for a specific date range.
     */
    public function scopeDateRange($query, $startDate, $endDate = null)
    {
        $endDate = $endDate ?? $startDate;
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include attendance for a specific status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include attendance for a specific type.
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include attendance for a specific batch.
     */
    public function scopeForBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope a query to only include attendance for a specific section.
     */
    public function scopeForSection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    /**
     * Scope a query to only include attendance for a specific subject.
     */
    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    /**
     * Scope a query to only include attendance for a specific student.
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope a query to only include attendance for a specific teacher.
     */
    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Get all possible attendance statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PRESENT => 'Present',
            self::STATUS_ABSENT => 'Absent',
            self::STATUS_LATE => 'Late',
            self::STATUS_HALF_DAY => 'Half Day',
            self::STATUS_HOLIDAY => 'Holiday',
            self::STATUS_LEAVE => 'On Leave',
        ];
    }

    /**
     * Get all possible attendance types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_DAILY => 'Daily',
            self::TYPE_SUBJECT_WISE => 'Subject Wise',
            self::TYPE_SPECIAL_EVENT => 'Special Event',
        ];
    }

    /**
     * Get the attendance percentage for a student in a batch/section.
     */
    public static function getStudentAttendancePercentage($studentId, $batchId = null, $sectionId = null, $startDate = null, $endDate = null): float
    {
        $query = self::where('student_id', $studentId);

        if ($batchId) {
            $query->where('batch_id', $batchId);
        }

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $total = $query->count();
        $present = $query->clone()
            ->whereIn('status', [self::STATUS_PRESENT, self::STATUS_LATE, self::STATUS_HALF_DAY])
            ->count();

        return $total > 0 ? round(($present / $total) * 100, 2) : 0;
    }

    /**
     * Get the attendance summary for a batch/section.
     */
    public static function getAttendanceSummary($batchId = null, $sectionId = null, $startDate = null, $endDate = null): array
    {
        $query = self::query();

        if ($batchId) {
            $query->where('batch_id', $batchId);
        }

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $total = $query->count();
        $present = $query->clone()->whereIn('status', [self::STATUS_PRESENT, self::STATUS_LATE, self::STATUS_HALF_DAY])->count();
        $absent = $query->clone()->where('status', self::STATUS_ABSENT)->count();
        $late = $query->clone()->where('status', self::STATUS_LATE)->count();
        $halfDay = $query->clone()->where('status', self::STATUS_HALF_DAY)->count();
        $onLeave = $query->clone()->where('status', self::STATUS_LEAVE)->count();
        $holiday = $query->clone()->where('status', self::STATUS_HOLIDAY)->count();

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'half_day' => $halfDay,
            'on_leave' => $onLeave,
            'holiday' => $holiday,
            'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
        ];
    }
}
