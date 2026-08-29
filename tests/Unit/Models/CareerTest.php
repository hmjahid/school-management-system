<?php

namespace Tests\Unit\Models;

use App\Models\Career;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareerTest extends TestCase
{
    use RefreshDatabase;

    private function makeCareer(array $attributes = []): Career
    {
        return Career::create(array_merge([
            'title' => 'Teacher',
            'description' => 'Teach students.',
            'requirements' => 'Degree required.',
            'type' => 'full-time',
            'location' => 'Dhaka',
            'deadline' => now()->addDays(10)->toDateString(),
        ], $attributes));
    }

    /** @test */
    public function it_persists_key_columns(): void
    {
        $career = $this->makeCareer(['is_published' => true]);

        $this->assertDatabaseHas('careers', [
            'id' => $career->id,
            'title' => 'Teacher',
            'type' => 'full-time',
            'location' => 'Dhaka',
            'is_published' => true,
        ]);

        $this->assertTrue($career->is_published);
    }

    /** @test */
    public function it_casts_salary_as_decimal(): void
    {
        $career = $this->makeCareer([
            'salary_min' => 10000,
            'salary_max' => 20000,
        ]);

        $this->assertSame('10000.00', $career->salary_min);
        $this->assertSame('20000.00', $career->salary_max);
    }

    /** @test */
    public function it_casts_deadline_as_date(): void
    {
        $date = now()->addDays(5)->toDateString();
        $career = $this->makeCareer(['deadline' => $date]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $career->deadline);
        $this->assertSame($date, $career->deadline->toDateString());
    }
}
