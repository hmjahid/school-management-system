<?php

namespace App\Observers;

use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Services\LedgerService;
use Illuminate\Support\Facades\Log;

class FinanceObserver
{
    public function __construct(public LedgerService $ledger) {}

    public function created(FeePayment $payment): void
    {
        try {
            $cashAccount = $this->resolveAccountForMethod($payment->payment_method ?? 'cash');
            $incomeAccount = ChartOfAccount::where('code', '4000')->first()
                ?? ChartOfAccount::where('type', ChartOfAccount::TYPE_INCOME)->first();

            if (!$cashAccount || !$incomeAccount) {
                return;
            }

            $this->ledger->postJournal([
                ['account_id' => $cashAccount->id, 'debit' => (float) $payment->amount, 'credit' => 0],
                ['account_id' => $incomeAccount->id, 'debit' => 0, 'credit' => (float) $payment->amount],
            ], $payment, 'Fee payment ' . ($payment->reference ?? $payment->id), $payment->recorded_by ?? null, optional($payment->paid_at)->toDateString() ?? now()->toDateString());
        } catch (\Throwable $e) {
            Log::warning('Ledger post failed for FeePayment', ['error' => $e->getMessage()]);
        }
    }

    public function createdExpense(Expense $expense): void
    {
        try {
            $cashAccount = $this->resolveAccountForMethod($expense->payment_method);
            $expenseAccount = $expense->chart_of_account_id
                ? ChartOfAccount::find($expense->chart_of_account_id)
                : ChartOfAccount::where('code', '5500')->first();

            if (!$cashAccount || !$expenseAccount) {
                return;
            }

            $this->ledger->postJournal([
                ['account_id' => $expenseAccount->id, 'debit' => (float) $expense->amount, 'credit' => 0],
                ['account_id' => $cashAccount->id, 'debit' => 0, 'credit' => (float) $expense->amount],
            ], $expense, 'Expense: ' . $expense->category, $expense->created_by, $expense->date?->toDateString());
        } catch (\Throwable $e) {
            Log::warning('Ledger post failed for Expense', ['error' => $e->getMessage()]);
        }
    }

    protected function resolveAccountForMethod(string $method): ?ChartOfAccount
    {
        return match (strtolower($method)) {
            'bank', 'bkash', 'nagad', 'rocket', 'card' => ChartOfAccount::where('code', '1010')->first(),
            default => ChartOfAccount::where('code', '1000')->first(),
        };
    }
}