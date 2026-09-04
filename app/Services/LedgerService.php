<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Post a single-sided ledger entry. For double-entry the caller must
     * invoke twice with offsetting debit/credit.
     */
    public function postEntry(
        int $accountId,
        float $debit,
        float $credit,
        ?string $date = null,
        ?\Illuminate\Database\Eloquent\Model $reference = null,
        ?string $note = null,
        ?int $userId = null,
    ): LedgerEntry {
        return LedgerEntry::create([
            'chart_of_account_id' => $accountId,
            'date' => $date ?: now()->toDateString(),
            'debit' => $debit,
            'credit' => $credit,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->getKey(),
            'note' => $note,
            'created_by' => $userId,
        ]);
    }

    /**
     * Post a complete journal entry (debit + credit pair).
     * Validates that total debit equals total credit.
     */
    public function postJournal(array $lines, ?\Illuminate\Database\Eloquent\Model $reference = null, ?string $note = null, ?int $userId = null, ?string $date = null): array
    {
        $debit = array_sum(array_column($lines, 'debit'));
        $credit = array_sum(array_column($lines, 'credit'));

        if (abs($debit - $credit) > 0.001) {
            throw new \InvalidArgumentException("Journal entry unbalanced: debit={$debit} credit={$credit}");
        }

        return DB::transaction(function () use ($lines, $reference, $note, $userId, $date) {
            $entries = [];
            foreach ($lines as $line) {
                $entries[] = $this->postEntry(
                    $line['account_id'],
                    (float) $line['debit'],
                    (float) $line['credit'],
                    $date,
                    $reference,
                    $note,
                    $userId,
                );
            }

            return $entries;
        });
    }

    public function findAccountByCode(string $code): ?ChartOfAccount
    {
        return ChartOfAccount::where('code', $code)->first();
    }
}
