<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\LedgerEntry;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoLedgerSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['name_en' => 'Tuition Fee Income', 'code' => 'INC-001', 'type' => 'income'],
            ['name_en' => 'Transport Fee Income', 'code' => 'INC-002', 'type' => 'income'],
            ['name_en' => 'Library Fee Income', 'code' => 'INC-003', 'type' => 'income'],
            ['name_en' => 'Admission Fee Income', 'code' => 'INC-004', 'type' => 'income'],
            ['name_en' => 'Salary Expense', 'code' => 'EXP-001', 'type' => 'expense'],
            ['name_en' => 'Utility Expense', 'code' => 'EXP-002', 'type' => 'expense'],
            ['name_en' => 'Maintenance Expense', 'code' => 'EXP-003', 'type' => 'expense'],
            ['name_en' => 'Stationery Expense', 'code' => 'EXP-004', 'type' => 'expense'],
            ['name_en' => 'Cash in Hand', 'code' => 'AST-001', 'type' => 'asset'],
            ['name_en' => 'Bank Account - Sonali', 'code' => 'AST-002', 'type' => 'asset'],
        ];

        foreach ($accounts as $a) {
            ChartOfAccount::create($a);
        }

        $incomeAccounts = ChartOfAccount::where('type', 'income')->get();
        $expenseAccounts = ChartOfAccount::where('type', 'expense')->get();
        $assetAccounts = ChartOfAccount::where('type', 'asset')->get();

        for ($month = 0; $month < 3; $month++) {
            $date = Carbon::now()->subMonths(2 - $month);

            foreach ($incomeAccounts as $account) {
                $numEntries = rand(2, 3);
                for ($e = 0; $e < $numEntries; $e++) {
                    LedgerEntry::create([
                        'chart_of_account_id' => $account->id,
                        'date' => $date->copy()->addDays(rand(1, 25))->format('Y-m-d'),
                        'note' => 'Monthly collection - ' . $account->name_en,
                        'debit' => rand(2, 5) * 10000,
                        'credit' => 0,
                        'reference_type' => 'fee_payment',
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
                        'note' => $account->name_en . ' - ' . $date->format('F Y'),
                        'debit' => 0,
                        'credit' => rand(1, 10) * 5000,
                        'reference_type' => ['expense', 'bill'][rand(0, 1)],
                        'reference_id' => rand(1, 50),
                    ]);
                }
            }
        }

        foreach ($assetAccounts as $account) {
            LedgerEntry::create([
                'chart_of_account_id' => $account->id,
                'date' => Carbon::now()->subMonths(6)->format('Y-m-d'),
                'note' => 'Opening balance - ' . $account->name_en,
                'debit' => $account->name_en === 'Cash in Hand' ? 150000 : 500000,
                'credit' => 0,
                'reference_type' => 'opening_balance',
                'reference_id' => null,
            ]);
        }
    }
}
