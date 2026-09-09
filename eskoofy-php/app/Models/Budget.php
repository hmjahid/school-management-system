<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Budget extends Model
{
    protected static string $table = 'budgets';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'expense_category_id', 'period_type', 'period_start', 'period_end',
        'amount', 'notes',
    ];

    protected array $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'amount' => 'float',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
