<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    private function makeEvent(array $attributes = []): Event
    {
        return Event::create(array_merge([
            'created_by' => User::factory()->create()->id,
            'title' => 'Annual Day',
            'start_date' => now()->addDays(1),
            'status' => 'published',
        ], $attributes));
    }

    #[Test]
    public function it_persists_key_columns(): void
    {
        $user = User::factory()->create();

        $event = Event::create([
            'created_by' => $user->id,
            'title' => 'Science Fair',
            'start_date' => now()->addDay(),
            'status' => 'draft',
            'is_virtual' => true,
        ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Science Fair',
            'status' => 'draft',
        ]);

        $this->assertTrue($event->is_virtual);
        $this->assertInstanceOf(User::class, $event->createdBy);
    }

    #[Test]
    public function it_belongs_to_many_attendees(): void
    {
        $event = $this->makeEvent();
        $user = User::factory()->create();

        $event->attendees()->attach($user->id, ['status' => 'registered']);

        $this->assertCount(1, $event->attendees);
        $this->assertSame('registered', $event->attendees->first()->pivot->status);
    }

    #[Test]
    public function it_scopes_upcoming_published_events(): void
    {
        $this->makeEvent(['title' => 'Upcoming', 'start_date' => now()->addDay(), 'status' => 'published']);
        $this->makeEvent(['title' => 'Past', 'start_date' => now()->subDay(), 'status' => 'published']);
        $this->makeEvent(['title' => 'Draft', 'start_date' => now()->addDay(), 'status' => 'draft']);

        $this->assertCount(1, Event::upcoming()->get());
    }

    #[Test]
    public function it_detects_registration_open(): void
    {
        $open = $this->makeEvent(['registration_deadline' => now()->addDay()]);
        $closed = $this->makeEvent(['registration_deadline' => now()->subDay()]);
        $noDeadline = $this->makeEvent(['registration_deadline' => null]);

        $this->assertTrue($open->isRegistrationOpen());
        $this->assertFalse($closed->isRegistrationOpen());
        $this->assertTrue($noDeadline->isRegistrationOpen());
    }

    #[Test]
    public function it_detects_full_events(): void
    {
        $event = $this->makeEvent(['max_attendees' => 1]);
        $this->assertFalse($event->isFull());

        $user = User::factory()->create();
        $event->attendees()->attach($user->id, ['status' => 'registered']);
        $this->assertTrue($event->isFull());
    }

    #[Test]
    public function it_soft_deletes(): void
    {
        $event = $this->makeEvent();
        $event->delete();

        $this->assertSoftDeleted('events', ['id' => $event->id]);
        $this->assertCount(0, Event::all());
    }
}
