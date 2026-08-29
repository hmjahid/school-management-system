<?php

namespace Tests\Unit\Services;

use App\Models\ScheduledNotification;
use App\Services\Notification\ScheduledNotificationService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ScheduledNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function makeServiceWithSendReturn($value): ScheduledNotificationService
    {
        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('send')->andReturn($value);

        return new ScheduledNotificationService($notificationService);
    }

    /** @test */
    public function it_schedules_a_one_time_notification_in_the_future(): void
    {
        $service = $this->makeServiceWithSendReturn(true);

        $scheduled = $service->schedule(
            'Welcome',
            'info',
            ['database'],
            [1],
            ['foo' => 'bar'],
            ['type' => 'once', 'datetime' => now()->addDay()->toDateTimeString()]
        );

        $this->assertInstanceOf(ScheduledNotification::class, $scheduled);
        $this->assertSame('pending', $scheduled->status);
        $this->assertTrue($scheduled->scheduled_at->isFuture());
    }

    /** @test */
    public function it_throws_when_scheduling_a_past_one_time_notification(): void
    {
        $service = $this->makeServiceWithSendReturn(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Scheduled time must be in the future for one-time notifications');

        $service->schedule(
            'Welcome',
            'info',
            ['database'],
            [1],
            [],
            ['type' => 'once', 'datetime' => now()->subDay()->toDateTimeString()]
        );
    }

    /** @test */
    public function it_schedules_a_recurring_daily_notification(): void
    {
        $service = $this->makeServiceWithSendReturn(true);

        $scheduled = $service->schedule(
            'Daily',
            'info',
            ['database'],
            [1],
            [],
            ['type' => 'daily']
        );

        $this->assertSame('daily', $scheduled->schedule['type']);
        $this->assertTrue($scheduled->scheduled_at->isFuture());
        $this->assertEqualsWithDelta(24, now()->diffInHours($scheduled->scheduled_at), 1);
    }

    /** @test */
    public function it_processes_due_notifications_and_marks_them_sent(): void
    {
        ScheduledNotification::create([
            'name' => 'Due',
            'type' => 'info',
            'channels' => ['database'],
            'recipients' => [1],
            'data' => [],
            'schedule' => ['type' => 'once', 'datetime' => now()->subMinute()->toDateTimeString()],
            'scheduled_at' => now()->subMinute(),
            'status' => 'pending',
        ]);

        $service = new ScheduledNotificationService(Mockery::mock(NotificationService::class)
            ->shouldReceive('send')->andReturn(true)->getMock());

        $processed = $service->processDueNotifications();

        $this->assertSame(1, $processed);
        $this->assertSame('sent', ScheduledNotification::first()->status);
    }

    /** @test */
    public function it_marks_due_notifications_failed_when_send_throws(): void
    {
        ScheduledNotification::create([
            'name' => 'Due',
            'type' => 'info',
            'channels' => ['database'],
            'recipients' => [1],
            'data' => [],
            'schedule' => ['type' => 'once', 'datetime' => now()->subMinute()->toDateTimeString()],
            'scheduled_at' => now()->subMinute(),
            'status' => 'pending',
        ]);

        $service = new ScheduledNotificationService(Mockery::mock(NotificationService::class)
            ->shouldReceive('send')->andThrow(new \Exception('boom'))->getMock());

        $processed = $service->processDueNotifications();

        $this->assertSame(0, $processed);
        $this->assertSame('failed', ScheduledNotification::first()->status);
    }

    /** @test */
    public function it_reschedules_recurring_notifications_after_sending(): void
    {
        ScheduledNotification::create([
            'name' => 'Daily',
            'type' => 'info',
            'channels' => ['database'],
            'recipients' => [1],
            'data' => [],
            'schedule' => ['type' => 'daily'],
            'scheduled_at' => now()->subMinute(),
            'status' => 'pending',
        ]);

        $service = new ScheduledNotificationService(Mockery::mock(NotificationService::class)
            ->shouldReceive('send')->andReturn(true)->getMock());

        $service->processDueNotifications();

        $this->assertSame(2, ScheduledNotification::count());
        $this->assertSame('sent', ScheduledNotification::where('status', 'sent')->first()->status);
        $this->assertSame('pending', ScheduledNotification::where('status', 'pending')->first()->status);
    }

    /** @test */
    public function it_returns_upcoming_notifications(): void
    {
        ScheduledNotification::create([
            'name' => 'Upcoming',
            'type' => 'info',
            'channels' => ['database'],
            'recipients' => [1],
            'data' => [],
            'schedule' => ['type' => 'once', 'datetime' => now()->addDay()->toDateTimeString()],
            'scheduled_at' => now()->addDay(),
            'status' => 'pending',
        ]);

        $service = $this->makeServiceWithSendReturn(true);

        $upcoming = $service->getUpcoming();

        $this->assertCount(1, $upcoming);
    }

    /** @test */
    public function it_cancels_a_pending_notification(): void
    {
        $notification = ScheduledNotification::create([
            'name' => 'Cancel',
            'type' => 'info',
            'channels' => ['database'],
            'recipients' => [1],
            'data' => [],
            'schedule' => ['type' => 'once', 'datetime' => now()->addDay()->toDateTimeString()],
            'scheduled_at' => now()->addDay(),
            'status' => 'pending',
        ]);

        $service = $this->makeServiceWithSendReturn(true);

        $this->assertTrue($service->cancel($notification->id));
        $this->assertSame('cancelled', $notification->fresh()->status);
    }

    /** @test */
    public function it_cannot_cancel_an_already_sent_notification(): void
    {
        $notification = ScheduledNotification::create([
            'name' => 'Sent',
            'type' => 'info',
            'channels' => ['database'],
            'recipients' => [1],
            'data' => [],
            'schedule' => ['type' => 'once', 'datetime' => now()->addDay()->toDateTimeString()],
            'scheduled_at' => now()->addDay(),
            'status' => 'sent',
        ]);

        $service = $this->makeServiceWithSendReturn(true);

        $this->assertFalse($service->cancel($notification->id));
    }

    /** @test */
    public function it_returns_stats_counts(): void
    {
        ScheduledNotification::create([
            'name' => 'S1',
            'type' => 'info',
            'channels' => ['database'],
            'recipients' => [1],
            'data' => [],
            'schedule' => ['type' => 'once', 'datetime' => now()->addDay()->toDateTimeString()],
            'scheduled_at' => now()->addDay(),
            'status' => 'pending',
        ]);

        $service = $this->makeServiceWithSendReturn(true);

        $stats = $service->getStats();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['pending']);
        $this->assertSame(0, $stats['sent']);
    }
}
