<?php

namespace Tests\Unit\Models;

use App\Models\ScheduledNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeNotification(array $attributes = []): ScheduledNotification
    {
        return ScheduledNotification::create(array_merge([
            'name' => 'Daily Reminder',
            'type' => 'reminder',
            'channels' => ['email'],
            'recipients' => ['user:1'],
            'data' => ['body' => 'hi'],
            'schedule' => ['type' => 'once'],
            'scheduled_at' => now()->addHour(),
            'status' => 'pending',
        ], $attributes));
    }

    /** @test */
    public function it_persists_required_columns_and_casts(): void
    {
        $notification = $this->makeNotification();

        $this->assertDatabaseHas('scheduled_notifications', [
            'id' => $notification->id,
            'status' => 'pending',
        ]);
        $this->assertIsArray($notification->channels);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $notification->scheduled_at);
    }

    /** @test */
    public function it_belongs_to_a_creator(): void
    {
        $user = User::factory()->create();
        $notification = $this->makeNotification(['created_by' => $user->id]);

        $this->assertTrue($notification->creator->is($user));
    }

    /** @test */
    public function scope_due_returns_pending_notifications_with_past_schedule(): void
    {
        $this->makeNotification([
            'scheduled_at' => now()->subHour(),
            'status' => 'pending',
        ]);

        $this->makeNotification([
            'scheduled_at' => now()->addHour(),
            'status' => 'pending',
        ]);

        $this->assertEquals(1, ScheduledNotification::due()->count());
    }

    /** @test */
    public function scope_active_excludes_cancelled_and_trashed(): void
    {
        $active = $this->makeNotification();
        $cancelled = $this->makeNotification(['status' => 'cancelled']);
        $trashed = $this->makeNotification();
        $trashed->delete();

        $activeIds = ScheduledNotification::active()->pluck('id')->all();

        $this->assertContains($active->id, $activeIds);
        $this->assertNotContains($cancelled->id, $activeIds);
        $this->assertNotContains($trashed->id, $activeIds);
    }

    /** @test */
    public function it_can_be_marked_sent_and_failed(): void
    {
        $notification = $this->makeNotification();
        $this->assertTrue($notification->markAsSent());
        $this->assertNotNull($notification->fresh()->sent_at);

        $notification2 = $this->makeNotification();
        $this->assertTrue($notification2->markAsFailed('error'));
        $this->assertEquals('failed', $notification2->fresh()->status);
    }

    /** @test */
    public function cancel_prevents_cancelling_sent_notifications(): void
    {
        $pending = $this->makeNotification();
        $this->assertTrue($pending->cancel());
        $this->assertEquals('cancelled', $pending->fresh()->status);

        $sent = $this->makeNotification(['status' => 'sent']);
        $this->assertFalse($sent->cancel());
        $this->assertEquals('sent', $sent->fresh()->status);
    }

    /** @test */
    public function get_next_schedule_computes_recurring_occurrence(): void
    {
        $notification = $this->makeNotification([
            'schedule' => ['type' => 'daily'],
            'scheduled_at' => now()->subDay(),
        ]);

        $next = $notification->getNextSchedule();
        $this->assertNotNull($next);
        $this->assertTrue($next->isFuture());
    }

    /** @test */
    public function it_soft_deletes(): void
    {
        $notification = $this->makeNotification();
        $notification->delete();

        $this->assertTrue($notification->trashed());
        $this->assertNull(ScheduledNotification::find($notification->id));
        $this->assertNotNull(ScheduledNotification::withTrashed()->find($notification->id));
    }
}
