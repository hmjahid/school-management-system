<?php

namespace Tests\Unit\Models;

use App\Models\Hostel;
use App\Models\HostelAssignment;
use App\Models\HostelRoom;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HostelAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function makeRoom(): HostelRoom
    {
        $hostel = Hostel::create(['name' => 'Assign Hostel']);

        return HostelRoom::create(['hostel_id' => $hostel->id, 'room_number' => 'Z1']);
    }

    /** @test */
    public function it_persists_key_columns(): void
    {
        $room = $this->makeRoom();
        $student = Student::factory()->create();
        $checkIn = now()->subDays(5)->toDateString();
        $checkOut = now()->toDateString();

        $assignment = HostelAssignment::create([
            'student_id' => $student->id,
            'room_id' => $room->id,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'status' => 'active',
            'notes' => 'First term',
        ]);

        $this->assertDatabaseHas('hostel_assignments', [
            'id' => $assignment->id,
            'status' => 'active',
            'notes' => 'First term',
        ]);
        $this->assertSame($checkIn, $assignment->check_in_date->toDateString());
        $this->assertSame($checkOut, $assignment->check_out_date->toDateString());
    }

    /** @test */
    public function it_defaults_status_to_active(): void
    {
        $room = $this->makeRoom();
        $student = Student::factory()->create();

        $assignment = HostelAssignment::create([
            'student_id' => $student->id,
            'room_id' => $room->id,
            'check_in_date' => now()->toDateString(),
        ])->fresh();

        $this->assertEquals('active', $assignment->status);
        $this->assertNull($assignment->check_out_date);
    }

    /** @test */
    public function it_belongs_to_a_student(): void
    {
        $room = $this->makeRoom();
        $student = Student::factory()->create();

        $assignment = HostelAssignment::create([
            'student_id' => $student->id,
            'room_id' => $room->id,
            'check_in_date' => now()->toDateString(),
        ]);

        $this->assertInstanceOf(Student::class, $assignment->student);
        $this->assertEquals($student->id, $assignment->student->id);
    }

    /** @test */
    public function it_belongs_to_a_room(): void
    {
        $room = $this->makeRoom();
        $student = Student::factory()->create();

        $assignment = HostelAssignment::create([
            'student_id' => $student->id,
            'room_id' => $room->id,
            'check_in_date' => now()->toDateString(),
        ]);

        $this->assertInstanceOf(HostelRoom::class, $assignment->room);
        $this->assertEquals($room->id, $assignment->room->id);
    }
}
