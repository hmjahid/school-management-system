<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Admission extends Model
{

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_WAITLISTED = 'waitlisted';
    public const STATUS_ENROLLED = 'enrolled';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_SUBMITTED = 'submitted';
    public const PAYMENT_VERIFIED = 'verified';
    public const PAYMENT_REJECTED = 'rejected';

    public const PAYMENT_METHODS = ['bkash', 'nagad', 'rocket', 'bank', 'cash'];

    protected static string $table = 'admissions';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'application_number', 'academic_session_id', 'batch_id', 'first_name',
        'last_name', 'gender', 'date_of_birth', 'blood_group', 'religion',
        'nationality', 'photo', 'email', 'phone', 'address', 'city', 'state',
        'country', 'postal_code', 'father_name', 'father_phone',
        'father_occupation', 'mother_name', 'mother_phone', 'mother_occupation',
        'guardian_name', 'guardian_relation', 'guardian_phone',
        'previous_school', 'previous_class', 'previous_grade',
        'transfer_certificate', 'birth_certificate', 'other_documents',
        'status', 'rejection_reason', 'admission_date', 'admission_notes',
        'admission_fee', 'payment_number', 'transaction_id', 'payment_method',
        'payment_status', 'paid_at', 'verified_at', 'verified_by',
        'payment_note', 'created_by', 'updated_by', 'metadata', 'submitted_at',
    ];

    protected array $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
        'submitted_at' => 'datetime',
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'admission_fee' => 'float',
        'other_documents' => 'json',
        'metadata' => 'json',
    ];

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function documents()
    {
        return $this->hasMany(AdmissionDocument::class);
    }

    public function tests()
    {
        return $this->hasMany(AdmissionTest::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class, 'admission_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->attributes['first_name'] ?? '') . ' ' . ($this->attributes['last_name'] ?? ''));
    }

    public function getStatusLabelAttribute(): string
    {
        $status = $this->attributes['status'] ?? '';
        return $status !== '' ? ucwords(str_replace('_', ' ', $status)) : __('Unknown');
    }

    public function getStatusBadgeAttribute(): string
    {
        $classes = [
            self::STATUS_DRAFT         => 'bg-gray-100 text-gray-800',
            self::STATUS_SUBMITTED     => 'bg-blue-100 text-blue-800',
            self::STATUS_UNDER_REVIEW  => 'bg-yellow-100 text-yellow-800',
            self::STATUS_APPROVED      => 'bg-green-100 text-green-800',
            self::STATUS_REJECTED      => 'bg-red-100 text-red-800',
            self::STATUS_WAITLISTED    => 'bg-purple-100 text-purple-800',
            self::STATUS_ENROLLED      => 'bg-indigo-100 text-indigo-800',
            self::STATUS_CANCELLED     => 'bg-gray-200 text-gray-800',
        ];
        return $classes[$this->attributes['status'] ?? ''] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Most recent scheduled admission test (laravel parity: latestOfMany scheduled_at).
     */
    public function latestTest()
    {
        if (array_key_exists('latestTest', $this->relations)) {
            return $this->relations['latestTest'];
        }
        $test = null;
        try {
            $test = AdmissionTest::query()
                ->where('admission_id', $this->getKey())
                ->whereRaw('deleted_at IS NULL')
                ->orderByDesc('scheduled_at')
                ->first();
        } catch (\Throwable) {
            $test = null;
        }
        return new \App\Core\Relation(null, 'one', $test ? [$test] : []);
    }
}
