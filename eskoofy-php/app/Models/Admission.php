<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Admission extends Model
{
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

    public function student()
    {
        return $this->hasOne(Student::class, 'admission_id');
    }
}
