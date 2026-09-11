<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Expense extends Model
{
    protected static string $table = 'expenses';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'category', 'amount', 'date', 'vendor', 'payment_method', 'note',
        'chart_of_account_id', 'created_by',
    ];

    protected array $casts = [
        'date' => 'date',
        'amount' => 'float',
    ];

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
