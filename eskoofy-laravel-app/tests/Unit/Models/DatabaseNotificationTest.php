<?php

namespace Tests\Unit\Models;

use App\Models\DatabaseNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeNotification(User $user, array $attributes = []): DatabaseNotification
    {
        $notification = new DatabaseNotification;
        $notification->forceFill(array_merge([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\TestNotification',
            'data' => ['message' => 'hello'],
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ], $attributes));
        $notification->save();

        return $notification;
    }

    #[Test]
    public function it_persists_columns_and_casts_data_to_array(): void
    {
        $user = User::factory()->create();
        $notification = $this->makeNotification($user);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'type' => 'App\\Notifications\\TestNotification',
        ]);
        $this->assertIsArray($notification->data);
        $this->assertEquals('hello', $notification->data['message']);
    }

    #[Test]
    public function it_belongs_to_a_notifiable(): void
    {
        $user = User::factory()->create();
        $notification = $this->makeNotification($user);

        $this->assertTrue($notification->notifiable->is($user));
    }

    #[Test]
    public function mark_as_read_and_unread_toggle_state(): void
    {
        $user = User::factory()->create();
        $notification = $this->makeNotification($user);

        $this->assertTrue($notification->unread());
        $this->assertFalse($notification->read());

        $notification->markAsRead();
        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertTrue($notification->fresh()->read());
        $this->assertFalse($notification->fresh()->unread());

        $notification->markAsUnread();
        $this->assertNull($notification->fresh()->read_at);
    }

    #[Test]
    public function it_exposes_type_data_and_id_accessors(): void
    {
        $user = User::factory()->create();
        $notification = $this->makeNotification($user);

        $this->assertEquals('App\\Notifications\\TestNotification', $notification->type());
        $this->assertEquals(['message' => 'hello'], $notification->data());
        $this->assertEquals($notification->getKey(), $notification->id());
    }
}
