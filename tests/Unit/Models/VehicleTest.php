<?php

namespace Tests\Unit\Models;

use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_key_columns(): void
    {
        $vehicle = Vehicle::create([
            'number' => 'CODE' . uniqid(),
            'type' => 'Bus',
            'capacity' => 40,
            'driver_name' => 'Mr. Rahman',
            'driver_phone' => '01722222222',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'type' => 'Bus',
            'capacity' => 40,
        ]);
        $this->assertSame(40, $vehicle->capacity);
        $this->assertTrue($vehicle->is_active);
    }

    /** @test */
    public function it_defaults_is_active_to_true_and_capacity_to_zero(): void
    {
        $vehicle = Vehicle::create(['number' => 'CODE' . uniqid()])->fresh();

        $this->assertTrue($vehicle->is_active);
        $this->assertEquals(0, $vehicle->capacity);
    }

    /** @test */
    public function it_has_many_routes(): void
    {
        $vehicle = Vehicle::create(['number' => 'CODE' . uniqid()]);
        TransportRoute::create(['name' => 'Route A', 'code' => 'CODE' . uniqid(), 'vehicle_id' => $vehicle->id]);
        TransportRoute::create(['name' => 'Route B', 'code' => 'CODE' . uniqid(), 'vehicle_id' => $vehicle->id]);

        $this->assertCount(2, $vehicle->routes);
        $this->assertInstanceOf(TransportRoute::class, $vehicle->routes->first());
    }
}
