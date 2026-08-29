<?php

namespace Tests\Unit\Models;

use App\Models\AdmitCard;
use App\Models\Exam;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmitCardTest extends TestCase
{
    use RefreshDatabase;

    private function makeExam(): Exam
    {
        return Exam::create(['name' => 'Exam ' . uniqid()]);
    }

    private function makeAdmitCard(array $attributes = []): AdmitCard
    {
        $exam = $this->makeExam();
        $student = Student::factory()->create();
        $user = User::factory()->create();

        return AdmitCard::create(array_merge([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'admit_card_number' => 'ADMIT-' . uniqid(),
            'issue_date' => now()->toDateString(),
            'status' => AdmitCard::STATUS_ISSUED,
            'generated_by' => $user->id,
        ], $attributes));
    }

    /** @test */
    public function it_persists_key_columns(): void
    {
        $card = $this->makeAdmitCard(['details' => ['hall' => 'A']]);

        $this->assertDatabaseHas('admit_cards', [
            'id' => $card->id,
            'admit_card_number' => $card->admit_card_number,
            'status' => AdmitCard::STATUS_ISSUED,
        ]);
        $this->assertSame(['hall' => 'A'], $card->details);
        $this->assertEquals(now()->toDateString(), $card->issue_date->toDateString());
    }

    /** @test */
    public function it_has_status_constants(): void
    {
        $this->assertSame('issued', AdmitCard::STATUS_ISSUED);
        $this->assertSame('revoked', AdmitCard::STATUS_REVOKED);
    }

    /** @test */
    public function it_generates_a_number(): void
    {
        $exam = $this->makeExam();
        $student = Student::factory()->create();

        $number = AdmitCard::generateNumber($exam, $student);

        $this->assertStringStartsWith('ADMIT-', $number);
        $this->assertStringContainsString((string) $exam->id, $number);
    }

    /** @test */
    public function it_belongs_to_an_exam(): void
    {
        $exam = $this->makeExam();
        $student = Student::factory()->create();
        $card = $this->makeAdmitCard(['exam_id' => $exam->id, 'student_id' => $student->id]);

        $this->assertInstanceOf(Exam::class, $card->exam);
        $this->assertSame($exam->id, $card->exam->id);
    }

    /** @test */
    public function it_belongs_to_a_student(): void
    {
        $student = Student::factory()->create();
        $card = $this->makeAdmitCard(['student_id' => $student->id]);

        $this->assertInstanceOf(Student::class, $card->student);
        $this->assertSame($student->id, $card->student->id);
    }

    /** @test */
    public function it_belongs_to_a_generator(): void
    {
        $user = User::factory()->create();
        $card = $this->makeAdmitCard(['generated_by' => $user->id]);

        $this->assertInstanceOf(User::class, $card->generatedBy);
        $this->assertSame($user->id, $card->generatedBy->id);
    }

    /** @test */
    public function it_soft_deletes(): void
    {
        $card = $this->makeAdmitCard();

        $this->assertNull($card->deleted_at);
        $card->delete();
        $this->assertNotNull($card->fresh()->deleted_at);
        $this->assertDatabaseMissing('admit_cards', ['id' => $card->id, 'deleted_at' => null]);
    }
}
