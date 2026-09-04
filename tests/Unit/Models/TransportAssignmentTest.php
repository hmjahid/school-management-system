<?php

namespace Tests\Unit\Models;

use App\Models\Student;
use App\Models\TransportAssignment;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransportAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function makeRoute(): TransportRoute
    {
        return TransportRoute::create(['name' => 'Assign Route', 'code' => 'CODE'.uniqid()]);
    }

    private function makeStop(TransportRoute $route): TransportStop
    {
        return TransportStop::create(['route_id' => $route->id, 'name' => 'Stop']);
    }

    #[Test]
    public function it_persists_key_columns(): void
    {
        $route = $this->makeRoute();
        $stop = $this->makeStop($route);
        $student = Student::factory()->create();
        $from = now()->subDays(10)->toDateString();
        $to = now()->addDays(10)->toDateString();

        $assignment = TransportAssignment::create([
            'student_id' => $student->id,
            'route_id' => $route->id,
            'stop_id' => $stop->id,
            'effective_from' => $from,
            'effective_to' => $to,
        ]);

        $this->assertDatabaseHas('transport_assignments', [
            'id' => $assignment->id,
            'stop_id' => $stop->id,
        ]);
        $this->assertSame($from, $assignment->effective_from->toDateString());
        $this->assertSame($to, $assignment->effective_to->toDateString());
    }

    #[Test]
    public function it_allows_null_stop_and_effective_to(): void
    {
        $route = $this->makeRoute();
        $student = Student::factory()->create();

        $assignment = TransportAssignment::create([
            'student_id' => $student->id,
            'route_id' => $route->id,
            'effective_from' => now()->toDateString(),
        ]);

        $this->assertNull($assignment->stop_id);
        $this->assertNull($assignment->effective_to);
    }

    #[Test]
    public function it_belongs_to_a_student(): void
    {
        $route = $this->makeRoute();
        $student = Student::factory()->create();

        $assignment = TransportAssignment::create([
            'student_id' => $student->id,
            'route_id' => $route->id,
            'effective_from' => now()->toDateString(),
        ]);

        $this->assertInstanceOf(Student::class, $assignment->student);
        $this->assertEquals($student->id, $assignment->student->id);
    }

    #[Test]
    public function it_belongs_to_a_route(): void
    {
        $route = $this->makeRoute();
        $student = Student::factory()->create();

        $assignment = TransportAssignment::create([
            'student_id' => $student->id,
            'route_id' => $route->id,
            'effective_from' => now()->toDateString(),
        ]);

        $this->assertInstanceOf(TransportRoute::class, $assignment->route);
        $this->assertEquals($route->id, $assignment->route->id);
    }

    #[Test]
    public function it_belongs_to_a_stop_when_present(): void
    {
        $route = $this->makeRoute();
        $stop = $this->makeStop($route);
        $student = Student::factory()->create();

        $assignment = TransportAssignment::create([
            'student_id' => $student->id,
            'route_id' => $route->id,
            'stop_id' => $stop->id,
            'effective_from' => now()->toDateString(),
        ]);

        $this->assertInstanceOf(TransportStop::class, $assignment->stop);
        $this->assertEquals($stop->id, $assignment->stop->id);
    }

    #[Test]
    public function is_active_returns_true_within_date_range(): void
    {
        $route = $this->makeRoute();
        $student = Student::factory()->create();

        $assignment = TransportAssignment::create([
            'student_id' => $student->id,
            'route_id' => $route->id,
            'effective_from' => now()->subDay()->toDateString(),
            'effective_to' => now()->addDay()->toDateString(),
        ]);

        $this->assertTrue($assignment->isActive());
    }

    #[Test]
    public function is_active_returns_false_before_start(): void
    {
        $route = $this->makeRoute();
        $student = Student::factory()->create();

        $assignment = TransportAssignment::create([
            'student_id' => $student->id,
            'route_id' => $route->id,
            'effective_from' => now()->addDay()->toDateString(),
        ]);

        $this->assertFalse($assignment->isActive());
    }

    #[Test]
    public function is_active_returns_false_after_end(): void
    {
        $route = $this->makeRoute();
        $student = Student::factory()->create();

        $assignment = TransportAssignment::create([
            'student_id' => $student->id,
            'route_id' => $route->id,
            'effective_from' => now()->subDays(10)->toDateString(),
            'effective_to' => now()->subDay()->toDateString(),
        ]);

        $this->assertFalse($assignment->isActive());
    }
}
