<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'category',
        'amount',
        'date',
        'vendor',
        'payment_method',
        'note',
        'chart_of_account_id',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'expense_category_id' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
