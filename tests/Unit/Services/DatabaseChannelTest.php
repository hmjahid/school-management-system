<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\Channels\DatabaseChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Tests\TestCase;

class DatabaseChannelTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(): User
    {
        return User::factory()->create(['email' => 'dbchan@example.com']);
    }

    /** @test */
    public function it_stores_a_database_notification_for_a_user(): void
    {
        $user = $this->makeUser();

        $notification = new class extends Notification {
            public function toArray($notifiable)
            {
                return ['hello' => 'world', 'count' => 3];
            }
        };
        $notification->id = (string) \Illuminate\Support\Str::uuid();

        $channel = new DatabaseChannel();
        $record = $channel->send($user, $notification);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
            'type' => get_class($notification),
        ]);

        $this->assertSame('world', $record->data['hello']);
        $this->assertNull($record->read_at);
    }

    /** @test */
    public function it_uses_the_to_database_method_when_available(): void
    {
        $user = $this->makeUser();

        $notification = new class extends Notification {
            public function toDatabase($notifiable)
            {
                return ['via' => 'database-channel'];
            }
        };
        $notification->id = (string) \Illuminate\Support\Str::uuid();

        $channel = new DatabaseChannel();
        $record = $channel->send($user, $notification);

        $this->assertSame('database-channel', $record->data['via']);
    }
}
