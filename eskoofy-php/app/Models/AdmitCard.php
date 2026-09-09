<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class AdmitCard extends Model
{
    protected static string $table = 'admit_cards';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'exam_id', 'student_id', 'admit_card_number', 'issue_date',
        'details', 'status', 'generated_by',
    ];

    protected array $casts = [
        'issue_date' => 'date',
        'details' => 'json',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
