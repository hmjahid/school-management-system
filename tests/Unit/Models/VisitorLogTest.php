<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorLogTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_required_columns(): void
    {
        $log = VisitorLog::create([
            'ip' => '127.0.0.1',
            'url' => '/home',
            'method' => 'GET',
        ]);

        $this->assertDatabaseHas('visitor_logs', [
            'ip' => '127.0.0.1',
            'url' => '/home',
            'method' => 'GET',
        ]);
    }

    /** @test */
    public function it_has_no_timestamps_and_sets_created_at_on_create(): void
    {
        $log = VisitorLog::create([
            'ip' => '10.0.0.1',
            'url' => '/about',
            'method' => 'POST',
        ]);

        $this->assertFalse(VisitorLog::first()->timestamps);
        $this->assertNotNull($log->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $log->created_at);
    }

    /** @test */
    public function it_is_optional_belongs_to_a_user(): void
    {
        $user = User::factory()->create();

        $log = VisitorLog::create([
            'ip' => '192.168.1.1',
            'url' => '/dashboard',
            'method' => 'GET',
            'user_id' => $user->id,
        ]);

        $this->assertTrue($log->user->is($user));

        $anonymous = VisitorLog::create([
            'ip' => '192.168.1.2',
            'url' => '/',
            'method' => 'GET',
        ]);

        $this->assertNull($anonymous->user);
    }
}
