<?php

namespace Tests\Unit\Models;

use App\Models\Routine;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoutineTest extends TestCase
{
    use RefreshDatabase;

    private function makeTeacher(): Teacher
    {
        $user = User::factory()->create();

        return Teacher::create([
            'user_id' => $user->id,
            'employee_id' => 'EMP-'.uniqid(),
            'joining_date' => now()->toDateString(),
        ]);
    }

    private function makeSubject(): Subject
    {
        return Subject::create([
            'name' => 'Subject '.uniqid(),
            'code' => 'SUB'.uniqid(),
        ]);
    }

    private function makeSection(): Section
    {
        $academicYear = \App\Models\AcademicYear::create([
            'name' => 'AY '.uniqid(),
            'session' => 'SES'.uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        return Section::create([
            'name' => 'Section '.uniqid(),
            'slug' => 'sec-'.uniqid(),
            'academic_year_id' => $academicYear->id,
        ]);
    }

    private function makeRoutine(array $attributes = []): Routine
    {
        $schoolClass = SchoolClass::factory()->create();
        $subject = $this->makeSubject();
        $teacher = $this->makeTeacher();

        return Routine::create(array_merge([
            'school_class_id' => $schoolClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'type' => Routine::TYPE_CLASS,
            'is_active' => true,
        ], $attributes));
    }

    #[Test]
    public function it_persists_key_columns(): void
    {
        $routine = $this->makeRoutine([
            'room_number' => 'A1',
            'batch_id' => null,
            'academic_session_id' => null,
            'section_id' => null,
        ]);

        $this->assertDatabaseHas('routines', [
            'id' => $routine->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'type' => Routine::TYPE_CLASS,
        ]);
        $this->assertTrue($routine->is_active);
    }

    #[Test]
    public function it_has_type_constants(): void
    {
        $this->assertSame('class', Routine::TYPE_CLASS);
        $this->assertSame('exam', Routine::TYPE_EXAM);
    }

    #[Test]
    public function it_has_day_constants(): void
    {
        $this->assertSame('Monday', Routine::DAYS[1]);
        $this->assertSame('Sunday', Routine::DAYS[7]);
    }

    #[Test]
    public function it_returns_the_day_name(): void
    {
        App::setLocale('en');
        $routine = $this->makeRoutine(['day_of_week' => 3]);

        $this->assertSame('Wednesday', $routine->day_name);
    }

    #[Test]
    public function scope_class_returns_only_class_routines(): void
    {
        $this->makeRoutine(['type' => Routine::TYPE_CLASS]);
        $this->makeRoutine(['type' => Routine::TYPE_EXAM]);

        $classRoutines = Routine::class()->get();

        $this->assertCount(1, $classRoutines);
        $this->assertSame(Routine::TYPE_CLASS, $classRoutines->first()->type);
    }

    #[Test]
    public function scope_exam_returns_only_exam_routines(): void
    {
        $this->makeRoutine(['type' => Routine::TYPE_CLASS]);
        $this->makeRoutine(['type' => Routine::TYPE_EXAM]);

        $examRoutines = Routine::exam()->get();

        $this->assertCount(1, $examRoutines);
        $this->assertSame(Routine::TYPE_EXAM, $examRoutines->first()->type);
    }

    #[Test]
    public function it_returns_the_configured_types(): void
    {
        $types = Routine::getTypes();

        $this->assertSame('Class Routine', $types[Routine::TYPE_CLASS]);
        $this->assertSame('Exam Routine', $types[Routine::TYPE_EXAM]);
    }

    #[Test]
    public function it_belongs_to_a_school_class(): void
    {
        $schoolClass = SchoolClass::factory()->create();
        $routine = $this->makeRoutine(['school_class_id' => $schoolClass->id]);

        $this->assertInstanceOf(SchoolClass::class, $routine->schoolClass);
        $this->assertSame($schoolClass->id, $routine->schoolClass->id);
    }

    #[Test]
    public function it_belongs_to_a_section(): void
    {
        $section = $this->makeSection();
        $routine = $this->makeRoutine(['section_id' => $section->id]);

        $this->assertInstanceOf(Section::class, $routine->section);
        $this->assertSame($section->id, $routine->section->id);
    }

    #[Test]
    public function it_belongs_to_a_subject(): void
    {
        $subject = $this->makeSubject();
        $routine = $this->makeRoutine(['subject_id' => $subject->id]);

        $this->assertInstanceOf(Subject::class, $routine->subject);
        $this->assertSame($subject->id, $routine->subject->id);
    }

    #[Test]
    public function it_belongs_to_a_teacher(): void
    {
        $teacher = $this->makeTeacher();
        $routine = $this->makeRoutine(['teacher_id' => $teacher->id]);

        $this->assertInstanceOf(Teacher::class, $routine->teacher);
        $this->assertSame($teacher->id, $routine->teacher->id);
    }
}
