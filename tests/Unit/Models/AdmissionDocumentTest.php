<?php

namespace Tests\Unit\Models;

use App\Models\Admission;
use App\Models\AdmissionDocument;
use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmission(): Admission
    {
        $session = AcademicSession::factory()->create();
        $batch = Batch::create(['name' => 'Batch ' . uniqid()]);
        $user = User::factory()->create();

        return Admission::create([
            'academic_session_id' => $session->id,
            'batch_id' => $batch->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'date_of_birth' => '2010-01-01',
            'email' => 'john' . uniqid() . '@example.com',
            'phone' => '0170000000' . rand(10, 99),
            'address' => '123 Street',
            'city' => 'Dhaka',
            'postal_code' => '1000',
            'father_name' => 'Father Doe',
            'father_phone' => '01700000001',
            'mother_name' => 'Mother Doe',
            'mother_phone' => '01700000002',
            'created_by' => $user->id,
        ]);
    }

    /** @test */
    public function it_persists_key_columns(): void
    {
        $admission = $this->makeAdmission();

        $doc = AdmissionDocument::create([
            'admission_id' => $admission->id,
            'type' => AdmissionDocument::TYPE_BIRTH_CERTIFICATE,
            'name' => 'Birth Cert',
            'file_path' => '/docs/birth.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 204800,
            'description' => 'Test doc',
        ]);

        $this->assertDatabaseHas('admission_documents', [
            'id' => $doc->id,
            'type' => AdmissionDocument::TYPE_BIRTH_CERTIFICATE,
            'file_size' => 204800,
            'is_approved' => false,
        ]);
        $this->assertFalse($doc->fresh()->is_approved);
    }

    /** @test */
    public function it_returns_the_correct_type_label(): void
    {
        $admission = $this->makeAdmission();

        $doc = AdmissionDocument::create([
            'admission_id' => $admission->id,
            'type' => AdmissionDocument::TYPE_TRANSFER_CERTIFICATE,
            'name' => 'TC',
            'file_path' => '/docs/tc.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        $this->assertSame('Transfer Certificate', $doc->type_label);
    }

    /** @test */
    public function it_formats_the_file_size(): void
    {
        $admission = $this->makeAdmission();

        $doc = AdmissionDocument::create([
            'admission_id' => $admission->id,
            'type' => AdmissionDocument::TYPE_PHOTO,
            'name' => 'Photo',
            'file_path' => '/docs/photo.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => 2048,
        ]);

        $this->assertSame('2.00 KB', $doc->file_size_formatted);
    }

    /** @test */
    public function it_returns_the_file_extension(): void
    {
        $admission = $this->makeAdmission();

        $doc = AdmissionDocument::create([
            'admission_id' => $admission->id,
            'type' => AdmissionDocument::TYPE_MARK_SHEET,
            'name' => 'Marks',
            'file_path' => '/docs/marks.PDF',
            'file_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        $this->assertSame('PDF', $doc->file_extension);
    }

    /** @test */
    public function it_can_be_approved(): void
    {
        $admission = $this->makeAdmission();
        $reviewer = User::factory()->create();

        $doc = AdmissionDocument::create([
            'admission_id' => $admission->id,
            'type' => AdmissionDocument::TYPE_OTHER,
            'name' => 'Other',
            'file_path' => '/docs/other.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        $this->assertTrue($doc->approve('Looks good', $reviewer->id));
        $this->assertTrue($doc->fresh()->is_approved);
        $this->assertSame('Looks good', $doc->fresh()->review_notes);
        $this->assertSame($reviewer->id, $doc->fresh()->reviewed_by);
        $this->assertNotNull($doc->fresh()->reviewed_at);
    }

    /** @test */
    public function it_can_be_rejected(): void
    {
        $admission = $this->makeAdmission();
        $reviewer = User::factory()->create();

        $doc = AdmissionDocument::create([
            'admission_id' => $admission->id,
            'type' => AdmissionDocument::TYPE_CHARACTER_CERTIFICATE,
            'name' => 'CC',
            'file_path' => '/docs/cc.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        $this->assertTrue($doc->reject('Incomplete', $reviewer->id));
        $this->assertFalse($doc->fresh()->is_approved);
        $this->assertSame('Incomplete', $doc->fresh()->review_notes);
    }

    /** @test */
    public function it_belongs_to_an_admission(): void
    {
        $admission = $this->makeAdmission();

        $doc = AdmissionDocument::create([
            'admission_id' => $admission->id,
            'type' => AdmissionDocument::TYPE_MIGRATION_CERTIFICATE,
            'name' => 'MC',
            'file_path' => '/docs/mc.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        $this->assertInstanceOf(Admission::class, $doc->admission);
        $this->assertSame($admission->id, $doc->admission->id);
    }

    /** @test */
    public function it_belongs_to_a_reviewer(): void
    {
        $admission = $this->makeAdmission();
        $reviewer = User::factory()->create();

        $doc = AdmissionDocument::create([
            'admission_id' => $admission->id,
            'type' => AdmissionDocument::TYPE_OTHER,
            'name' => 'Other',
            'file_path' => '/docs/other.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024,
            'reviewed_by' => $reviewer->id,
        ]);

        $this->assertInstanceOf(User::class, $doc->reviewedBy);
        $this->assertSame($reviewer->id, $doc->reviewedBy->id);
    }
}
