<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Section extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'class_id',
        'teacher_id',
        'academic_session_id',
        'capacity',
        'room_number',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
        'academic_session_id' => 'integer',
    ];

    /**
     * Get the class that owns the section.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the class teacher for the section.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    /**
     * Get the academic session for the section.
     */
    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    /**
     * Get the students for the section.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * The teachers who teach in this section.
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'section_teacher', 'section_id', 'teacher_id')
            ->withPivot(['subject_id', 'academic_session_id', 'is_class_teacher'])
            ->withTimestamps();
    }

    /**
     * The subjects taught in this section.
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'section_subject', 'section_id', 'subject_id')
            ->withPivot(['teacher_id', 'academic_session_id'])
            ->withTimestamps();
    }

    /**
     * Get the attendances for the section.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get the timetable slots for the section.
     */
    public function timetableSlots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class);
    }

    /**
     * Get the exams for the section.
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Get the exam results for the section.
     */
    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * Get the notices for the section.
     */
    public function notices(): HasMany
    {
        return $this->hasMany(Notice::class);
    }

    /**
     * Get the status badge for the section.
     */
    public function getStatusBadgeAttribute(): string
    {
        $status = $this->is_active ? 'active' : 'inactive';
        $color = $this->is_active ? 'success' : 'secondary';
        return "<span class='badge bg-{$color}'>" . ucfirst($status) . "</span>";
    }

    /**
     * Get the full section name with class.
     */
    public function getFullNameAttribute(): string
    {
        return $this->class ? $this->class->name . ' - ' . $this->name : $this->name;
    }

    /**
     * Get the current student count.
     */
    public function getStudentCountAttribute(): int
    {
        return $this->students()->count();
    }

    /**
     * Get the available seats.
     */
    public function getAvailableSeatsAttribute(): int
    {
        return max(0, $this->capacity - $this->student_count);
    }

    /**
     * Check if the section has available seats.
     */
    public function getHasAvailableSeatsAttribute(): bool
    {
        return $this->available_seats > 0;
    }

    /**
     * Get the attendance percentage for the section.
     */
    public function getAttendancePercentageAttribute(): ?float
    {
        // TODO: Implement attendance percentage calculation
        return null;
    }

    /**
     * Scope a query to only include active sections.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include sections of a specific class.
     */
    public function scopeOfClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Scope a query to only include sections with available seats.
     */
    public function scopeWithAvailableSeats($query)
    {
        return $query->whereRaw('capacity > (SELECT COUNT(*) FROM students WHERE section_id = sections.id)');
    }

    /**
     * Scope a query to only include sections taught by a specific teacher.
     */
    public function scopeTaughtBy($query, $teacherId)
    {
        return $query->whereHas('teachers', function ($q) use ($teacherId) {
            $q->where('teacher_id', $teacherId);
        });
    }

    /**
     * Get the class teacher name.
     */
    public function getClassTeacherNameAttribute(): ?string
    {
        return $this->teacher ? $this->teacher->user->name : null;
    }

    /**
     * Get the academic session name.
     */
    public function getAcademicSessionNameAttribute(): ?string
    {
        return $this->academicSession ? $this->academicSession->name : null;
    }

    /**
     * Get the class name.
     */
    public function getClassNameAttribute(): ?string
    {
        return $this->class ? $this->class->name : null;
    }
}
