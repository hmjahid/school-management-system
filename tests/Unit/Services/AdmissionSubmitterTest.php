<?php

namespace Tests\Unit\Services;

use App\Models\AcademicSession;
use App\Models\Admission;
use App\Models\Batch;
use App\Services\AdmissionSubmitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdmissionSubmitterTest extends TestCase
{
    use RefreshDatabase;

    protected function makeRequest(array $overrides = []): Request
    {
        $sessionId = AcademicSession::factory()->create()->id;
        $batchId = Batch::create(['name' => 'Batch '.uniqid()])->id;

        $data = array_merge([
            'academic_session_id' => $sessionId,
            'batch_id' => $batchId,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender' => 'female',
            'date_of_birth' => '2010-05-20',
            'email' => 'jane'.uniqid().'@example.com',
            'phone' => '01700000001',
            'address' => '456 Test Road',
            'city' => 'Dhaka',
            'postal_code' => '1209',
            'father_name' => 'John Doe',
            'father_phone' => '01700000002',
            'mother_name' => 'Jane Mother',
            'mother_phone' => '01700000003',
        ], $overrides);

        return Request::create('/admissions', 'POST', $data);
    }

    /** @test */
    public function it_creates_a_submitted_admission(): void
    {
        Notification::fake();

        $request = $this->makeRequest();
        $admission = app(AdmissionSubmitter::class)->submitPublicApplication($request);

        $this->assertInstanceOf(Admission::class, $admission);
        $this->assertEquals(Admission::STATUS_SUBMITTED, $admission->status);
        $this->assertNotNull($admission->submitted_at);
        $this->assertDatabaseHas('admissions', ['id' => $admission->id, 'status' => Admission::STATUS_SUBMITTED]);
    }

    /** @test */
    public function it_sends_submission_notification(): void
    {
        Notification::fake();

        $request = $this->makeRequest(['email' => 'notify'.uniqid().'@example.com']);
        $admission = app(AdmissionSubmitter::class)->submitPublicApplication($request);

        Notification::assertSentTo(
            new \Illuminate\Notifications\AnonymousNotifiable,
            \App\Notifications\AdmissionSubmittedNotification::class
        );

        $this->assertNotNull($admission->id);
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $request = Request::create('/admissions', 'POST', []);

        app(AdmissionSubmitter::class)->submitPublicApplication($request);
    }

    /** @test */
    public function it_rejects_duplicate_email(): void
    {
        Notification::fake();

        $email = 'dup'.uniqid().'@example.com';
        $first = $this->makeRequest(['email' => $email]);
        app(AdmissionSubmitter::class)->submitPublicApplication($first);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $second = $this->makeRequest(['email' => $email]);
        app(AdmissionSubmitter::class)->submitPublicApplication($second);
    }

    /** @test */
    public function it_stores_no_documents_when_no_files_uploaded(): void
    {
        Notification::fake();

        $request = $this->makeRequest();
        $admission = app(AdmissionSubmitter::class)->submitPublicApplication($request);

        $this->assertCount(0, $admission->documents);
        $this->assertNull($admission->metadata['uploaded_files'] ?? null);
    }
}
