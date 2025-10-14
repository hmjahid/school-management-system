<?php

namespace Database\Factories;

use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition()
    {
        return [
            'name' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'capacity' => $this->faker->numberBetween(20, 40),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
