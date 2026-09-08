<?php

namespace Database\Seeders;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoLeaveSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name_en' => 'Sick Leave', 'days_per_year' => 14, 'is_active' => true, 'is_paid' => true],
            ['name_en' => 'Casual Leave', 'days_per_year' => 10, 'is_active' => true, 'is_paid' => true],
            ['name_en' => 'Annual Leave', 'days_per_year' => 30, 'is_active' => true, 'is_paid' => true],
            ['name_en' => 'Maternity Leave', 'days_per_year' => 120, 'is_active' => true, 'is_paid' => true],
            ['name_en' => 'Paternity Leave', 'days_per_year' => 7, 'is_active' => true, 'is_paid' => true],
        ];

        foreach ($types as $t) {
            LeaveType::create($t);
        }

        $teachers = Teacher::take(10)->get();
        $leaveTypes = LeaveType::all();
        $statuses = ['approved', 'pending', 'rejected'];
        $weights = [50, 30, 20];

        foreach ($teachers as $teacher) {
            $numLeaves = rand(1, 3);
            for ($i = 0; $i < $numLeaves; $i++) {
                $type = $leaveTypes->random();
                $startDate = Carbon::now()->subMonths(rand(0, 3))->startOfDay();
                $days = rand(1, min(5, $type->days_per_year));

                $rand = rand(1, 100);
                $cumulative = 0;
                $status = 'approved';
                foreach (array_combine($statuses, $weights) as $s => $w) {
                    $cumulative += $w;
                    if ($rand <= $cumulative) {
                        $status = $s;
                        break;
                    }
                }

                LeaveRequest::create([
                    'teacher_id' => $teacher->id,
                    'leave_type_id' => $type->id,
                    'from_date' => $startDate->format('Y-m-d'),
                    'to_date' => $startDate->copy()->addDays($days - 1)->format('Y-m-d'),
                    'reason' => $status === 'approved' ? 'Personal reasons' : 'Family emergency',
                    'status' => $status,
                    'approver_id' => $status === 'approved' ? 1 : null,
                ]);
            }
        }
    }
}
