<?php

namespace Tests\Unit\Models;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeaveTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_persists_key_columns(): void
    {
        $type = LeaveType::create([
            'name_en' => 'Annual Leave',
            'name_bn' => 'বার্ষিক ছুটি',
            'days_per_year' => 15,
            'is_paid' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('leave_types', [
            'name_en' => 'Annual Leave',
            'name_bn' => 'বার্ষিক ছুটি',
            'days_per_year' => 15,
            'is_paid' => true,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_casts_columns_correctly(): void
    {
        $type = LeaveType::create([
            'name_en' => 'Sick Leave',
            'days_per_year' => 10,
            'is_paid' => 1,
            'is_active' => 0,
        ]);

        $this->assertSame(10, $type->days_per_year);
        $this->assertTrue($type->is_paid);
        $this->assertFalse($type->is_active);
    }

    #[Test]
    public function its_name_accessor_returns_english_by_default(): void
    {
        $type = new LeaveType([
            'name_en' => 'Casual Leave',
            'name_bn' => 'ক্যাজুয়াল ছুটি',
        ]);

        $this->assertEquals('Casual Leave', $type->name());
    }

    #[Test]
    public function its_name_accessor_returns_bengali_when_locale_is_bn(): void
    {
        \Illuminate\Support\Facades\App::setLocale('bn');

        $type = new LeaveType([
            'name_en' => 'Casual Leave',
            'name_bn' => 'ক্যাজুয়াল ছুটি',
        ]);

        $this->assertEquals('ক্যাজুয়াল ছুটি', $type->name());

        \Illuminate\Support\Facades\App::setLocale('en');
    }

    #[Test]
    public function its_name_accessor_falls_back_to_english_when_bengali_empty(): void
    {
        \Illuminate\Support\Facades\App::setLocale('bn');

        $type = new LeaveType([
            'name_en' => 'Casual Leave',
            'name_bn' => null,
        ]);

        $this->assertEquals('Casual Leave', $type->name());

        \Illuminate\Support\Facades\App::setLocale('en');
    }

    #[Test]
    public function it_has_many_requests(): void
    {
        $type = LeaveType::create(['name_en' => 'Annual Leave', 'days_per_year' => 15]);
        $teacher = Teacher::create(['user_id' => User::factory()->create()->id]);

        $request = LeaveRequest::create([
            'teacher_id' => $teacher->id,
            'leave_type_id' => $type->id,
            'from_date' => '2026-01-01',
            'to_date' => '2026-01-03',
            'reason' => 'Vacation',
            'status' => 'pending',
        ]);

        $this->assertTrue($type->requests->contains($request));
    }
}
