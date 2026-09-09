<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class ExpenseCategory extends Model
{
    protected static string $table = 'expense_categories';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'name', 'description', 'color', 'is_active',
    ];

    protected array $casts = [
        'is_active' => 'boolean',
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'expense_category_id');
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class, 'expense_category_id');
    }
}
