<?php

namespace Tests\Unit\Models;

use App\Models\Student;
use App\Models\StudentIdCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentIdCardTest extends TestCase
{
    use RefreshDatabase;

    private function makeCard(array $attributes = []): StudentIdCard
    {
        $student = Student::factory()->create();
        $user = User::factory()->create();

        return StudentIdCard::create(array_merge([
            'student_id' => $student->id,
            'id_card_number' => 'ID-' . uniqid(),
            'issue_date' => now()->toDateString(),
            'status' => StudentIdCard::STATUS_ACTIVE,
            'generated_by' => $user->id,
        ], $attributes));
    }

    /** @test */
    public function it_persists_key_columns(): void
    {
        $card = $this->makeCard([
            'blood_group' => 'O+',
            'photo_url' => '/photos/student.jpg',
            'details' => ['rfid' => '123'],
        ]);

        $this->assertDatabaseHas('student_id_cards', [
            'id' => $card->id,
            'id_card_number' => $card->id_card_number,
            'blood_group' => 'O+',
            'status' => StudentIdCard::STATUS_ACTIVE,
        ]);
        $this->assertSame(['rfid' => '123'], $card->details);
    }

    /** @test */
    public function it_has_status_constants(): void
    {
        $this->assertSame('active', StudentIdCard::STATUS_ACTIVE);
        $this->assertSame('expired', StudentIdCard::STATUS_EXPIRED);
        $this->assertSame('revoked', StudentIdCard::STATUS_REVOKED);
    }

    /** @test */
    public function it_generates_a_number(): void
    {
        $student = Student::factory()->create();

        $number = StudentIdCard::generateNumber($student);

        $this->assertStringStartsWith('ID-' . $student->id . '-', $number);
    }

    /** @test */
    public function it_detects_expiry(): void
    {
        $expired = $this->makeCard(['expiry_date' => now()->subDay()->toDateString()]);
        $valid = $this->makeCard(['expiry_date' => now()->addYear()->toDateString()]);

        $this->assertTrue($expired->isExpired());
        $this->assertFalse($valid->isExpired());
    }

    /** @test */
    public function it_belongs_to_a_student(): void
    {
        $student = Student::factory()->create();
        $card = $this->makeCard(['student_id' => $student->id]);

        $this->assertInstanceOf(Student::class, $card->student);
        $this->assertSame($student->id, $card->student->id);
    }

    /** @test */
    public function it_belongs_to_a_generator(): void
    {
        $user = User::factory()->create();
        $card = $this->makeCard(['generated_by' => $user->id]);

        $this->assertInstanceOf(User::class, $card->generatedBy);
        $this->assertSame($user->id, $card->generatedBy->id);
    }

    /** @test */
    public function it_soft_deletes(): void
    {
        $card = $this->makeCard();

        $this->assertNull($card->deleted_at);
        $card->delete();
        $this->assertNotNull($card->fresh()->deleted_at);
    }
}
