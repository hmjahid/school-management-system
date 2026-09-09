<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Guardian extends Model
{
    protected static string $table = 'guardians';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'user_id', 'occupation', 'company', 'nid_number', 'passport_number',
        'driving_license', 'nationality', 'religion', 'blood_group',
        'present_address', 'permanent_address', 'city', 'state', 'zip_code',
        'country', 'phone', 'office_phone', 'emergency_contact', 'relationship',
        'is_primary', 'monthly_income', 'education_level', 'notes',
    ];

    protected array $casts = [
        'is_primary' => 'boolean',
        'monthly_income' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
