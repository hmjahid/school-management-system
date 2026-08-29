<?php

namespace Tests\Unit\Models;

use App\Models\Hostel;
use App\Models\HostelAssignment;
use App\Models\HostelRoom;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HostelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_key_columns(): void
    {
        $hostel = Hostel::create([
            'name' => 'Sunrise Hostel',
            'address' => '123 Main Road',
            'description' => 'Boys hostel',
            'total_rooms' => 20,
            'warden_name' => 'Mr. Karim',
            'warden_phone' => '01711111111',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('hostels', [
            'id' => $hostel->id,
            'name' => 'Sunrise Hostel',
            'total_rooms' => 20,
            'status' => 'active',
        ]);
        $this->assertEquals('Sunrise Hostel', $hostel->name);
        $this->assertEquals(20, $hostel->total_rooms);
    }

    /** @test */
    public function it_defaults_status_to_active(): void
    {
        $hostel = Hostel::create(['name' => 'Green Hostel'])->fresh();

        $this->assertEquals('active', $hostel->status);
    }

    /** @test */
    public function it_has_many_rooms(): void
    {
        $hostel = Hostel::create(['name' => 'Lake Hostel']);
        HostelRoom::create(['hostel_id' => $hostel->id, 'room_number' => 'A1']);
        HostelRoom::create(['hostel_id' => $hostel->id, 'room_number' => 'A2']);

        $this->assertCount(2, $hostel->rooms);
        $this->assertInstanceOf(HostelRoom::class, $hostel->rooms->first());
    }

    /** @test */
    public function it_has_many_assignments_through_rooms(): void
    {
        $hostel = Hostel::create(['name' => 'Hill Hostel']);
        $room = HostelRoom::create(['hostel_id' => $hostel->id, 'room_number' => 'B1']);
        $student = Student::factory()->create();
        $assignment = HostelAssignment::create([
            'student_id' => $student->id,
            'room_id' => $room->id,
            'check_in_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->assertCount(1, $hostel->assignments);
        $this->assertTrue($hostel->assignments->contains('id', $assignment->id));
    }
}
