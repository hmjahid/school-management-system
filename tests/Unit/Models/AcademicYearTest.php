<?php

namespace Tests\Unit\Models;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AcademicYearTest extends TestCase
{
    use RefreshDatabase;

    private function makeYear(array $overrides = []): AcademicYear
    {
        return AcademicYear::create(array_merge([
            'name' => 'Year '.uniqid(),
            'session' => 'SES'.uniqid(),
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'is_current' => true,
            'description' => 'Academic year',
        ], $overrides));
    }

    #[Test]
    public function it_persists_fillable_columns(): void
    {
        $session = 'SES'.uniqid();
        $year = $this->makeYear(['session' => $session, 'name' => 'Year One']);

        $this->assertDatabaseHas('academic_years', [
            'name' => 'Year One',
            'session' => $session,
            'is_current' => true,
        ]);
        $this->assertTrue($year->is_current);
    }

    #[Test]
    public function it_casts_dates_and_boolean(): void
    {
        $year = $this->makeYear();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $year->start_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $year->end_date);
        $this->assertIsBool($year->is_current);
    }

    #[Test]
    public function it_defines_sections_relationship(): void
    {
        $year = $this->makeYear();

        $this->assertInstanceOf(HasMany::class, $year->sections());
    }

    #[Test]
    public function it_soft_deletes(): void
    {
        $year = $this->makeYear();
        $year->delete();

        $this->assertSoftDeleted('academic_years', ['id' => $year->id]);
    }
}
