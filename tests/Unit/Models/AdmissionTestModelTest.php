<?php

namespace Tests\Unit\Models;

use App\Models\AcademicSession;
use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdmissionTestModelTest extends TestCase
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

    #[Test]
    public function it_persists_required_columns(): void
    {
        $admission = $this->makeAdmission();

        $test = AdmissionTest::create([
            'admission_id' => $admission->id,
            'scheduled_at' => '2026-04-01 10:00:00',
            'venue' => 'Main Hall',
            'status' => 'scheduled',
            'notes' => 'Bring documents',
            'created_by' => User::factory()->create()->id,
        ]);

        $this->assertDatabaseHas('admission_tests', [
            'admission_id' => $admission->id,
            'venue' => 'Main Hall',
            'status' => 'scheduled',
            'notes' => 'Bring documents',
        ]);
    }

    #[Test]
    public function it_defaults_status_to_scheduled(): void
    {
        $admission = $this->makeAdmission();

        $test = AdmissionTest::create([
            'admission_id' => $admission->id,
        ]);

        $this->assertDatabaseHas('admission_tests', [
            'id' => $test->id,
            'status' => 'scheduled',
        ]);
    }

    #[Test]
    public function it_casts_scheduled_at_to_datetime(): void
    {
        $admission = $this->makeAdmission();

        $test = AdmissionTest::create([
            'admission_id' => $admission->id,
            'scheduled_at' => '2026-04-01 10:00:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $test->scheduled_at);
    }

    #[Test]
    public function it_belongs_to_an_admission(): void
    {
        $admission = $this->makeAdmission();

        $test = AdmissionTest::create([
            'admission_id' => $admission->id,
        ]);

        $this->assertTrue($test->admission->is($admission));
    }
}
