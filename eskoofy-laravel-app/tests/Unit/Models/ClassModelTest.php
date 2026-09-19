<?php

namespace Tests\Unit\Models;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClassModelTest extends TestCase
{
    use RefreshDatabase;

    private function makeSection(): Section
    {
        $year = AcademicYear::create([
            'name' => 'Year '.uniqid(),
            'session' => 'SES'.uniqid(),
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);

        return Section::create([
            'name' => 'Section '.uniqid(),
            'slug' => 'SEC'.uniqid(),
            'academic_year_id' => $year->id,
        ]);
    }

    private function makeSubject(): Subject
    {
        return Subject::create([
            'name' => 'Subject '.uniqid(),
            'code' => 'SUB'.uniqid(),
        ]);
    }

    private function makeClass(array $overrides = []): ClassModel
    {
        return ClassModel::create(array_merge([
            'name' => 'Class '.uniqid(),
            'teacher_id' => User::factory()->create()->id,
            'subject_id' => $this->makeSubject()->id,
            'section_id' => $this->makeSection()->id,
            'academic_year' => '2024-2025',
            'schedule' => 'Mon 10:00',
            'room_number' => 'R1',
            'is_active' => true,
            'description' => 'Desc',
        ], $overrides));
    }

    #[Test]
    public function it_persists_fillable_columns(): void
    {
        $name = 'Class '.uniqid();
        $class = $this->makeClass(['name' => $name, 'academic_year' => '2025-2026']);

        $this->assertDatabaseHas('classes', [
            'name' => $name,
            'academic_year' => '2025-2026',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_casts_is_active_boolean(): void
    {
        $class = $this->makeClass(['is_active' => 0]);

        $this->assertFalse($class->is_active);
        $this->assertIsBool($class->is_active);
    }

    #[Test]
    public function it_belongs_to_teacher(): void
    {
        $teacher = User::factory()->create();
        $class = $this->makeClass(['teacher_id' => $teacher->id]);

        $this->assertInstanceOf(BelongsTo::class, $class->teacher());
        $this->assertSame($teacher->id, $class->teacher->id);
    }

    #[Test]
    public function it_belongs_to_subject(): void
    {
        $subject = Subject::create([
            'name' => 'Subject '.uniqid(),
            'code' => 'SUB'.uniqid(),
        ]);
        $class = $this->makeClass(['subject_id' => $subject->id]);

        $this->assertInstanceOf(BelongsTo::class, $class->subject());
        $this->assertSame($subject->id, $class->subject->id);
    }

    #[Test]
    public function it_belongs_to_section(): void
    {
        $section = $this->makeSection();
        $class = $this->makeClass(['section_id' => $section->id]);

        $this->assertInstanceOf(BelongsTo::class, $class->section());
        $this->assertSame($section->id, $class->section->id);
    }

    #[Test]
    public function it_has_grades_relationship(): void
    {
        $class = $this->makeClass();

        $this->assertInstanceOf(HasMany::class, $class->grades());
    }
}
