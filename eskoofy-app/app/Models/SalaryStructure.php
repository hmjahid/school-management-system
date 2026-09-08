<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructure extends Model
{
    protected $fillable = ['teacher_id', 'basic', 'allowances', 'deductions', 'effective_from', 'is_active'];

    protected $casts = [
        'basic' => 'decimal:2',
        'allowances' => 'array',
        'deductions' => 'array',
        'effective_from' => 'date',
        'is_active' => 'boolean',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function totalAllowances(): float
    {
        return collect($this->allowances ?? [])->sum(fn ($a) => (float) ($a['amount'] ?? 0));
    }

    public function totalDeductions(): float
    {
        return collect($this->deductions ?? [])->sum(fn ($d) => (float) ($d['amount'] ?? 0));
    }

    public function gross(): float
    {
        return (float) $this->basic + $this->totalAllowances();
    }

    public function net(): float
    {
        return $this->gross() - $this->totalDeductions();
    }
}
