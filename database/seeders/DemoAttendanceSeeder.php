<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $students = Student::where('status', 'active')->take(50)->get();
        $statuses = ['present', 'absent', 'late', 'half_day'];
        $weights = [85, 8, 5, 2];
        $markedBy = 1;

        for ($day = 30; $day >= 1; $day--) {
            $date = Carbon::now()->subDays($day);
            if ($date->isFriday()) continue;

            static $consecutiveAbsent = [];

            foreach ($students as $student) {
                $key = $student->id;
                $prevAbsent = ($consecutiveAbsent[$key] ?? 0) >= 2;

                if ($prevAbsent) {
                    $status = 'present';
                    $consecutiveAbsent[$key] = 0;
                } else {
                    $rand = rand(1, 100);
                    $cumulative = 0;
                    $status = 'present';
                    foreach (array_combine($statuses, $weights) as $s => $w) {
                        $cumulative += $w;
                        if ($rand <= $cumulative) { $status = $s; break; }
                    }
                    if ($status === 'absent') {
                        $consecutiveAbsent[$key] = ($consecutiveAbsent[$key] ?? 0) + 1;
                    } else {
                        $consecutiveAbsent[$key] = 0;
                    }
                }

                Attendance::create([
                    'student_id' => $student->id,
                    'school_class_id' => $student->class_id,
                    'date' => $date->format('Y-m-d'),
                    'status' => $status,
                    'marked_by' => $markedBy,
                ]);
            }
        }
    }
}
