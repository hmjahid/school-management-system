<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guardian extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'occupation',
        'company',
        'nid_number',
        'passport_number',
        'driving_license',
        'nationality',
        'religion',
        'blood_group',
        'present_address',
        'permanent_address',
        'city',
        'state',
        'zip_code',
        'country',
        'phone',
        'office_phone',
        'emergency_contact',
        'relationship',
        'is_primary',
        'monthly_income',
        'education_level',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'monthly_income' => 'decimal:2',
    ];

    /**
     * Get the user that owns the guardian.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the students for the guardian.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'guardian_student', 'guardian_id', 'student_id')
            ->withPivot(['relationship', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Get the primary students for the guardian.
     */
    public function primaryStudents()
    {
        return $this->belongsToMany(Student::class, 'guardian_student', 'guardian_id', 'student_id')
            ->wherePivot('is_primary', true);
    }

    /**
     * Get the secondary students for the guardian.
     */
    public function secondaryStudents()
    {
        return $this->belongsToMany(Student::class, 'guardian_student', 'guardian_id', 'student_id')
            ->wherePivot('is_primary', false);
    }

    /**
     * Get the attendances for the guardian's students.
     */
    public function studentAttendances()
    {
        return $this->hasManyThrough(
            Attendance::class,
            'App\Models\GuardianStudent',
            'guardian_id',
            'student_id',
            'id',
            'student_id'
        );
    }

    /**
     * Get the exam results for the guardian's students.
     */
    public function studentExamResults()
    {
        return $this->hasManyThrough(
            ExamResult::class,
            'App\Models\GuardianStudent',
            'guardian_id',
            'student_id',
            'id',
            'student_id'
        );
    }

    /**
     * Get the fee payments made by the guardian.
     */
    public function feePayments(): HasMany
    {
        return $this->hasMany(FeePayment::class, 'paid_by');
    }

    /**
     * Get the invoices for the guardian's students.
     */
    public function studentInvoices()
    {
        return $this->hasManyThrough(
            Invoice::class,
            'App\Models\GuardianStudent',
            'guardian_id',
            'student_id',
            'id',
            'student_id'
        );
    }

    /**
     * Get the notices for the guardian's students.
     */
    public function studentNotices()
    {
        return $this->hasManyThrough(
            Notice::class,
            'App\Models\GuardianStudent',
            'guardian_id',
            'student_id',
            'id',
            'student_id'
        );
    }

    /**
     * Get the full address of the guardian.
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
     * Get the status badge for the guardian.
     */
    public function getStatusBadgeAttribute(): string
    {
        $status = $this->is_active ? 'active' : 'inactive';
        $color = $this->is_active ? 'success' : 'secondary';
        return "<span class='badge bg-{$color}'>" . ucfirst($status) . "</span>";
    }

    /**
     * Get the primary contact information.
     */
    public function getPrimaryContactAttribute(): array
    {
        return [
            'phone' => $this->phone,
            'email' => $this->user->email,
            'address' => $this->full_address,
        ];
    }

    /**
     * Get the emergency contact information.
     */
    public function getEmergencyContactAttribute(): array
    {
        return [
            'name' => $this->user->name,
            'relationship' => $this->relationship,
            'phone' => $this->emergency_contact,
            'email' => $this->user->email,
        ];
    }

    /**
     * Get the total number of students under this guardian.
     */
    public function getTotalStudentsAttribute(): int
    {
        return $this->students()->count();
    }

    /**
     * Get the total outstanding balance across all students.
     */
    public function getTotalOutstandingBalanceAttribute()
    {
        // TODO: Implement outstanding balance calculation
        return 0;
    }

    /**
     * Get the total paid amount across all students.
     */
    public function getTotalPaidAmountAttribute()
    {
        // TODO: Implement paid amount calculation
        return 0;
    }

    /**
     * Scope a query to only include active guardians.
     */
    public function scopeActive($query)
    {
        return $query->whereHas('user', function($q) {
            $q->where('is_active', true);
        });
    }

    /**
     * Scope a query to only include guardians with primary students.
     */
    public function scopeWithPrimaryStudents($query)
    {
        return $query->whereHas('primaryStudents');
    }

    /**
     * Scope a query to only include guardians with secondary students.
     */
    public function scopeWithSecondaryStudents($query)
    {
        return $query->whereHas('secondaryStudents');
    }
}
