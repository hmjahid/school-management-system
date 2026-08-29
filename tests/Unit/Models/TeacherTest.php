<?php

namespace Tests\Unit\Models;

use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherTest extends TestCase
{
    use RefreshDatabase;

    private function makeTeacher(array $overrides = []): Teacher
    {
        return Teacher::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'status' => 'active',
        ], $overrides));
    }

    /** @test */
    public function full_address_combines_parts(): void
    {
        $teacher = $this->makeTeacher([
            'present_address' => '10 Lake View',
            'city' => 'Chittagong',
            'state' => 'Chittagong',
            'zip_code' => '4000',
            'country' => 'Bangladesh',
        ]);

        $this->assertEquals('10 Lake View, Chittagong, Chittagong, 4000, Bangladesh', $teacher->full_address);
    }

    /** @test */
    public function status_badge_returns_proper_color(): void
    {
        $this->assertStringContainsString('badge bg-success', $this->makeTeacher(['status' => 'active'])->status_badge);
        $this->assertStringContainsString('badge bg-secondary', $this->makeTeacher(['status' => 'inactive'])->status_badge);
        $this->assertStringContainsString('badge bg-warning', $this->makeTeacher(['status' => 'on_leave'])->status_badge);
        $this->assertStringContainsString('badge bg-dark', $this->makeTeacher(['status' => 'retired'])->status_badge);
    }

    /** @test */
    public function is_class_teacher_true_when_attached_as_class_teacher(): void
    {
        $teacher = $this->makeTeacher();
        $class = SchoolClass::factory()->create();

        $teacher->classes()->attach($class->id, ['is_class_teacher' => true]);

        $this->assertTrue($teacher->isClassTeacher($class));
    }

    /** @test */
    public function is_class_teacher_false_when_not_class_teacher_of_given_class(): void
    {
        $teacher = $this->makeTeacher();
        $class = SchoolClass::factory()->create();
        $other = SchoolClass::factory()->create();

        $teacher->classes()->attach($class->id, ['is_class_teacher' => false]);

        $this->assertFalse($teacher->isClassTeacher($other));
    }
}
