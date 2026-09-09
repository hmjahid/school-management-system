<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class LedgerEntry extends Model
{
    protected static string $table = 'ledger_entries';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'chart_of_account_id', 'date', 'debit', 'credit', 'reference_type',
        'reference_id', 'note', 'created_by',
    ];

    protected array $casts = [
        'date' => 'date',
        'debit' => 'float',
        'credit' => 'float',
    ];

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
