<?php

namespace Database\Factories;

use App\Models\AcademicSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicSessionFactory extends Factory
{
    protected $model = AcademicSession::class;

    public function definition(): array
    {
        $year = (int) now()->format('Y');

        return [
            'name' => (string) $year,
            'code' => (string) $year,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'is_active' => true,
            'is_current' => true,
        ];
    }
}
