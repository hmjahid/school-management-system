<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\LedgerEntry;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoLedgerSeeder extends Seeder
{
    public function run(): void
    {
        $incomeAccounts = ChartOfAccount::where('type', ChartOfAccount::TYPE_INCOME)->get();
        $expenseAccounts = ChartOfAccount::where('type', ChartOfAccount::TYPE_EXPENSE)->get();
        $cashAccount = ChartOfAccount::where('code', '1000')->first();
        $bankAccount = ChartOfAccount::where('code', '1010')->first();

        if ($incomeAccounts->isEmpty() || $expenseAccounts->isEmpty() || ! $cashAccount) {
            return;
        }

        for ($month = 0; $month < 3; $month++) {
            $date = Carbon::now()->subMonths(2 - $month);

            foreach ($incomeAccounts as $account) {
                $numEntries = rand(2, 3);
                for ($e = 0; $e < $numEntries; $e++) {
                    LedgerEntry::create([
                        'chart_of_account_id' => $account->id,
                        'date' => $date->copy()->addDays(rand(1, 25))->format('Y-m-d'),
                        'note' => 'Monthly collection - '.$account->name_en,
                        'debit' => 0,
                        'credit' => rand(2, 5) * 10000,
                        'reference_type' => FeePayment::class,
                        'reference_id' => rand(1, 100),
                    ]);
                }
            }

            foreach ($expenseAccounts as $account) {
                $numEntries = rand(1, 2);
                for ($e = 0; $e < $numEntries; $e++) {
                    LedgerEntry::create([
                        'chart_of_account_id' => $account->id,
                        'date' => $date->copy()->addDays(rand(1, 28))->format('Y-m-d'),
                        'note' => $account->name_en.' - '.$date->format('F Y'),
                        'debit' => rand(1, 10) * 5000,
                        'credit' => 0,
                        'reference_type' => Expense::class,
                        'reference_id' => rand(1, 50),
                    ]);
                }
            }
        }

        LedgerEntry::create([
            'chart_of_account_id' => $cashAccount->id,
            'date' => Carbon::now()->subMonths(6)->format('Y-m-d'),
            'note' => 'Opening balance - Cash on Hand',
            'debit' => 150000,
            'credit' => 0,
            'reference_type' => null,
            'reference_id' => null,
        ]);

        if ($bankAccount) {
            LedgerEntry::create([
                'chart_of_account_id' => $bankAccount->id,
                'date' => Carbon::now()->subMonths(6)->format('Y-m-d'),
                'note' => 'Opening balance - Bank Account',
                'debit' => 500000,
                'credit' => 0,
                'reference_type' => null,
                'reference_id' => null,
            ]);
        }
    }
}
