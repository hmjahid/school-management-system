<?php

namespace Tests\Unit\Models;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssignmentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function makeSubject(): Subject
    {
        return Subject::create([
            'name' => 'Subject '.uniqid(),
            'code' => 'SUB'.uniqid(),
        ]);
    }

    private function makeAssignment(): Assignment
    {
        $batch = Batch::create(['name' => 'Batch '.uniqid()]);
        $subject = $this->makeSubject();
        $user = User::factory()->create();

        return Assignment::create([
            'title' => 'Homework 1',
            'batch_id' => $batch->id,
            'subject_id' => $subject->id,
            'due_date' => now()->addDays(3),
            'created_by' => $user->id,
        ]);
    }

    private function makeSubmission(array $attributes = []): AssignmentSubmission
    {
        $assignment = $this->makeAssignment();
        $student = Student::factory()->create();

        return AssignmentSubmission::create(array_merge([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'status' => AssignmentSubmission::STATUS_SUBMITTED,
        ], $attributes));
    }

    #[Test]
    public function it_persists_key_columns(): void
    {
        $submission = $this->makeSubmission([
            'file_path' => '/subs/file.pdf',
            'notes' => 'Done',
            'marks' => 18,
            'feedback' => 'Good',
        ]);

        $this->assertDatabaseHas('assignment_submissions', [
            'id' => $submission->id,
            'status' => AssignmentSubmission::STATUS_SUBMITTED,
            'marks' => 18,
        ]);
        $this->assertSame('Done', $submission->notes);
    }

    #[Test]
    public function it_has_status_constants(): void
    {
        $this->assertSame('submitted', AssignmentSubmission::STATUS_SUBMITTED);
        $this->assertSame('late', AssignmentSubmission::STATUS_LATE);
        $this->assertSame('graded', AssignmentSubmission::STATUS_GRADED);
        $this->assertSame('not_submitted', AssignmentSubmission::STATUS_NOT_SUBMITTED);
    }

    #[Test]
    public function it_belongs_to_an_assignment(): void
    {
        $assignment = $this->makeAssignment();
        $student = Student::factory()->create();
        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
        ]);

        $this->assertInstanceOf(Assignment::class, $submission->assignment);
        $this->assertSame($assignment->id, $submission->assignment->id);
    }

    #[Test]
    public function it_belongs_to_a_student(): void
    {
        $student = Student::factory()->create();
        $submission = $this->makeSubmission(['student_id' => $student->id]);

        $this->assertInstanceOf(Student::class, $submission->student);
        $this->assertSame($student->id, $submission->student->id);
    }

    #[Test]
    public function it_belongs_to_a_guardian(): void
    {
        $user = User::factory()->create();
        $guardian = Guardian::create([
            'user_id' => $user->id,
            'relation_type' => 'father',
        ]);
        $submission = $this->makeSubmission(['guardian_id' => $guardian->id]);

        $this->assertInstanceOf(Guardian::class, $submission->guardian);
        $this->assertSame($guardian->id, $submission->guardian->id);
    }

    #[Test]
    public function guardian_relationship_is_nullable(): void
    {
        $submission = $this->makeSubmission();

        $this->assertNull($submission->guardian);
    }

    #[Test]
    public function it_belongs_to_a_grader(): void
    {
        $grader = User::factory()->create();
        $submission = $this->makeSubmission(['graded_by' => $grader->id]);

        $this->assertInstanceOf(User::class, $submission->gradedBy);
        $this->assertSame($grader->id, $submission->gradedBy->id);
    }
}
