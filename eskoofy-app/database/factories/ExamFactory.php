<?php

namespace Database\Factories;

use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamFactory extends Factory
{
    protected $model = Exam::class;

    public function definition()
    {
        return [
            'name' => $this->faker->randomElement(['Midterm Exam', 'Final Exam', 'Quiz 1', 'Quiz 2', 'Assignment 1']),
            'exam_date' => $this->faker->dateTimeBetween('+1 week', '+2 months'),
            'total_marks' => $this->faker->randomElement([20, 30, 50, 100]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
