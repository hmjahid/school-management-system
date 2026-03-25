<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'class_id' => SchoolClass::factory(),
            'admission_number' => 'ADM'.$this->faker->unique()->numerify('#####'),
            'admission_date' => now()->toDateString(),
            'roll_number' => (string) $this->faker->unique()->numberBetween(1, 1000),
            'status' => 'active',
        ];
    }
}
