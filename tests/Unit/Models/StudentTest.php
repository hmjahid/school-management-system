<?php

namespace Tests\Unit\Models;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(array $overrides = []): Student
    {
        return Student::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'class_id' => SchoolClass::factory()->create()->id,
            'admission_no' => 'ADM'.uniqid(),
            'admission_number' => 'ADMN'.uniqid(),
            'admission_date' => now()->toDateString(),
            'first_name' => 'Ayesha',
            'last_name' => 'Rahman',
            'gender' => 'female',
            'present_address' => '12 Main St',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'zip_code' => '1207',
            'country' => 'Bangladesh',
            'status' => 'active',
        ], $overrides));
    }

    /** @test */
    public function it_builds_full_address_from_parts(): void
    {
        $student = $this->makeStudent();

        $this->assertEquals('12 Main St, Dhaka, Dhaka, 1207, Bangladesh', $student->full_address);
    }

    /** @test */
    public function full_address_omits_empty_parts(): void
    {
        $student = $this->makeStudent([
            'present_address' => 'Only Street',
            'city' => null,
            'state' => null,
            'zip_code' => null,
            'country' => 'Bangladesh',
        ]);

        $this->assertEquals('Only Street, Bangladesh', $student->full_address);
    }

    /** @test */
    public function status_badge_returns_proper_color(): void
    {
        $this->assertStringContainsString('badge bg-success', $this->makeStudent(['status' => 'active'])->status_badge);
        $this->assertStringContainsString('badge bg-secondary', $this->makeStudent(['status' => 'inactive'])->status_badge);
        $this->assertStringContainsString('badge bg-info', $this->makeStudent(['status' => 'graduated'])->status_badge);
        $this->assertStringContainsString('badge bg-warning', $this->makeStudent(['status' => 'transferred'])->status_badge);
    }

    /** @test */
    public function factory_creates_a_valid_student(): void
    {
        $student = Student::factory()->create();

        $this->assertNotNull($student->id);
        $this->assertNotNull($student->class_id);
        $this->assertNotNull($student->user_id);
    }
}
