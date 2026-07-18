<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';
    public const TYPE_EQUITY = 'equity';

    protected $fillable = ['code', 'name_en', 'name_bn', 'type', 'parent_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'chart_of_account_id');
    }

    public function balance(?string $startDate = null, ?string $endDate = null): float
    {
        $query = $this->entries();
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }
        $row = $query->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')->first();
        // For asset/expense: balance = debit - credit
        // For liability/income/equity: balance = credit - debit
        return in_array($this->type, [self::TYPE_ASSET, self::TYPE_EXPENSE], true)
            ? (float) ($row->d - $row->c)
            : (float) ($row->c - $row->d);
    }
}