<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Payslip extends Model
{
    protected static string $table = 'payslips';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'teacher_id', 'month', 'year', 'basic', 'total_allowances',
        'total_deductions', 'net_salary', 'details', 'status',
        'generated_at', 'paid_at',
    ];

    protected array $casts = [
        'basic' => 'float',
        'total_allowances' => 'float',
        'total_deductions' => 'float',
        'net_salary' => 'float',
        'details' => 'json',
        'generated_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
