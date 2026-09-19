<?php

namespace Database\Factories;

use App\Models\AcademicSession;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolClassFactory extends Factory
{
    protected $model = SchoolClass::class;

    public function definition(): array
    {
        return [
            'name' => 'Class '.$this->faker->randomElement(['One', 'Two', 'Three']),
            'code' => strtoupper($this->faker->bothify('C##')),
            'grade_level' => $this->faker->numberBetween(1, 12),
            'academic_session_id' => AcademicSession::factory(),
            'is_active' => true,
        ];
    }
}
