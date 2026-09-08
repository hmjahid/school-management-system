<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Fee;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FeesPageFilterTest extends TestCase
{
    use RefreshDatabase;

    private function seedClassAndSection(): array
    {
        $class = SchoolClass::create(['name' => 'Class One']);
        $year = AcademicYear::create([
            'name' => (string) now()->year,
            'session' => (string) now()->year,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'is_current' => true,
        ]);
        $section = Section::create([
            'name' => 'A',
            'slug' => 'sec-a-'.now()->timestamp,
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
        ]);

        return [$class, $section];
    }

    private function admin(): User
    {
        Role::create(['name' => 'admin']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_fees_page_accepts_class_section_status_and_fee_type_filters(): void
    {
        $admin = $this->admin();
        [$class, $section] = $this->seedClassAndSection();

        Fee::create([
            'name' => 'Monthly Tuition',
            'amount' => 500,
            'fee_type' => 'tuition',
            'status' => 'active',
            'class_id' => $class->id,
            'section_id' => $section->id,
        ]);
        Fee::create([
            'name' => 'Recess Exam Fee',
            'amount' => 200,
            'fee_type' => 'exam',
            'status' => 'inactive',
            'class_id' => $class->id,
            'section_id' => $section->id,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard.fees', [
                'class_id' => $class->id,
                'section_id' => $section->id,
                'status' => 'active',
                'fee_type' => 'tuition',
            ]))
            ->assertStatus(200)
            ->assertSee('Monthly Tuition')
            ->assertDontSee('Recess Exam Fee');
    }

    public function test_fees_page_lists_all_fees_without_filters(): void
    {
        $admin = $this->admin();
        [$class, $section] = $this->seedClassAndSection();

        Fee::create([
            'name' => 'Transport Fee',
            'amount' => 300,
            'fee_type' => 'transport',
            'status' => 'active',
            'class_id' => $class->id,
            'section_id' => $section->id,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard.fees'))
            ->assertStatus(200)
            ->assertSee('Transport Fee');
    }

    public function test_fees_page_denies_regular_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.fees'))
            ->assertStatus(403);
    }
}
