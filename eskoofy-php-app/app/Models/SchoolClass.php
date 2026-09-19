<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class SchoolClass extends Model
{
    protected static string $table = 'school_classes';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'name', 'code', 'description', 'grade_level', 'academic_session_id',
        'class_teacher_id', 'max_students', 'is_active', 'monthly_fee',
        'admission_fee', 'exam_fee', 'other_fees', 'notes', 'shift',
    ];

    protected array $casts = [
        'is_active' => 'boolean',
        'monthly_fee' => 'float',
        'admission_fee' => 'float',
        'exam_fee' => 'float',
        'other_fees' => 'float',
    ];

    public function sections()
    {
        return $this->hasMany(Section::class, 'class_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }
}
