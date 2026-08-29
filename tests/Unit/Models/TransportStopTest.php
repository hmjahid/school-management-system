<?php

namespace Tests\Unit\Models;

use App\Models\TransportRoute;
use App\Models\TransportStop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransportStopTest extends TestCase
{
    use RefreshDatabase;

    private function makeRoute(): TransportRoute
    {
        return TransportRoute::create(['name' => 'Stop Route', 'code' => 'CODE' . uniqid()]);
    }

    /** @test */
    public function it_persists_key_columns(): void
    {
        $route = $this->makeRoute();
        $stop = TransportStop::create([
            'route_id' => $route->id,
            'name' => 'Central Stop',
            'pickup_time' => '08:00:00',
            'drop_time' => '15:00:00',
            'sort' => 3,
        ]);

        $this->assertDatabaseHas('transport_stops', [
            'id' => $stop->id,
            'name' => 'Central Stop',
            'sort' => 3,
        ]);
        $this->assertEquals(3, $stop->sort);
    }

    /** @test */
    public function it_defaults_sort_to_zero_and_times_are_nullable(): void
    {
        $route = $this->makeRoute();
        $stop = TransportStop::create(['route_id' => $route->id, 'name' => 'No Time Stop']);

        $this->assertEquals(0, $stop->sort);
        $this->assertNull($stop->pickup_time);
        $this->assertNull($stop->drop_time);
    }

    /** @test */
    public function it_belongs_to_a_route(): void
    {
        $route = $this->makeRoute();
        $stop = TransportStop::create(['route_id' => $route->id, 'name' => 'R Stop']);

        $this->assertInstanceOf(TransportRoute::class, $stop->route);
        $this->assertEquals($route->id, $stop->route->id);
    }

    /** @test */
    public function it_formats_time_casts(): void
    {
        $route = $this->makeRoute();
        $stop = TransportStop::create([
            'route_id' => $route->id,
            'name' => 'Timed',
            'pickup_time' => '08:30:00',
        ]);

        $this->assertEquals('08:30', $stop->pickup_time->format('H:i'));
    }
}
