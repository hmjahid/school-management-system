<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use App\Models\ClassModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'class_id' => ClassModel::factory(),
            'roll_number' => $this->faker->unique()->numberBetween(1, 1000),
            'attendance_percentage' => $this->faker->randomFloat(2, 70, 100),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
