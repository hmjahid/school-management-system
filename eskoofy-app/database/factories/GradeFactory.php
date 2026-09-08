<?php

namespace Database\Factories;

use App\Models\ClassModel;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition()
    {
        return [
            'student_id' => Student::factory(),
            'class_id' => ClassModel::factory(),
            'subject_id' => Subject::factory(),
            'exam_id' => Exam::factory(),
            'marks_obtained' => $this->faker->numberBetween(0, 100),
            'total_marks' => 100,
            'grade' => $this->faker->randomElement(['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'D', 'F']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
