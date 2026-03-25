<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Fee extends Model
{
    use SoftDeletes;

    // Fee types
    public const TYPE_TUITION = 'tuition';
    public const TYPE_ADMISSION = 'admission';
    public const TYPE_EXAM = 'exam';
    public const TYPE_TRANSPORT = 'transport';
    public const TYPE_LIBRARY = 'library';
    public const TYPE_UNIFORM = 'uniform';
    public const TYPE_OTHER = 'other';

    // Fee frequencies
    public const FREQUENCY_ONE_TIME = 'one_time';
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_QUARTERLY = 'quarterly';
    public const FREQUENCY_HALF_YEARLY = 'half_yearly';
    public const FREQUENCY_YEARLY = 'yearly';

    // Statuses
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'code',
        'description',
        'class_id',
        'section_id',
        'student_id',
        'amount',
        'fee_type',
        'frequency',
        'start_date',
        'end_date',
        'fine_amount',
        'fine_type',
        'fine_grace_days',
        'discount_amount',
        'discount_type',
        'status',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fine_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'metadata' => 'array',
    ];

    protected $appends = [
        'formatted_amount',
        'formatted_fine_amount',
        'formatted_discount_amount',
        'status_badge',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($fee) {
            if (auth()->check()) {
                $fee->created_by = $fee->created_by ?? auth()->id();
            }
        });
    }

    /**
     * Get the school class that owns the fee.
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the section that owns the fee.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the student that owns the fee.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the user who created the fee.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the fee payments for the fee.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    /**
     * Get the formatted amount attribute.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2);
    }

    /**
     * Get the formatted fine amount attribute.
     */
    public function getFormattedFineAmountAttribute(): string
    {
        return number_format($this->fine_amount, 2);
    }

    /**
     * Get the formatted discount amount attribute.
     */
    public function getFormattedDiscountAmountAttribute(): string
    {
        return number_format($this->discount_amount, 2);
    }

    /**
     * Get the status badge HTML.
     */
    public function getStatusBadgeAttribute(): string
    {
        $statuses = [
            self::STATUS_ACTIVE => 'success',
            self::STATUS_INACTIVE => 'secondary',
            self::STATUS_ARCHIVED => 'danger',
        ];

        $color = $statuses[$this->status] ?? 'secondary';
        return "<span class='badge bg-{$color}'>{$this->status}</span>";
    }

    /**
     * Scope a query to only include active fees.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Get all fee types.
     */
    public static function getFeeTypes(): array
    {
        return [
            self::TYPE_TUITION => 'Tuition Fee',
            self::TYPE_ADMISSION => 'Admission Fee',
            self::TYPE_EXAM => 'Exam Fee',
            self::TYPE_TRANSPORT => 'Transport Fee',
            self::TYPE_LIBRARY => 'Library Fee',
            self::TYPE_UNIFORM => 'Uniform Fee',
            self::TYPE_OTHER => 'Other Fee',
        ];
    }

    /**
     * Get all fee frequencies.
     */
    public static function getFrequencies(): array
    {
        return [
            self::FREQUENCY_ONE_TIME => 'One Time',
            self::FREQUENCY_DAILY => 'Daily',
            self::FREQUENCY_WEEKLY => 'Weekly',
            self::FREQUENCY_MONTHLY => 'Monthly',
            self::FREQUENCY_QUARTERLY => 'Quarterly',
            self::FREQUENCY_HALF_YEARLY => 'Half Yearly',
            self::FREQUENCY_YEARLY => 'Yearly',
        ];
    }
}
