<?php

namespace Tests\Unit\Models;

use App\Models\Admission;
use App\Models\AcademicSession;
use App\Models\Batch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmission(array $overrides = []): Admission
    {
        $sessionId = AcademicSession::factory()->create()->id;
        $batchId = Batch::create(['name' => 'Batch '.uniqid()])->id;

        return Admission::create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'Applicant',
            'email' => 'test'.uniqid().'@example.com',
            'phone' => '01700000000',
            'date_of_birth' => '2010-01-15',
            'gender' => 'male',
            'city' => 'Dhaka',
            'address' => '123 Test Street',
            'postal_code' => '1207',
            'country' => 'Bangladesh',
            'father_name' => 'Test Father',
            'father_phone' => '01711111111',
            'mother_name' => 'Test Mother',
            'mother_phone' => '01722222222',
            'academic_session_id' => $sessionId,
            'batch_id' => $batchId,
            'status' => Admission::STATUS_DRAFT,
        ], $overrides));
    }

    /** @test */
    public function it_auto_generates_application_number_on_create(): void
    {
        $admission = $this->makeAdmission(['first_name' => 'John', 'last_name' => 'Doe']);

        $this->assertNotNull($admission->application_number);
        $this->assertStringStartsWith('APP'.date('Y'), $admission->application_number);
    }

    /** @test */
    public function it_generates_sequential_application_numbers(): void
    {
        $first = Admission::generateApplicationNumber();
        $this->assertEquals('APP'.date('Y').'00001', $first);

        $this->makeAdmission();

        $second = Admission::generateApplicationNumber();
        $this->assertEquals('APP'.date('Y').'00002', $second);
    }

    /** @test */
    public function it_returns_full_name_attribute(): void
    {
        $admission = $this->makeAdmission(['first_name' => 'Jane', 'last_name' => 'Smith']);

        $this->assertEquals('Jane Smith', $admission->full_name);
    }

    /** @test */
    public function it_returns_correct_status_label(): void
    {
        $admission = $this->makeAdmission(['status' => Admission::STATUS_UNDER_REVIEW]);

        $this->assertEquals('Under review', $admission->status_label);
    }

    /** @test */
    public function it_returns_correct_status_badge(): void
    {
        $approved = $this->makeAdmission(['status' => Admission::STATUS_APPROVED]);
        $this->assertEquals('bg-green-100 text-green-800', $approved->status_badge);

        $rejected = $this->makeAdmission(['status' => Admission::STATUS_REJECTED]);
        $this->assertEquals('bg-red-100 text-red-800', $rejected->status_badge);
    }

    /** @test */
    public function it_checks_state_methods(): void
    {
        $draft = $this->makeAdmission(['status' => Admission::STATUS_DRAFT]);
        $this->assertTrue($draft->isDraft());
        $this->assertFalse($draft->isSubmitted());

        $submitted = $this->makeAdmission(['status' => Admission::STATUS_SUBMITTED]);
        $this->assertTrue($submitted->isSubmitted());
        $this->assertFalse($submitted->isDraft());
    }

    /** @test */
    public function it_submits_draft_admission(): void
    {
        $admission = $this->makeAdmission(['status' => Admission::STATUS_DRAFT]);

        $this->assertTrue($admission->submit());
        $admission->refresh();

        $this->assertEquals(Admission::STATUS_SUBMITTED, $admission->status);
        $this->assertNotNull($admission->submitted_at);
    }

    /** @test */
    public function it_prevents_submitting_non_draft_admission(): void
    {
        $admission = $this->makeAdmission(['status' => Admission::STATUS_SUBMITTED]);

        $this->assertFalse($admission->submit());
    }

    /** @test */
    public function it_approves_submitted_admission(): void
    {
        $admission = $this->makeAdmission(['status' => Admission::STATUS_SUBMITTED]);

        $this->assertTrue($admission->approve('Good candidate'));
        $admission->refresh();

        $this->assertEquals(Admission::STATUS_APPROVED, $admission->status);
        $this->assertEquals('Good candidate', $admission->admission_notes);
        $this->assertNotNull($admission->admission_date);
    }

    /** @test */
    public function it_prevents_approving_non_submittable_admission(): void
    {
        $admission = $this->makeAdmission(['status' => Admission::STATUS_DRAFT]);

        $this->assertFalse($admission->approve());
    }

    /** @test */
    public function it_rejects_submitted_admission(): void
    {
        $admission = $this->makeAdmission(['status' => Admission::STATUS_SUBMITTED]);

        $this->assertTrue($admission->reject('Failed requirements'));
        $admission->refresh();

        $this->assertEquals(Admission::STATUS_REJECTED, $admission->status);
        $this->assertEquals('Failed requirements', $admission->rejection_reason);
    }

    /** @test */
    public function it_prevents_rejecting_non_submittable_admission(): void
    {
        $admission = $this->makeAdmission(['status' => Admission::STATUS_DRAFT]);

        $this->assertFalse($admission->reject('No reason'));
    }

    /** @test */
    public function enroll_returns_null_for_non_approved_admission(): void
    {
        $admission = $this->makeAdmission(['status' => Admission::STATUS_SUBMITTED]);

        $this->assertNull($admission->enroll());
    }

    /** @test */
    public function enroll_returns_null_for_already_enrolled_admission(): void
    {
        $admission = $this->makeAdmission(['status' => Admission::STATUS_ENROLLED]);

        $this->assertNull($admission->enroll());
    }

    /** @test */
    public function it_returns_payment_methods_constant(): void
    {
        $this->assertIsArray(Admission::PAYMENT_METHODS);
        $this->assertContains('bkash', Admission::PAYMENT_METHODS);
        $this->assertContains('cash', Admission::PAYMENT_METHODS);
    }
}
