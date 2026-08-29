<?php

namespace Tests\Unit\Models;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function makeSubject(): Subject
    {
        return Subject::create([
            'name' => 'Subject ' . uniqid(),
            'code' => 'SUB' . uniqid(),
        ]);
    }

    private function makeSection(): Section
    {
        $academicYear = \App\Models\AcademicYear::create([
            'name' => 'AY ' . uniqid(),
            'session' => 'SES' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        return Section::create([
            'name' => 'Section ' . uniqid(),
            'slug' => 'sec-' . uniqid(),
            'academic_year_id' => $academicYear->id,
        ]);
    }

    private function makeAssignment(array $attributes = []): Assignment
    {
        $batch = Batch::create(['name' => 'Batch ' . uniqid()]);
        $subject = $this->makeSubject();
        $user = User::factory()->create();

        return Assignment::create(array_merge([
            'title' => 'Homework 1',
            'batch_id' => $batch->id,
            'subject_id' => $subject->id,
            'due_date' => now()->addDays(3),
            'created_by' => $user->id,
        ], $attributes));
    }

    /** @test */
    public function it_persists_key_columns(): void
    {
        $assignment = $this->makeAssignment([
            'description' => 'Do the work',
            'total_marks' => 20,
            'allow_guardian_notes' => true,
        ]);

        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'title' => 'Homework 1',
            'total_marks' => 20,
        ]);
        $this->assertTrue($assignment->allow_guardian_notes);
        $this->assertInstanceOf(\DateTimeInterface::class, $assignment->due_date);
    }

    /** @test */
    public function it_soft_deletes(): void
    {
        $assignment = $this->makeAssignment();

        $this->assertNull($assignment->deleted_at);
        $assignment->delete();
        $this->assertNotNull($assignment->fresh()->deleted_at);
    }

    /** @test */
    public function it_belongs_to_a_batch(): void
    {
        $batch = Batch::create(['name' => 'Batch ' . uniqid()]);
        $assignment = $this->makeAssignment(['batch_id' => $batch->id]);

        $this->assertInstanceOf(Batch::class, $assignment->batch);
        $this->assertSame($batch->id, $assignment->batch->id);
    }

    /** @test */
    public function it_belongs_to_a_class(): void
    {
        $class = SchoolClass::factory()->create();
        $assignment = $this->makeAssignment(['class_id' => $class->id]);

        $this->assertInstanceOf(SchoolClass::class, $assignment->class);
        $this->assertSame($class->id, $assignment->class->id);
    }

    /** @test */
    public function it_belongs_to_a_section(): void
    {
        $section = $this->makeSection();
        $assignment = $this->makeAssignment(['section_id' => $section->id]);

        $this->assertInstanceOf(Section::class, $assignment->section);
        $this->assertSame($section->id, $assignment->section->id);
    }

    /** @test */
    public function it_belongs_to_a_subject(): void
    {
        $subject = $this->makeSubject();
        $assignment = $this->makeAssignment(['subject_id' => $subject->id]);

        $this->assertInstanceOf(Subject::class, $assignment->subject);
        $this->assertSame($subject->id, $assignment->subject->id);
    }

    /** @test */
    public function it_belongs_to_a_creator(): void
    {
        $user = User::factory()->create();
        $assignment = $this->makeAssignment(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $assignment->createdBy);
        $this->assertSame($user->id, $assignment->createdBy->id);
    }

    /** @test */
    public function it_has_many_submissions(): void
    {
        $assignment = $this->makeAssignment();
        $student = Student::factory()->create();

        AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
        ]);

        $this->assertCount(1, $assignment->submissions);
        $this->assertInstanceOf(AssignmentSubmission::class, $assignment->submissions->first());
    }
}
