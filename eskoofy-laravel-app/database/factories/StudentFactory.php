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
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'admission_no' => 'ADM'.$this->faker->unique()->numerify('#####'),
            'admission_number' => 'ADMNUM'.$this->faker->unique()->numerify('####'),
            'roll_no' => 'S'.$this->faker->unique()->numerify('###'),
            'roll_number' => 'ROLL'.$this->faker->unique()->numerify('####'),
            'admission_date' => now()->toDateString(),
            'date_of_birth' => now()->subYears(rand(10, 18))->toDateString(),
            'address' => '123 Test St, Test City',
            'phone' => $this->faker->numerify('01##########'),
            'status' => 'active',
        ];
    }
}
