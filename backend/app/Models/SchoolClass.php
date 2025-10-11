<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SchoolClass extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'grade_level',
        'academic_session_id',
        'class_teacher_id',
        'max_students',
        'is_active',
        'monthly_fee',
        'admission_fee',
        'exam_fee',
        'other_fees',
        'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'monthly_fee' => 'decimal:2',
        'admission_fee' => 'decimal:2',
        'exam_fee' => 'decimal:2',
        'other_fees' => 'decimal:2',
    ];

    /**
     * Get the sections for the class.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'class_id');
    }

    /**
     * Get the students in this class.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    /**
     * The teachers that belong to the class.
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'class_teacher', 'class_id', 'teacher_id')
            ->withPivot(['is_class_teacher', 'academic_session_id'])
            ->withTimestamps();
    }

    /**
     * The subjects that belong to the class.
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'class_subject', 'class_id', 'subject_id')
            ->withPivot(['academic_session_id', 'teacher_id'])
            ->withTimestamps();
    }

    /**
     * Get the class teacher for the class.
     */
    public function classTeacher()
    {
        return $this->belongsTo(Teacher::class, 'class_teacher_id');
    }

    /**
     * Get the academic session for the class.
     */
    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    /**
     * Get the attendances for the class.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'class_id');
    }

    /**
     * Get the exams for the class.
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'class_id');
    }

    /**
     * Get the fees for the class.
     */
    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class, 'class_id');
    }

    /**
     * Get the notices for the class.
     */
    public function notices(): HasMany
    {
        return $this->hasMany(Notice::class, 'class_id');
    }

    /**
     * Get the total number of students in the class.
     */
    public function getTotalStudentsAttribute(): int
    {
        return $this->students()->count();
    }

    /**
     * Get the total number of sections in the class.
     */
    public function getTotalSectionsAttribute(): int
    {
        return $this->sections()->count();
    }

    /**
     * Get the total number of teachers assigned to the class.
     */
    public function getTotalTeachersAttribute(): int
    {
        return $this->teachers()->count();
    }

    /**
     * Get the total number of subjects in the class.
     */
    public function getTotalSubjectsAttribute(): int
    {
        return $this->subjects()->count();
    }

    /**
     * Get the status badge for the class.
     */
    public function getStatusBadgeAttribute(): string
    {
        $status = $this->is_active ? 'active' : 'inactive';
        $color = $this->is_active ? 'success' : 'secondary';
        return "<span class='badge bg-{$color}'>" . ucfirst($status) . "</span>";
    }

    /**
     * Scope a query to only include active classes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive classes.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Get the class name with code.
     */
    public function getNameWithCodeAttribute(): string
    {
        return "{$this->name} ({$this->code})";
    }

    /**
     * Get the class name with grade level.
     */
    public function getNameWithGradeAttribute(): string
    {
        return "Grade {$this->grade_level} - {$this->name}";
    }
}
