<?php

namespace Tests\Unit\Models;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_required_columns(): void
    {
        $user = User::factory()->create();

        $activity = Activity::create([
            'user_id' => $user->id,
            'type' => 'login',
            'title' => 'User logged in',
            'message' => 'A user signed in',
        ]);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'user_id' => $user->id,
            'type' => 'login',
            'title' => 'User logged in',
        ]);
    }

    /** @test */
    public function it_casts_properties_to_array_and_read_at_to_datetime(): void
    {
        $user = User::factory()->create();

        $activity = Activity::create([
            'user_id' => $user->id,
            'type' => 'update',
            'title' => 'Updated',
            'message' => 'msg',
            'properties' => ['ip' => '127.0.0.1'],
            'read_at' => now(),
        ]);

        $this->assertIsArray($activity->properties);
        $this->assertEquals('127.0.0.1', $activity->properties['ip']);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $activity->read_at);
    }

    /** @test */
    public function it_belongs_to_a_user(): void
    {
        $user = User::factory()->create();

        $activity = Activity::create([
            'user_id' => $user->id,
            'type' => 'login',
            'title' => 'T',
            'message' => 'M',
        ]);

        $this->assertTrue($activity->user->is($user));
    }
}
