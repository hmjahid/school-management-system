<?php

namespace Database\Seeders;

use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoFeeSeeder extends Seeder
{
    public function run(): void
    {
        $classes = SchoolClass::all();
        $feeTypeData = [
            ['name' => 'Tuition Fee', 'amount' => [500, 1500, 2000, 2500, 3000, 3500, 4000, 4500, 5000, 5500, 6000, 6500, 7000, 7500]],
            ['name' => 'Transport Fee', 'amount' => [300, 500, 800, 1000, 1200, 1500, 1500, 1800, 2000, 2000, 2200, 2500, 2500, 3000]],
            ['name' => 'Library Fee', 'amount' => [100, 100, 150, 150, 200, 200, 250, 250, 300, 300, 350, 350, 400, 400]],
            ['name' => 'Lab Fee', 'amount' => [0, 0, 0, 0, 200, 200, 300, 300, 400, 400, 500, 500, 600, 600]],
            ['name' => 'Sports Fee', 'amount' => [100, 100, 150, 150, 200, 200, 250, 250, 300, 300, 350, 350, 400, 400]],
            ['name' => 'Activity Fee', 'amount' => [50, 50, 100, 100, 150, 150, 200, 200, 250, 250, 300, 300, 350, 350]],
        ];

        $students = Student::where('status', 'active')->take(50)->get();

        foreach ($classes as $class) {
            $classStudents = $students->where('class_id', $class->id);
            if ($classStudents->isEmpty()) continue;

            $level = $class->grade_level ? $class->grade_level - 1 : 0;

            foreach ($feeTypeData as $ft) {
                $amount = $ft['amount'][min($level, count($ft['amount']) - 1)];
                $fee = Fee::create([
                    'class_id' => $class->id,
                    'name' => $ft['name'],
                    'fee_type' => match ($ft['name']) {
                        'Tuition Fee' => 'tuition',
                        'Transport Fee' => 'transport',
                        'Library Fee' => 'library',
                        default => 'other',
                    },
                    'amount' => $amount,
                    'frequency' => 'monthly',
                    'status' => 'active',
                ]);

                foreach ($classStudents as $student) {
                    for ($monthOffset = 0; $monthOffset < 3; $monthOffset++) {
                        $paymentDate = Carbon::now()->subMonths(2 - $monthOffset)->startOfMonth()->addDays(rand(1, 15));
                        $paid = rand(0, 100) < 70;

                        FeePayment::create([
                            'fee_id' => $fee->id,
                            'student_id' => $student->id,
                            'amount' => $amount,
                            'paid_amount' => $paid ? $amount : 0,
                            'balance' => $paid ? 0 : $amount,
                            'payment_date' => $paymentDate->format('Y-m-d'),
                            'month' => $paymentDate->format('F'),
                            'year' => $paymentDate->format('Y'),
                            'status' => $paid ? 'paid' : 'pending',
                            'payment_method' => $paid ? (['cash', 'bank_transfer', 'online_payment'][rand(0, 2)]) : 'cash',
                            'transaction_id' => $paid ? 'TXN' . strtoupper(uniqid()) : null,
                            'notes' => $paid ? 'Payment received' : null,
                            'created_by' => 1,
                        ]);
                    }
                }
            }
        }
    }
}
