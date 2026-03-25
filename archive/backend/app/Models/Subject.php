<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'short_name',
        'credit_hours',
        'description',
        'is_active',
        'is_elective',
        'has_lab',
        'theory_marks',
        'practical_marks',
        'passing_marks',
        'max_class_per_week',
        'priority',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_elective' => 'boolean',
        'has_lab' => 'boolean',
        'credit_hours' => 'float',
        'theory_marks' => 'float',
        'practical_marks' => 'float',
        'passing_marks' => 'float',
        'max_class_per_week' => 'integer',
        'priority' => 'integer',
    ];

    /**
     * The classes that have this subject.
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_subject', 'subject_id', 'class_id')
            ->withPivot(['teacher_id', 'academic_session_id', 'is_compulsory', 'max_weekly_classes'])
            ->withTimestamps();
    }

    /**
     * The teachers who teach this subject.
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'class_subject_teacher', 'subject_id', 'teacher_id')
            ->withPivot(['class_id', 'academic_session_id', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * The exams associated with this subject.
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * The exam results for this subject.
     */
    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * The attendances for this subject.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * The study materials for this subject.
     */
    public function studyMaterials(): HasMany
    {
        return $this->hasMany(StudyMaterial::class);
    }

    /**
     * The assignments for this subject.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * The syllabus for this subject.
     */
    public function syllabus(): HasMany
    {
        return $this->hasMany(SubjectSyllabus::class);
    }

    /**
     * The timetable slots for this subject.
     */
    public function timetableSlots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class);
    }

    /**
     * Get the status badge for the subject.
     */
    public function getStatusBadgeAttribute(): string
    {
        $status = $this->is_active ? 'active' : 'inactive';
        $color = $this->is_active ? 'success' : 'secondary';
        return "<span class='badge bg-{$color}'>" . ucfirst($status) . "</span>";
    }

    /**
     * Get the total marks for the subject.
     */
    public function getTotalMarksAttribute(): float
    {
        return (float) ($this->theory_marks + $this->practical_marks);
    }

    /**
     * Get the type badge for the subject.
     */
    public function getTypeBadgeAttribute(): string
    {
        $types = [
            'theory' => 'primary',
            'practical' => 'info',
            'both' => 'success',
        ];

        $type = strtolower($this->type);
        $color = $types[$type] ?? 'secondary';
        
        return "<span class='badge bg-{$color}'>" . ucfirst($type) . "</span>";
    }

    /**
     * Scope a query to only include active subjects.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include elective subjects.
     */
    public function scopeElective($query)
    {
        return $query->where('is_elective', true);
    }

    /**
     * Scope a query to only include compulsory subjects.
     */
    public function scopeCompulsory($query)
    {
        return $query->where('is_elective', false);
    }

    /**
     * Scope a query to only include subjects with labs.
     */
    public function scopeWithLab($query)
    {
        return $query->where('has_lab', true);
    }

    /**
     * Scope a query to only include subjects of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the number of classes that have this subject.
     */
    public function getClassCountAttribute(): int
    {
        return $this->classes()->count();
    }

    /**
     * Get the number of teachers who teach this subject.
     */
    public function getTeacherCountAttribute(): int
    {
        return $this->teachers()->count();
    }

    /**
     * Get the number of students enrolled in this subject.
     */
    public function getStudentCountAttribute(): int
    {
        // TODO: Implement accurate student count based on class enrollments
        return 0;
    }
}
