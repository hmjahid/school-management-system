<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EventsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user to be the event creator
        $admin = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['admin', 'super-admin']);
        })->first();
        
        if (!$admin) {
            $admin = User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@school.test',
                'password' => bcrypt('password')
            ]);
            $admin->assignRole('admin');
        }

        $events = [
            [
                'title' => 'Parent-Teacher Conference',
                'description' => 'Annual parent-teacher conference to discuss student progress and academic performance.',
                'start_date' => Carbon::now()->addDays(5),
                'end_date' => Carbon::now()->addDays(5)->addHours(4),
                'location' => 'School Auditorium',
                'is_virtual' => false,
                'max_attendees' => 100,
                'registration_deadline' => Carbon::now()->addDays(3),
                'status' => 'published',
            ],
            [
                'title' => 'Science Fair',
                'description' => 'Annual science fair showcasing student projects and experiments.',
                'start_date' => Carbon::now()->addDays(10),
                'end_date' => Carbon::now()->addDays(10)->addHours(6),
                'location' => 'School Gymnasium',
                'is_virtual' => false,
                'max_attendees' => 200,
                'registration_deadline' => Carbon::now()->addDays(8),
                'status' => 'published',
            ],
            [
                'title' => 'Virtual Open House',
                'description' => 'Virtual tour of the school facilities and meet the teachers.',
                'start_date' => Carbon::now()->addDays(3),
                'end_date' => Carbon::now()->addDays(3)->addHours(2),
                'meeting_url' => 'https://meet.school.test/open-house',
                'is_virtual' => true,
                'max_attendees' => 300,
                'registration_deadline' => Carbon::now()->addDay(),
                'status' => 'published',
            ],
            [
                'title' => 'Sports Day',
                'description' => 'Annual sports competition between different houses.',
                'start_date' => Carbon::now()->addDays(15),
                'end_date' => Carbon::now()->addDays(15)->addHours(8),
                'location' => 'School Sports Ground',
                'is_virtual' => false,
                'max_attendees' => 500,
                'registration_deadline' => Carbon::now()->addDays(10),
                'status' => 'published',
            ],
            [
                'title' => 'Music Concert',
                'description' => 'Annual music concert featuring performances by students.',
                'start_date' => Carbon::now()->addDays(20),
                'end_date' => Carbon::now()->addDays(20)->addHours(3),
                'location' => 'School Auditorium',
                'is_virtual' => false,
                'max_attendees' => 150,
                'registration_deadline' => Carbon::now()->addDays(18),
                'status' => 'published',
            ],
        ];

        foreach ($events as $eventData) {
            $event = new Event($eventData);
            $event->created_by = $admin->id;
            $event->save();
        }

        $this->command->info('Created ' . count($events) . ' test events');
    }
}
