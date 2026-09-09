<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Certificate extends Model
{
    protected static string $table = 'certificates';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'name', 'student_id', 'certificate_type', 'template', 'issue_date',
        'certificate_number', 'body', 'status', 'created_by', 'generated_by',
    ];

    protected array $casts = [
        'template' => 'json',
        'issue_date' => 'date',
        'body' => 'json',
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
