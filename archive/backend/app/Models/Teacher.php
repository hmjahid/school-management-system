<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{

    protected $fillable = [
        'user_id',
        'employee_id',
        'qualification',
        'gender',
        'blood_group',
        'date_of_birth',
        'religion',
        'nationality',
        'phone',
        'emergency_contact',
        'present_address',
        'permanent_address',
        'city',
        'state',
        'zip_code',
        'country',
        'joining_date',
        'leaving_date',
        'status',
        'bank_name',
        'bank_account_number',
        'bank_branch',
        'salary',
        'salary_type',
        'nid_number',
        'passport_number',
        'driving_license',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'leaving_date' => 'date',
        'salary' => 'decimal:2',
    ];

    /**
     * Get the user that owns the teacher.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The classes that belong to the teacher.
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_teacher', 'teacher_id', 'class_id')
            ->withTimestamps()
            ->withPivot(['is_class_teacher', 'academic_session_id']);
    }

    /**
     * The sections that belong to the teacher.
     */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'section_teacher', 'teacher_id', 'section_id')
            ->withTimestamps()
            ->withPivot(['academic_session_id']);
    }

    /**
     * The subjects that belong to the teacher.
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_teacher', 'teacher_id', 'subject_id')
            ->withTimestamps()
            ->withPivot(['class_id', 'section_id', 'academic_session_id']);
    }

    /**
     * Get the class teacher assignments for the teacher.
     */
    public function classTeacherAssignments(): HasMany
    {
        return $this->hasMany(ClassTeacher::class);
    }

    /**
     * Get the attendances for the teacher.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(TeacherAttendance::class);
    }

    /**
     * Get the leaves for the teacher.
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(TeacherLeave::class);
    }

    /**
     * Get the salary payments for the teacher.
     */
    public function salaryPayments(): HasMany
    {
        return $this->hasMany(TeacherSalaryPayment::class);
    }

    /**
     * Get the teacher's full address.
     */
    public function getFullAddressAttribute(): string
    {
        $address = [
            $this->present_address,
            $this->city,
            $this->state,
            $this->zip_code,
            $this->country
        ];

        return implode(', ', array_filter($address));
    }

    /**
     * Get the teacher's current status with badge.
     */
    public function getStatusBadgeAttribute(): string
    {
        $statuses = [
            'active' => 'success',
            'inactive' => 'secondary',
            'on_leave' => 'warning',
            'retired' => 'dark'
        ];

        $color = $statuses[$this->status] ?? 'secondary';
        return "<span class='badge bg-{$color}'>" . ucfirst($this->status) . "</span>";
    }

    /**
     * Check if the teacher is class teacher of a specific class.
     */
    public function isClassTeacher(SchoolClass $class, $academicSessionId = null): bool
    {
        $query = $this->classes()
            ->where('class_id', $class->id)
            ->wherePivot('is_class_teacher', true);

        if ($academicSessionId) {
            $query->wherePivot('academic_session_id', $academicSessionId);
        }

        return $query->exists();
    }

    /**
     * Get the teacher's current class assignments.
     */
    public function getCurrentClassAssignments($academicSessionId = null)
    {
        return $this->classes()
            ->when($academicSessionId, function ($query) use ($academicSessionId) {
                $query->wherePivot('academic_session_id', $academicSessionId);
            })
            ->withPivot(['is_class_teacher', 'academic_session_id'])
            ->get();
    }
}
