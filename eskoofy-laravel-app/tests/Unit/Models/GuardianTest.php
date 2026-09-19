<?php

namespace Tests\Unit\Models;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GuardianTest extends TestCase
{
    use RefreshDatabase;

    private function makeGuardian(array $overrides = []): Guardian
    {
        return Guardian::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'relation_type' => 'father',
            'present_address' => '5 Park Rd',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'zip_code' => '1212',
            'country' => 'Bangladesh',
        ], $overrides));
    }

    #[Test]
    public function full_address_combines_parts(): void
    {
        $this->assertEquals('5 Park Rd, Dhaka, Dhaka, 1212, Bangladesh', $this->makeGuardian()->full_address);
    }

    #[Test]
    public function status_badge_returns_a_badge_string(): void
    {
        $this->assertStringContainsString('badge', $this->makeGuardian()->status_badge);
    }

    #[Test]
    public function students_relationship_and_total_students_count(): void
    {
        $guardian = $this->makeGuardian();

        $s1 = Student::factory()->create();
        $s2 = Student::factory()->create();

        $guardian->students()->attach($s1->id, ['is_primary' => true, 'relationship' => 'father']);
        $guardian->students()->attach($s2->id, ['is_primary' => false, 'relationship' => 'father']);

        $this->assertCount(2, $guardian->students);
        $this->assertEquals(2, $guardian->total_students);
    }

    #[Test]
    public function primary_students_scope_filters_primary_only(): void
    {
        $guardian = $this->makeGuardian();
        $primary = Student::factory()->create();
        $other = Student::factory()->create();

        $guardian->students()->attach($primary->id, ['is_primary' => true, 'relationship' => 'father']);
        $guardian->students()->attach($other->id, ['is_primary' => false, 'relationship' => 'uncle']);

        $this->assertCount(1, $guardian->primaryStudents);
        $this->assertEquals($primary->id, $guardian->primaryStudents->first()->id);
    }
}
