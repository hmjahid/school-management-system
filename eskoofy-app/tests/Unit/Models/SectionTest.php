<?php

namespace Tests\Unit\Models;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Section;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SectionTest extends TestCase
{
    use RefreshDatabase;

    private function makeYear(): AcademicYear
    {
        return AcademicYear::create([
            'name' => 'Year '.uniqid(),
            'session' => 'SES'.uniqid(),
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);
    }

    private function makeSection(array $overrides = []): Section
    {
        return Section::create(array_merge([
            'name' => 'Section '.uniqid(),
            'slug' => 'SEC'.uniqid(),
            'capacity' => 30,
            'academic_year_id' => $this->makeYear()->id,
            'is_active' => true,
        ], $overrides));
    }

    #[Test]
    public function it_persists_fillable_columns(): void
    {
        $slug = 'SEC'.uniqid();
        $section = $this->makeSection(['slug' => $slug, 'capacity' => 25, 'is_active' => false]);

        $this->assertDatabaseHas('sections', [
            'slug' => $slug,
            'capacity' => 25,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function it_casts_boolean_and_integer(): void
    {
        $section = $this->makeSection(['is_active' => 1, 'capacity' => 40]);

        $this->assertIsBool($section->is_active);
        $this->assertTrue($section->is_active);
        $this->assertIsInt($section->capacity);
    }

    #[Test]
    public function it_belongs_to_academic_year(): void
    {
        $year = $this->makeYear();
        $section = $this->makeSection(['academic_year_id' => $year->id]);

        $this->assertInstanceOf(BelongsTo::class, $section->academicYear());
        $this->assertSame($year->id, $section->academicYear->id);
    }

    #[Test]
    public function it_belongs_to_school_class_when_set(): void
    {
        $class = SchoolClass::factory()->create();
        $section = $this->makeSection(['class_id' => $class->id]);

        $this->assertInstanceOf(BelongsTo::class, $section->schoolClass());
        $this->assertSame($class->id, $section->schoolClass->id);
    }

    #[Test]
    public function it_has_students_relationship(): void
    {
        $section = $this->makeSection();

        $this->assertInstanceOf(HasMany::class, $section->students());
    }

    #[Test]
    public function it_computes_status_badge(): void
    {
        $section = $this->makeSection(['is_active' => true]);

        $this->assertStringContainsString('badge', $section->status_badge);
        $this->assertStringContainsString('Active', $section->status_badge);
    }

    #[Test]
    public function it_computes_full_name_without_class(): void
    {
        $section = $this->makeSection(['name' => 'Alpha']);

        $this->assertSame('Alpha', $section->full_name);
    }

    #[Test]
    public function it_computes_available_seats_and_student_count(): void
    {
        $section = $this->makeSection(['capacity' => 30]);

        $this->assertSame(0, $section->student_count);
        $this->assertSame(30, $section->available_seats);
        $this->assertTrue($section->has_available_seats);
    }

    #[Test]
    public function it_scopes_active_and_of_class(): void
    {
        $this->makeSection(['is_active' => true]);
        $this->makeSection(['is_active' => false]);

        $class = SchoolClass::factory()->create();
        $this->makeSection(['class_id' => $class->id, 'is_active' => false]);

        $this->assertEquals(1, Section::active()->count());
        $this->assertEquals(1, Section::ofClass($class->id)->count());
    }

    #[Test]
    public function it_soft_deletes(): void
    {
        $section = $this->makeSection();
        $section->delete();

        $this->assertSoftDeleted('sections', ['id' => $section->id]);
    }
}
