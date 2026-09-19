<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class ChartOfAccount extends Model
{
    protected static string $table = 'chart_of_accounts';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'code', 'name_en', 'name_bn', 'type', 'parent_id', 'is_active',
    ];

    protected array $casts = [
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function entries()
    {
        return $this->hasMany(LedgerEntry::class, 'chart_of_account_id');
    }
}
