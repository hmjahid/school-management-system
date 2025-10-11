<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Admission extends Model
{
    use SoftDeletes;

    // Admission statuses
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_WAITLISTED = 'waitlisted';
    public const STATUS_ENROLLED = 'enrolled';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'application_number',
        'academic_session_id',
        'batch_id',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'blood_group',
        'religion',
        'nationality',
        'photo',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'father_name',
        'father_phone',
        'father_occupation',
        'mother_name',
        'mother_phone',
        'mother_occupation',
        'guardian_name',
        'guardian_relation',
        'guardian_phone',
        'previous_school',
        'previous_class',
        'previous_grade',
        'transfer_certificate',
        'birth_certificate',
        'other_documents',
        'status',
        'rejection_reason',
        'admission_date',
        'admission_notes',
        'created_by',
        'updated_by',
        'metadata',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
        'other_documents' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = [
        'full_name',
        'status_label',
        'status_badge',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($admission) {
            if (empty($admission->application_number)) {
                $admission->application_number = static::generateApplicationNumber();
            }
            
            if (auth()->check()) {
                $admission->created_by = auth()->id();
                $admission->updated_by = auth()->id();
            }
        });

        static::updating(function ($admission) {
            if (auth()->check()) {
                $admission->updated_by = auth()->id();
            }
        });
    }

    /**
     * Generate a unique application number.
     */
    public static function generateApplicationNumber(): string
    {
        $prefix = 'APP' . date('Y');
        $lastApp = static::where('application_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastApp 
            ? (int) substr($lastApp->application_number, 7) + 1 
            : 1;

        return $prefix . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get the academic session of the admission.
     */
    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    /**
     * Get the batch of the admission.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the user who created the admission.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the admission.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the admission documents.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(AdmissionDocument::class);
    }

    /**
     * Get the admission payment.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'admission_id');
    }

    /**
     * Get the student record if enrolled.
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'admission_id');
    }

    /**
     * Get the admission's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * Get the status badge class.
     */
    public function getStatusBadgeAttribute(): string
    {
        $classes = [
            self::STATUS_DRAFT => 'bg-gray-100 text-gray-800',
            self::STATUS_SUBMITTED => 'bg-blue-100 text-blue-800',
            self::STATUS_UNDER_REVIEW => 'bg-yellow-100 text-yellow-800',
            self::STATUS_APPROVED => 'bg-green-100 text-green-800',
            self::STATUS_REJECTED => 'bg-red-100 text-red-800',
            self::STATUS_WAITLISTED => 'bg-purple-100 text-purple-800',
            self::STATUS_ENROLLED => 'bg-indigo-100 text-indigo-800',
            self::STATUS_CANCELLED => 'bg-gray-200 text-gray-800',
        ];

        return $classes[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Check if the admission is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the admission is submitted.
     */
    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    /**
     * Check if the admission is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if the admission is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if the admission is enrolled.
     */
    public function isEnrolled(): bool
    {
        return $this->status === self::STATUS_ENROLLED;
    }

    /**
     * Submit the admission.
     */
    public function submit(): bool
    {
        if ($this->isDraft()) {
            $this->status = self::STATUS_SUBMITTED;
            $this->submitted_at = now();
            return $this->save();
        }
        
        return false;
    }

    /**
     * Approve the admission.
     */
    public function approve(string $notes = null): bool
    {
        if ($this->isSubmitted() || $this->isUnderReview()) {
            $this->status = self::STATUS_APPROVED;
            $this->admission_date = now();
            $this->admission_notes = $notes;
            $this->approved_by = auth()->id();
            $this->approved_at = now();
            return $this->save();
        }
        
        return false;
    }

    /**
     * Reject the admission.
     */
    public function reject(string $reason): bool
    {
        if ($this->isSubmitted() || $this->isUnderReview()) {
            $this->status = self::STATUS_REJECTED;
            $this->rejection_reason = $reason;
            $this->rejected_by = auth()->id();
            $this->rejected_at = now();
            return $this->save();
        }
        
        return false;
    }

    /**
     * Enroll the student.
     */
    public function enroll(array $studentData = []): ?Student
    {
        if (!$this->isApproved() || $this->isEnrolled()) {
            return null;
        }

        // Create student record
        $student = Student::create([
            'admission_id' => $this->id,
            'admission_date' => $this->admission_date,
            'roll_number' => Student::generateRollNumber($this->batch_id),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'blood_group' => $this->blood_group,
            'religion' => $this->religion,
            'nationality' => $this->nationality,
            'photo' => $this->photo,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'father_name' => $this->father_name,
            'father_phone' => $this->father_phone,
            'father_occupation' => $this->father_occupation,
            'mother_name' => $this->mother_name,
            'mother_phone' => $this->mother_phone,
            'mother_occupation' => $this->mother_occupation,
            'guardian_name' => $this->guardian_name,
            'guardian_relation' => $this->guardian_relation,
            'guardian_phone' => $this->guardian_phone,
            'previous_school' => $this->previous_school,
            'previous_class' => $this->previous_class,
            'previous_grade' => $this->previous_grade,
            'status' => Student::STATUS_ACTIVE,
            'metadata' => $studentData,
            'created_by' => $this->created_by,
            'updated_by' => auth()->id(),
        ]);

        if ($student) {
            $this->status = self::STATUS_ENROLLED;
            $this->enrolled_at = now();
            $this->save();

            // Create user account for the student
            $user = User::create([
                'name' => $this->full_name,
                'email' => $this->email,
                'password' => bcrypt(Str::random(10)), // Random password, will need to be reset
                'status' => User::STATUS_ACTIVE,
            ]);

            if ($user) {
                $user->assignRole('student');
                $student->update(['user_id' => $user->id]);
            }

            // TODO: Send welcome email with password reset link
        }

        return $student;
    }
}
