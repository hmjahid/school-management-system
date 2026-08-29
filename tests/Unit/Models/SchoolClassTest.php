<?php

namespace Tests\Unit\Models;

use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolClassTest extends TestCase
{
    use RefreshDatabase;

    private function makeClass(array $overrides = []): SchoolClass
    {
        return SchoolClass::factory()->create(array_merge([
            'code' => 'CODE'.uniqid(),
        ], $overrides));
    }

    /** @test */
    public function it_persists_fillable_columns(): void
    {
        $code = 'CODE'.uniqid();
        $class = $this->makeClass([
            'code' => $code,
            'name' => 'Class One',
            'grade_level' => 5,
            'is_active' => true,
            'monthly_fee' => 123.45,
        ]);

        $this->assertDatabaseHas('school_classes', [
            'code' => $code,
            'name' => 'Class One',
            'grade_level' => 5,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_casts_fees_and_booleans(): void
    {
        $class = $this->makeClass([
            'monthly_fee' => 99.50,
            'is_active' => 1,
        ]);

        $this->assertIsBool($class->is_active);
        $this->assertTrue($class->is_active);
        $this->assertIsNumeric($class->monthly_fee);
    }

    /** @test */
    public function it_has_sections_and_students_relationships(): void
    {
        $class = $this->makeClass();

        $this->assertInstanceOf(HasMany::class, $class->sections());
        $this->assertInstanceOf(HasMany::class, $class->students());
    }

    /** @test */
    public function it_exposes_shift_constants_and_getter(): void
    {
        $this->assertSame('morning', SchoolClass::SHIFT_MORNING);
        $this->assertSame('day', SchoolClass::SHIFT_DAY);
        $this->assertSame('evening', SchoolClass::SHIFT_EVENING);

        $shifts = SchoolClass::getShifts();
        $this->assertArrayHasKey('morning', $shifts);
        $this->assertArrayHasKey('day', $shifts);
        $this->assertArrayHasKey('evening', $shifts);
    }

    /** @test */
    public function it_computes_name_accessors(): void
    {
        $class = $this->makeClass([
            'name' => 'One',
            'code' => 'C99',
            'grade_level' => 9,
        ]);

        $this->assertSame('One (C99)', $class->name_with_code);
        $this->assertSame('Grade 9 - One', $class->name_with_grade);
    }

    /** @test */
    public function it_scopes_active_and_inactive(): void
    {
        $this->makeClass(['is_active' => true]);
        $this->makeClass(['is_active' => false]);

        $this->assertEquals(1, SchoolClass::active()->count());
        $this->assertEquals(1, SchoolClass::inactive()->count());
    }
}
