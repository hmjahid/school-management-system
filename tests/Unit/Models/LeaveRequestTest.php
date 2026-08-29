<?php

namespace Tests\Unit\Models;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    private function makeTeacher(): Teacher
    {
        return Teacher::create(['user_id' => User::factory()->create()->id, 'status' => 'active']);
    }

    private function makeLeave(array $overrides = []): LeaveRequest
    {
        return LeaveRequest::create(array_merge([
            'teacher_id' => $this->makeTeacher()->id,
            'leave_type_id' => LeaveType::create(['name_en' => 'Sick Leave'])->id,
            'from_date' => now()->subDays(2),
            'to_date' => now()->subDay(),
            'reason' => 'Personal',
            'status' => LeaveRequest::STATUS_PENDING,
        ], $overrides));
    }

    /** @test */
    public function it_exposes_status_constants(): void
    {
        $this->assertEquals('pending', LeaveRequest::STATUS_PENDING);
        $this->assertEquals('approved', LeaveRequest::STATUS_APPROVED);
        $this->assertEquals('rejected', LeaveRequest::STATUS_REJECTED);
        $this->assertEquals('cancelled', LeaveRequest::STATUS_CANCELLED);
    }

    /** @test */
    public function days_counts_inclusive_range(): void
    {
        // from 2 days ago to 1 day ago => 2 days
        $this->assertEquals(2, $this->makeLeave()->days());
    }

    /** @test */
    public function approve_sets_status_and_approver(): void
    {
        $leave = $this->makeLeave();
        $approver = User::factory()->create();

        $this->assertTrue($leave->approve($approver->id, 'Approved'));

        $leave->refresh();
        $this->assertEquals(LeaveRequest::STATUS_APPROVED, $leave->status);
        $this->assertEquals($approver->id, $leave->approver_id);
        $this->assertEquals('Approved', $leave->approver_note);
        $this->assertNotNull($leave->decided_at);
    }

    /** @test */
    public function reject_sets_status_and_approver(): void
    {
        $leave = $this->makeLeave();
        $approver = User::factory()->create();

        $leave->reject($approver->id, 'Not eligible');

        $leave->refresh();
        $this->assertEquals(LeaveRequest::STATUS_REJECTED, $leave->status);
        $this->assertEquals($approver->id, $leave->approver_id);
    }

    /** @test */
    public function cancel_sets_cancelled_status(): void
    {
        $leave = $this->makeLeave();

        $leave->cancel();

        $leave->refresh();
        $this->assertEquals(LeaveRequest::STATUS_CANCELLED, $leave->status);
        $this->assertNotNull($leave->decided_at);
    }
}
