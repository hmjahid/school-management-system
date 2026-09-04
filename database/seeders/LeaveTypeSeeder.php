<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name_en' => 'Casual Leave', 'name_bn' => 'নৈমিত্তিক ছুটি', 'days_per_year' => 10, 'is_paid' => true],
            ['name_en' => 'Sick Leave', 'name_bn' => 'অসুস্থতার ছুটি', 'days_per_year' => 14, 'is_paid' => true],
            ['name_en' => 'Annual Leave', 'name_bn' => 'বার্ষিক ছুটি', 'days_per_year' => 20, 'is_paid' => true],
            ['name_en' => 'Unpaid Leave', 'name_bn' => 'বেতনহীন ছুটি', 'days_per_year' => 30, 'is_paid' => false],
            ['name_en' => 'Maternity Leave', 'name_bn' => 'মাতৃত্বকালীন ছুটি', 'days_per_year' => 90, 'is_paid' => true],
        ];

        foreach ($types as $t) {
            LeaveType::updateOrCreate(['name_en' => $t['name_en']], $t + ['is_active' => true]);
        }
    }
}
