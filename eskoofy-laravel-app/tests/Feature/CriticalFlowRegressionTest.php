<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CriticalFlowRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $role = Role::findOrCreate('admin');
        $role->givePermissionTo(Permission::findOrCreate('view_students'));
        $role->givePermissionTo(Permission::findOrCreate('view_admissions'));

        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function seedAcademicData(): AcademicSession
    {
        $session = AcademicSession::create([
            'name' => '2026',
            'code' => '2026',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'is_active' => true,
        ]);
        Batch::create(['name' => 'Batch A']);
        SchoolClass::create(['name' => 'Class One']);
        $studentUser = User::factory()->create();
        Student::create([
            'user_id' => $studentUser->id,
            'class_id' => SchoolClass::first()->id,
            'admission_number' => 'R-1001',
            'admission_date' => now()->toDateString(),
            'first_name' => 'Test',
            'last_name' => 'Student',
        ]);

        return $session;
    }

    public function test_public_site_critical_pages_return_200(): void
    {
        $this->seedAcademicData();

        foreach (['/', '/results', '/admissions/apply', '/contact'] as $path) {
            $this->get($path)->assertStatus(200);
        }
    }

    public function test_admin_dashboard_critical_pages_return_200(): void
    {
        $user = $this->admin();
        $this->seedAcademicData();

        $paths = [
            route('dashboard'),
            route('dashboard.students'),
            route('dashboard.fee-payments.index'),
            route('dashboard.admissions.index'),
            route('dashboard.communications'),
        ];

        foreach ($paths as $path) {
            $response = $this->actingAs($user)->get($path);
            $this->assertSame(200, $response->status(), 'Path failed with '.$response->status().': '.$path.' | '.substr(strip_tags((string) $response->getContent()), 0, 200));
        }
    }

    public function test_result_lookup_api_still_enveloped(): void
    {
        $this->seedAcademicData();

        $this->getJson('/api/v1/academics/results/lookup?roll=999999')
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'errors']);
    }
}
