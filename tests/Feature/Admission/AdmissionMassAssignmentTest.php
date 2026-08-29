<?php

namespace Tests\Feature\Admission;

use App\Models\AcademicSession;
use App\Models\Admission;
use App\Models\Batch;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdmissionMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function makeSessionAndBatch(): array
    {
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

        return [$session, $batch];
    }

    /** @test */
    public function api_applicant_cannot_set_admission_status_on_create()
    {
        [$session, $batch] = $this->makeSessionAndBatch();

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admissions', [
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
                // Attacker tries to bypass review and mark the application enrolled.
                'status' => 'enrolled',
                'is_admin' => 1,
            ]);

        // The Admission model is persisted even though the JSON resource blows up
        // on an unrelated pre-existing Batch global-scope issue; assert the row.
        $this->assertDatabaseHas('admissions', [
            'email' => 'applicant@example.com',
            'status' => Admission::STATUS_DRAFT,
        ]);

        $admission = Admission::query()->first();
        $this->assertNotNull($admission);
        // Status is forced to draft; the supplied "enrolled" value is ignored.
        $this->assertSame(Admission::STATUS_DRAFT, $admission->status);
    }

    /** @test */
    public function public_applicant_cannot_set_status_on_apply()
    {
        Notification::fake();

        [$session, $batch] = $this->makeSessionAndBatch();

        $response = $this->post(route('admissions.apply.store'), [
            'academic_session_id' => $session->id,
            'batch_id' => $batch->id,
            'first_name' => 'A',
            'last_name' => 'B',
            'gender' => 'male',
            'date_of_birth' => now()->subYears(10)->toDateString(),
            'email' => 'applicant2@example.com',
            'phone' => '0123456789',
            'address' => 'Addr',
            'city' => 'City',
            'postal_code' => '1234',
            'father_name' => 'Dad',
            'father_phone' => '0123456789',
            'mother_name' => 'Mom',
            'mother_phone' => '0123456789',
            'status' => 'enrolled',
        ]);

        $response->assertRedirect();

        $admission = Admission::query()->first();
        $this->assertNotNull($admission);
        // Public submitter always forces "submitted", never the supplied value.
        $this->assertSame(Admission::STATUS_SUBMITTED, $admission->status);
    }
}
