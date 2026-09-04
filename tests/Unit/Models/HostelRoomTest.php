<?php

namespace Tests\Unit\Models;

use App\Models\Hostel;
use App\Models\HostelAssignment;
use App\Models\HostelRoom;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HostelRoomTest extends TestCase
{
    use RefreshDatabase;

    private function makeHostel(): Hostel
    {
        return Hostel::create(['name' => 'Test Hostel']);
    }

    #[Test]
    public function it_persists_key_columns(): void
    {
        $hostel = $this->makeHostel();
        $room = HostelRoom::create([
            'hostel_id' => $hostel->id,
            'room_number' => 'R101',
            'room_type' => 'double',
            'capacity' => 2,
            'occupied' => 1,
            'status' => 'available',
        ]);

        $this->assertDatabaseHas('hostel_rooms', [
            'id' => $room->id,
            'room_number' => 'R101',
            'capacity' => 2,
            'occupied' => 1,
        ]);
        $this->assertSame(2, $room->capacity);
        $this->assertSame(1, $room->occupied);
    }

    #[Test]
    public function it_defaults_room_type_capacity_and_occupied(): void
    {
        $hostel = $this->makeHostel();
        $room = HostelRoom::create(['hostel_id' => $hostel->id, 'room_number' => 'R102'])->fresh();

        $this->assertEquals('double', $room->room_type);
        $this->assertEquals(2, $room->capacity);
        $this->assertEquals(0, $room->occupied);
        $this->assertEquals('available', $room->status);
    }

    #[Test]
    public function it_belongs_to_a_hostel(): void
    {
        $hostel = $this->makeHostel();
        $room = HostelRoom::create(['hostel_id' => $hostel->id, 'room_number' => 'R103']);

        $this->assertInstanceOf(Hostel::class, $room->hostel);
        $this->assertEquals($hostel->id, $room->hostel->id);
    }

    #[Test]
    public function it_has_many_assignments(): void
    {
        $hostel = $this->makeHostel();
        $room = HostelRoom::create(['hostel_id' => $hostel->id, 'room_number' => 'R104']);
        $student = Student::factory()->create();
        HostelAssignment::create([
            'student_id' => $student->id,
            'room_id' => $room->id,
            'check_in_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->assertCount(1, $room->assignments);
        $this->assertInstanceOf(HostelAssignment::class, $room->assignments->first());
    }

    #[Test]
    public function it_belongs_to_many_students_through_assignments(): void
    {
        $hostel = $this->makeHostel();
        $room = HostelRoom::create(['hostel_id' => $hostel->id, 'room_number' => 'R105']);
        $student = Student::factory()->create();
        HostelAssignment::create([
            'student_id' => $student->id,
            'room_id' => $room->id,
            'check_in_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->assertCount(1, $room->students);
        $this->assertTrue($room->students->contains('id', $student->id));
    }
}
