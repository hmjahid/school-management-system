<?php

namespace Tests\Unit\Models;

use App\Models\Certificate;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    private function makeCertificate(array $attributes = []): Certificate
    {
        $user = User::factory()->create();

        return Certificate::create(array_merge([
            'name' => 'Test Certificate',
            'template' => ['title' => 'Template'],
            'created_by' => $user->id,
            'status' => Certificate::STATUS_DRAFT,
        ], $attributes));
    }

    /** @test */
    public function it_persists_key_columns(): void
    {
        $cert = $this->makeCertificate([
            'certificate_type' => 'character',
            'certificate_number' => 'CERT-' . uniqid(),
            'issue_date' => now()->toDateString(),
            'body' => ['content' => 'Body'],
        ]);

        $this->assertDatabaseHas('certificates', [
            'id' => $cert->id,
            'name' => 'Test Certificate',
            'certificate_type' => 'character',
            'status' => Certificate::STATUS_DRAFT,
        ]);
        $this->assertSame(['title' => 'Template'], $cert->template);
        $this->assertSame(['content' => 'Body'], $cert->body);
    }

    /** @test */
    public function it_has_status_constants(): void
    {
        $this->assertSame('draft', Certificate::STATUS_DRAFT);
        $this->assertSame('issued', Certificate::STATUS_ISSUED);
        $this->assertSame('revoked', Certificate::STATUS_REVOKED);
    }

    /** @test */
    public function it_has_type_constants(): void
    {
        $this->assertContains('transfer', Certificate::TYPES);
        $this->assertContains('character', Certificate::TYPES);
        $this->assertContains('achievement', Certificate::TYPES);
    }

    /** @test */
    public function it_generates_a_number(): void
    {
        $number = Certificate::generateNumber();

        $this->assertStringStartsWith('CERT-' . now()->year . '-', $number);
    }

    /** @test */
    public function it_belongs_to_a_student(): void
    {
        $student = Student::factory()->create();
        $cert = $this->makeCertificate(['student_id' => $student->id]);

        $this->assertInstanceOf(Student::class, $cert->student);
        $this->assertSame($student->id, $cert->student->id);
    }

    /** @test */
    public function it_belongs_to_a_generator(): void
    {
        $user = User::factory()->create();
        $cert = $this->makeCertificate(['generated_by' => $user->id]);

        $this->assertInstanceOf(User::class, $cert->generatedBy);
        $this->assertSame($user->id, $cert->generatedBy->id);
    }
}
