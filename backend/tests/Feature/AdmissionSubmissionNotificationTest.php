<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Admission;
use App\Models\Batch;
use App\Notifications\AdmissionSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdmissionSubmissionNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_admission_submission_sends_email_notification(): void
    {
        Notification::fake();

        $session = AcademicSession::create([
            'name' => '2026',
            'code' => '2026',
            'start_date' => now()->subMonths(1)->toDateString(),
            'end_date' => now()->addMonths(11)->toDateString(),
            'is_active' => true,
            'is_current' => true,
        ]);

        $batch = Batch::withoutGlobalScopes()->create([
            'name' => 'Class 1',
            'academic_session_id' => $session->id,
            'is_active' => true,
        ]);

        $payload = [
            'academic_session_id' => $session->id,
            'batch_id' => $batch->id,
            'first_name' => 'A',
            'last_name' => 'B',
            'gender' => 'male',
            'date_of_birth' => now()->subYears(10)->toDateString(),
            'email' => 'applicant@example.com',
            'phone' => '0123456789',
            'address' => 'Addr',
            'city' => 'City',
            'postal_code' => '1234',
            'father_name' => 'Dad',
            'father_phone' => '0123456789',
            'mother_name' => 'Mom',
            'mother_phone' => '0123456789',
        ];

        $res = $this->post(route('admissions.apply.store'), $payload);
        $admission = Admission::query()->first();
        $this->assertNotNull($admission);
        $res->assertRedirect(route('admissions.status', ['application_number' => $admission->application_number]));

        $this->assertDatabaseCount('admissions', 1);

        Notification::assertSentOnDemand(AdmissionSubmittedNotification::class);
    }
}
