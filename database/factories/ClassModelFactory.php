<?php

namespace Database\Factories;

use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassModelFactory extends Factory
{
    protected $model = ClassModel::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true) . ' Class',
            'teacher_id' => User::factory(),
            'subject_id' => Subject::factory(),
            'section_id' => Section::factory(),
            'academic_year' => '2023-2024',
            'schedule' => $this->faker->randomElement(['Mon, Wed, Fri 10:00 AM - 11:00 AM', 'Tue, Thu 1:00 PM - 2:30 PM']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
