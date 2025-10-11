<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'class_id',
        'section_id',
        'batch_id',
        'guardian_id',
        'admission_number',
        'admission_date',
        'roll_number',
        'blood_group',
        'religion',
        'nationality',
        'nid_number',
        'birth_certificate_number',
        'permanent_address',
        'present_address',
        'city',
        'state',
        'zip_code',
        'country',
        'phone_1',
        'phone_2',
        'email',
        'parent_name',
        'parent_phone',
        'parent_email',
        'parent_occupation',
        'parent_address',
        'monthly_fee',
        'transport_fee',
        'discount',
        'status',
        'notes',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'monthly_fee' => 'decimal:2',
        'transport_fee' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the student.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the class that owns the student.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the section that owns the student.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the batch that owns the student.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the guardian that owns the student.
     */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    /**
     * Get the attendances for the student.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get the exam results for the student.
     */
    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * Get the fee payments for the student.
     */
    public function feePayments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    /**
     * Get the invoices for the student.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the student's full address.
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
     * Get the student's current status.
     */
    public function getStatusBadgeAttribute(): string
    {
        $statuses = [
            'active' => 'success',
            'inactive' => 'secondary',
            'graduated' => 'info',
            'transferred' => 'warning'
        ];

        $color = $statuses[$this->status] ?? 'secondary';
        return "<span class='badge bg-{$color}'>{$this->status}</span>";
    }
}
