<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ChartOfAccount extends Model
{
    public const TYPE_ASSET = 'asset';

    public const TYPE_LIABILITY = 'liability';

    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    public const TYPE_EQUITY = 'equity';

    public const TYPE_BANK = 'bank';

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

        // `ledger_entries.date` is a date column but is written with a time
        // component (`Y-m-d H:i:s`), so comparing it against a bare `Y-m-d`
        // string silently drops every entry dated on the end date (e.g. today,
        // or any month-end report). Compare against whole-day bounds instead.
        if ($startDate) {
            $query->where('date', '>=', Carbon::parse($startDate)->startOfDay());
        }
        if ($endDate) {
            $query->where('date', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $row = $query->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')->first();

        // For asset/expense: balance = debit - credit
        // For liability/income/equity: balance = credit - debit
        return in_array($this->type, [self::TYPE_ASSET, self::TYPE_EXPENSE], true)
            ? (float) ($row->d - $row->c)
            : (float) ($row->c - $row->d);
    }
}
