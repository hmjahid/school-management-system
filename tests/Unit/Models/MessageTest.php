<?php

namespace Tests\Unit\Models;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_key_columns(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'subject' => 'Hello',
            'body' => 'Message body',
        ]);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'body' => 'Message body',
        ]);

        $this->assertNull($message->read_at);
    }

    /** @test */
    public function it_belongs_to_sender_and_receiver(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'body' => 'Body',
        ]);

        $this->assertInstanceOf(User::class, $message->sender);
        $this->assertInstanceOf(User::class, $message->receiver);
        $this->assertSame($sender->id, $message->sender->id);
        $this->assertSame($receiver->id, $message->receiver->id);
    }

    /** @test */
    public function it_scopes_unread_messages(): void
    {
        $s = User::factory()->create();
        $r = User::factory()->create();

        Message::create(['sender_id' => $s->id, 'receiver_id' => $r->id, 'body' => 'Unread']);
        Message::create([
            'sender_id' => $s->id,
            'receiver_id' => $r->id,
            'body' => 'Read',
            'read_at' => now(),
        ]);

        $this->assertCount(1, Message::unread()->get());
    }

    /** @test */
    public function it_marks_a_message_as_read(): void
    {
        $s = User::factory()->create();
        $r = User::factory()->create();

        $message = Message::create([
            'sender_id' => $s->id,
            'receiver_id' => $r->id,
            'body' => 'Body',
        ]);

        $message->markAsRead();

        $this->assertNotNull($message->fresh()->read_at);
    }
}
