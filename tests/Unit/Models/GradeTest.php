<?php

namespace Tests\Unit\Models;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeTest extends TestCase
{
    use RefreshDatabase;

    private function makeClass(): ClassModel
    {
        $year = AcademicYear::create([
            'name' => 'Year '.uniqid(),
            'session' => 'SES'.uniqid(),
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);
        $section = Section::create([
            'name' => 'Section '.uniqid(),
            'slug' => 'SEC'.uniqid(),
            'academic_year_id' => $year->id,
        ]);

        return ClassModel::create([
            'name' => 'Class '.uniqid(),
            'teacher_id' => User::factory()->create()->id,
            'subject_id' => Subject::create([
                'name' => 'Subject '.uniqid(),
                'code' => 'SUB'.uniqid(),
            ])->id,
            'section_id' => $section->id,
            'academic_year' => '2024-2025',
        ]);
    }

    private function makeGrade(array $overrides = []): Grade
    {
        return Grade::create(array_merge([
            'student_id' => Student::factory()->create()->id,
            'class_id' => $this->makeClass()->id,
            'subject_id' => Subject::create([
                'name' => 'Subject '.uniqid(),
                'code' => 'SUB'.uniqid(),
            ])->id,
            'marks_obtained' => 85,
            'total_marks' => 100,
            'grade' => 'A',
            'remarks' => 'Good',
        ], $overrides));
    }

    /** @test */
    public function it_persists_fillable_columns(): void
    {
        $grade = $this->makeGrade(['marks_obtained' => 78, 'grade' => 'B', 'remarks' => 'Ok']);

        $this->assertDatabaseHas('grades', [
            'marks_obtained' => 78,
            'total_marks' => 100,
            'grade' => 'B',
            'remarks' => 'Ok',
            'student_id' => $grade->student_id,
        ]);
    }

    /** @test */
    public function it_casts_marks_as_decimal(): void
    {
        $grade = $this->makeGrade(['marks_obtained' => 92.5, 'total_marks' => 100.0]);

        $this->assertIsNumeric($grade->marks_obtained);
        $this->assertIsNumeric($grade->total_marks);
    }

    /** @test */
    public function it_belongs_to_student(): void
    {
        $student = Student::factory()->create();
        $grade = $this->makeGrade(['student_id' => $student->id]);

        $this->assertInstanceOf(BelongsTo::class, $grade->student());
        $this->assertSame($student->id, $grade->student->id);
    }

    /** @test */
    public function it_belongs_to_class(): void
    {
        $class = $this->makeClass();
        $grade = $this->makeGrade(['class_id' => $class->id]);

        $this->assertInstanceOf(BelongsTo::class, $grade->class());
        $this->assertSame($class->id, $grade->class->id);
    }

    /** @test */
    public function it_belongs_to_subject(): void
    {
        $subject = Subject::create([
            'name' => 'Subject '.uniqid(),
            'code' => 'SUB'.uniqid(),
        ]);
        $grade = $this->makeGrade(['subject_id' => $subject->id]);

        $this->assertInstanceOf(BelongsTo::class, $grade->subject());
        $this->assertSame($subject->id, $grade->subject->id);
    }

    /** @test */
    public function it_belongs_to_exam_when_set(): void
    {
        $grade = $this->makeGrade(['exam_id' => null]);

        $this->assertInstanceOf(BelongsTo::class, $grade->exam());
        $this->assertNull($grade->exam);
    }
}
