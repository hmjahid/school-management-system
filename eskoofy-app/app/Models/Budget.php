<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use TenantScoped;

    protected $fillable = [
        'expense_category_id',
        'period_type',
        'period_start',
        'period_end',
        'amount',
        'notes',
    ];

    protected $casts = [
        'expense_category_id' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
        'amount' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
