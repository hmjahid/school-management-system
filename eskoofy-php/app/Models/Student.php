<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Student extends Model
{
    protected static string $table = 'students';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'user_id', 'class_id', 'section_id', 'batch_id', 'guardian_id',
        'admission_id', 'admission_no', 'admission_number', 'roll_no',
        'admission_date', 'roll_number', 'first_name', 'last_name',
        'date_of_birth', 'gender', 'phone', 'address', 'postal_code',
        'blood_group', 'religion', 'nationality', 'nid_number',
        'birth_certificate_number', 'permanent_address', 'present_address',
        'city', 'state', 'zip_code', 'country', 'phone_1', 'phone_2',
        'email', 'parent_name', 'parent_phone', 'parent_email',
        'parent_occupation', 'parent_address', 'father_name', 'mother_name',
        'father_phone', 'mother_phone', 'father_occupation', 'mother_occupation',
        'monthly_fee', 'transport_fee', 'discount', 'status', 'notes',
        'is_notable', 'achievement',
    ];

    protected array $casts = [
        'admission_date' => 'date',
        'monthly_fee' => 'float',
        'transport_fee' => 'float',
        'discount' => 'float',
        'is_notable' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }
}
