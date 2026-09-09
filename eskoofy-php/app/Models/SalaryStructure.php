<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class SalaryStructure extends Model
{
    protected static string $table = 'salary_structures';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'teacher_id', 'basic', 'allowances', 'deductions', 'effective_from',
        'is_active',
    ];

    protected array $casts = [
        'basic' => 'float',
        'allowances' => 'json',
        'deductions' => 'json',
        'effective_from' => 'date',
        'is_active' => 'boolean',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
