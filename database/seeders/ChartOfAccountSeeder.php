<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['code' => '1000', 'name_en' => 'Cash on Hand', 'name_bn' => 'হাতে নগদ', 'type' => ChartOfAccount::TYPE_ASSET],
            ['code' => '1010', 'name_en' => 'Bank Account', 'name_bn' => 'ব্যাংক হিসাব', 'type' => ChartOfAccount::TYPE_ASSET],
            ['code' => '1200', 'name_en' => 'Accounts Receivable', 'name_bn' => 'প্রাপ্য হিসাব', 'type' => ChartOfAccount::TYPE_ASSET],
            ['code' => '2000', 'name_en' => 'Accounts Payable', 'name_bn' => 'প্রদেয় হিসাব', 'type' => ChartOfAccount::TYPE_LIABILITY],
            ['code' => '3000', 'name_en' => 'Owner Equity', 'name_bn' => 'মালিকের মূলধন', 'type' => ChartOfAccount::TYPE_EQUITY],
            ['code' => '4000', 'name_en' => 'Tuition Fee Income', 'name_bn' => 'বেতন আয়', 'type' => ChartOfAccount::TYPE_INCOME],
            ['code' => '4010', 'name_en' => 'Admission Fee Income', 'name_bn' => 'ভর্তি ফি আয়', 'type' => ChartOfAccount::TYPE_INCOME],
            ['code' => '4020', 'name_en' => 'Exam Fee Income', 'name_bn' => 'পরীক্ষার ফি আয়', 'type' => ChartOfAccount::TYPE_INCOME],
            ['code' => '4030', 'name_en' => 'Transport Fee Income', 'name_bn' => 'পরিবহন ফি আয়', 'type' => ChartOfAccount::TYPE_INCOME],
            ['code' => '5000', 'name_en' => 'Salary Expense', 'name_bn' => 'বেতন খরচ', 'type' => ChartOfAccount::TYPE_EXPENSE],
            ['code' => '5100', 'name_en' => 'Rent Expense', 'name_bn' => 'ভাড়া খরচ', 'type' => ChartOfAccount::TYPE_EXPENSE],
            ['code' => '5200', 'name_en' => 'Utility Expense', 'name_bn' => 'ইউটিলিটি খরচ', 'type' => ChartOfAccount::TYPE_EXPENSE],
            ['code' => '5300', 'name_en' => 'Supplies Expense', 'name_bn' => 'সরবরাহ খরচ', 'type' => ChartOfAccount::TYPE_EXPENSE],
            ['code' => '5400', 'name_en' => 'Maintenance Expense', 'name_bn' => 'রক্ষণাবেক্ষণ খরচ', 'type' => ChartOfAccount::TYPE_EXPENSE],
            ['code' => '5500', 'name_en' => 'Miscellaneous Expense', 'name_bn' => 'বিবিধ খরচ', 'type' => ChartOfAccount::TYPE_EXPENSE],
        ];

        foreach ($accounts as $a) {
            ChartOfAccount::updateOrCreate(['code' => $a['code']], $a + ['is_active' => true]);
        }
    }
}