<?php

namespace Tests\Unit\Models;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationLogTest extends TestCase
{
    use RefreshDatabase;

    private function makeLog(array $attributes = []): NotificationLog
    {
        $user = User::factory()->create();

        $log = new NotificationLog();
        $log->forceFill(array_merge([
            'type' => 'refund_created',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'channel' => 'email',
            'status' => NotificationLog::STATUS_PENDING,
        ], $attributes));
        $log->save();

        return $log;
    }

    /** @test */
    public function it_persists_columns_and_casts(): void
    {
        $log = $this->makeLog([
            'status' => NotificationLog::STATUS_SENT,
            'metadata' => ['provider' => 'ses'],
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'id' => $log->id,
            'status' => NotificationLog::STATUS_SENT,
        ]);
        $this->assertIsArray($log->metadata);
        $this->assertEquals('ses', $log->metadata['provider']);
    }

    /** @test */
    public function it_belongs_to_a_notifiable(): void
    {
        $user = User::factory()->create();
        $log = $this->makeLog(['notifiable_id' => $user->id]);

        $this->assertTrue($log->notifiable->is($user));
    }

    /** @test */
    public function status_scopes_filter_correctly(): void
    {
        $this->makeLog(['status' => NotificationLog::STATUS_PENDING]);
        $this->makeLog(['status' => NotificationLog::STATUS_SENT]);
        $this->makeLog(['status' => NotificationLog::STATUS_FAILED]);
        $this->makeLog(['status' => NotificationLog::STATUS_DELIVERED]);
        $this->makeLog(['status' => NotificationLog::STATUS_OPENED]);

        $this->assertEquals(1, NotificationLog::pending()->count());
        $this->assertEquals(1, NotificationLog::sent()->count());
        $this->assertEquals(1, NotificationLog::failed()->count());
        $this->assertEquals(1, NotificationLog::delivered()->count());
        $this->assertEquals(1, NotificationLog::opened()->count());
        $this->assertEquals(5, NotificationLog::forChannel('email')->count());
    }

    /** @test */
    public function mark_as_sent_sets_status_and_timestamp(): void
    {
        $log = $this->makeLog();
        $this->assertTrue($log->markAsSent());

        $this->assertEquals(NotificationLog::STATUS_SENT, $log->fresh()->status);
        $this->assertNotNull($log->fresh()->sent_at);
    }

    /** @test */
    public function mark_as_failed_records_error_message(): void
    {
        $log = $this->makeLog();
        $log->markAsFailed('boom');

        $fresh = $log->fresh();
        $this->assertEquals(NotificationLog::STATUS_FAILED, $fresh->status);
        $this->assertEquals('boom', $fresh->error_message);
    }

    /** @test */
    public function get_delivery_time_returns_seconds_between_sent_and_delivered(): void
    {
        $sent = now()->subMinutes(2);
        $delivered = $sent->copy()->addSeconds(30);
        $log = $this->makeLog([
            'status' => NotificationLog::STATUS_DELIVERED,
            'sent_at' => $sent,
            'delivered_at' => $delivered,
        ]);

        $this->assertEquals(30.0, $log->getDeliveryTime());
    }

    /** @test */
    public function get_statuses_returns_all_available_statuses(): void
    {
        $statuses = NotificationLog::getStatuses();
        $this->assertArrayHasKey(NotificationLog::STATUS_PENDING, $statuses);
        $this->assertArrayHasKey(NotificationLog::STATUS_COMPLAINED, $statuses);
    }
}
