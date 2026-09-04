<?php

namespace Tests\Unit\Models;

use App\Models\SalaryStructure;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SalaryStructureTest extends TestCase
{
    use RefreshDatabase;

    private function makeTeacher(): Teacher
    {
        return Teacher::create([
            'user_id' => User::factory()->create()->id,
            'employee_id' => 'EMP'.uniqid(),
            'joining_date' => now()->subYear(),
        ]);
    }

    private function makeStructure(array $overrides = []): SalaryStructure
    {
        return SalaryStructure::create(array_merge([
            'teacher_id' => $this->makeTeacher()->id,
            'basic' => 25000,
            'allowances' => [
                ['name' => 'House Rent', 'amount' => 3000],
                ['name' => 'Medical', 'amount' => 1000],
            ],
            'deductions' => [
                ['name' => 'Tax', 'amount' => 2000],
            ],
            'effective_from' => now()->startOfYear(),
            'is_active' => true,
        ], $overrides));
    }

    #[Test]
    public function it_persists_key_columns_and_defaults(): void
    {
        $structure = $this->makeStructure();

        $this->assertDatabaseHas('salary_structures', [
            'id' => $structure->id,
            'basic' => 25000,
            'is_active' => true,
        ]);
        $this->assertTrue($structure->is_active);
    }

    #[Test]
    public function it_casts_effective_from_to_date_and_arrays(): void
    {
        $date = now()->startOfYear()->startOfDay();
        $structure = SalaryStructure::create([
            'teacher_id' => $this->makeTeacher()->id,
            'basic' => 1000,
            'allowances' => [['name' => 'A', 'amount' => 100]],
            'deductions' => [['name' => 'D', 'amount' => 50]],
            'effective_from' => $date,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $structure->effective_from);
        $this->assertEquals($date->toDateString(), $structure->effective_from->toDateString());
        $this->assertIsArray($structure->allowances);
        $this->assertIsArray($structure->deductions);
    }

    #[Test]
    public function it_belongs_to_a_teacher(): void
    {
        $teacher = $this->makeTeacher();
        $structure = $this->makeStructure(['teacher_id' => $teacher->id]);

        $this->assertInstanceOf(Teacher::class, $structure->teacher);
        $this->assertEquals($teacher->id, $structure->teacher->id);
    }

    #[Test]
    public function it_sums_total_allowances(): void
    {
        $structure = $this->makeStructure();

        $this->assertEquals(4000.0, $structure->totalAllowances());
    }

    #[Test]
    public function it_sums_total_deductions(): void
    {
        $structure = $this->makeStructure();

        $this->assertEquals(2000.0, $structure->totalDeductions());
    }

    #[Test]
    public function gross_is_basic_plus_allowances(): void
    {
        $structure = $this->makeStructure();

        $this->assertEquals(29000.0, $structure->gross());
    }

    #[Test]
    public function net_is_gross_minus_deductions(): void
    {
        $structure = $this->makeStructure();

        $this->assertEquals(27000.0, $structure->net());
    }

    #[Test]
    public function totals_are_zero_when_allowances_and_deductions_null(): void
    {
        $structure = SalaryStructure::create([
            'teacher_id' => $this->makeTeacher()->id,
            'basic' => 12000,
            'effective_from' => now()->startOfYear(),
        ]);

        $this->assertEquals(0.0, $structure->totalAllowances());
        $this->assertEquals(0.0, $structure->totalDeductions());
        $this->assertEquals(12000.0, $structure->gross());
        $this->assertEquals(12000.0, $structure->net());
    }
}
