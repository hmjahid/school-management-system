<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class DemoExamSeeder extends Seeder
{
    public function run(): void
    {
        $sessions = AcademicSession::all();
        $batches = Batch::all();

        $examTypes = ['Midterm', 'Final', 'Pre-Test'];
        $totalMarks = [50, 100, 100];

        foreach ($sessions as $session) {
            foreach ($examTypes as $idx => $type) {
                foreach ($batches as $batch) {
                    $subjects = Subject::all()->shuffle()->take(rand(3, 6));
                    foreach ($subjects as $subject) {
                        $exam = Exam::create([
                            'batch_id' => $batch->id,
                            'academic_session_id' => $session->id,
                            'name' => $type.' - '.$subject->name,
                            'type' => strtolower(str_replace(' ', '_', $type)),
                            'start_date' => now()->subMonths(rand(0, 6))->format('Y-m-d'),
                            'end_date' => now()->subMonths(rand(0, 6))->addDays(rand(1, 3))->format('Y-m-d'),
                            'total_marks' => $totalMarks[$idx],
                            'passing_marks' => (int) ($totalMarks[$idx] * 0.4),
                            'description' => $type.' examination for '.$subject->name,
                            'status' => 'completed',
                        ]);

                        $students = Student::where('batch_id', $batch->id)->get();
                        foreach ($students as $student) {
                            $obtained = rand(10, $totalMarks[$idx]);
                            $grade = $this->calculateGrade($obtained, $totalMarks[$idx]);

                            ExamResult::create([
                                'exam_id' => $exam->id,
                                'student_id' => $student->id,
                                'obtained_marks' => $obtained,
                                'grade' => $grade,
                                'remarks' => $grade === 'F' ? 'Needs improvement' : ($grade === 'A+' ? 'Excellent' : 'Good'),
                            ]);
                        }
                    }
                }
            }
        }
    }

    private function calculateGrade($marks, $total): string
    {
        $pct = ($marks / $total) * 100;
        if ($pct >= 80) {
            return 'A+';
        }
        if ($pct >= 70) {
            return 'A';
        }
        if ($pct >= 60) {
            return 'A-';
        }
        if ($pct >= 50) {
            return 'B';
        }
        if ($pct >= 40) {
            return 'C';
        }
        if ($pct >= 33) {
            return 'D';
        }

        return 'F';
    }
}
