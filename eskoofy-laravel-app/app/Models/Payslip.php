<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payslip extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'net_salary', 'paid_at'])
            ->logOnlyDirty()
            ->useLogName('payroll');
    }

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'teacher_id', 'month', 'year', 'basic', 'total_allowances',
        'total_deductions', 'net_salary', 'details', 'status',
        'generated_at', 'paid_at',
    ];

    protected $casts = [
        'basic' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'details' => 'array',
        'generated_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function monthName(): string
    {
        return \Carbon\Carbon::create()->month($this->month)->format('F');
    }

    public function markPaid(): bool
    {
        $this->status = self::STATUS_PAID;
        $this->paid_at = now();

        return $this->save();
    }
}
