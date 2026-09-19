<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class StudentIdCard extends Model
{
    protected static string $table = 'student_id_cards';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'student_id', 'id_card_number', 'issue_date', 'expiry_date',
        'blood_group', 'photo_url', 'details', 'status', 'generated_by',
    ];

    protected array $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'details' => 'json',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
