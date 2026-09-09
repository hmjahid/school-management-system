<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Teacher extends Model
{
    protected static string $table = 'teachers';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'user_id', 'employee_id', 'qualification', 'gender', 'blood_group',
        'date_of_birth', 'religion', 'nationality', 'phone', 'emergency_contact',
        'present_address', 'permanent_address', 'city', 'state', 'zip_code',
        'country', 'joining_date', 'leaving_date', 'status', 'bank_name',
        'bank_account_number', 'bank_branch', 'salary', 'salary_type',
        'nid_number', 'passport_number', 'driving_license', 'notes',
    ];

    protected array $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'leaving_date' => 'date',
        'salary' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
