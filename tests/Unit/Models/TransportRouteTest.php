<?php

namespace Tests\Unit\Models;

use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransportRouteTest extends TestCase
{
    use RefreshDatabase;

    private function makeVehicle(): Vehicle
    {
        return Vehicle::create(['number' => 'CODE'.uniqid()]);
    }

    #[Test]
    public function it_persists_key_columns(): void
    {
        $vehicle = $this->makeVehicle();
        $route = TransportRoute::create([
            'name' => 'City Route',
            'code' => 'CODE'.uniqid(),
            'fare' => 500.00,
            'vehicle_id' => $vehicle->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('transport_routes', [
            'id' => $route->id,
            'name' => 'City Route',
            'fare' => 500.00,
        ]);
        $this->assertSame('500.00', $route->fare);
        $this->assertTrue($route->is_active);
    }

    #[Test]
    public function it_defaults_is_active_to_true_and_fare_to_zero(): void
    {
        $route = TransportRoute::create(['name' => 'Default Route', 'code' => 'CODE'.uniqid()])->fresh();

        $this->assertTrue($route->is_active);
        $this->assertEquals('0.00', $route->fare);
    }

    #[Test]
    public function it_belongs_to_a_vehicle(): void
    {
        $vehicle = $this->makeVehicle();
        $route = TransportRoute::create(['name' => 'R', 'code' => 'CODE'.uniqid(), 'vehicle_id' => $vehicle->id]);

        $this->assertInstanceOf(Vehicle::class, $route->vehicle);
        $this->assertEquals($vehicle->id, $route->vehicle->id);
    }

    #[Test]
    public function it_has_many_stops_ordered_by_sort(): void
    {
        $route = TransportRoute::create(['name' => 'R', 'code' => 'CODE'.uniqid()]);
        TransportStop::create(['route_id' => $route->id, 'name' => 'Stop 2', 'sort' => 2]);
        TransportStop::create(['route_id' => $route->id, 'name' => 'Stop 1', 'sort' => 1]);

        $this->assertCount(2, $route->stops);
        $this->assertEquals('Stop 1', $route->stops->first()->name);
        $this->assertEquals('Stop 2', $route->stops->last()->name);
    }

    #[Test]
    public function it_has_many_assignments(): void
    {
        $route = TransportRoute::create(['name' => 'R', 'code' => 'CODE'.uniqid()]);
        $student = \App\Models\Student::factory()->create();
        \App\Models\TransportAssignment::create([
            'student_id' => $student->id,
            'route_id' => $route->id,
            'effective_from' => now()->toDateString(),
        ]);

        $this->assertCount(1, $route->assignments);
        $this->assertInstanceOf(\App\Models\TransportAssignment::class, $route->assignments->first());
    }
}
