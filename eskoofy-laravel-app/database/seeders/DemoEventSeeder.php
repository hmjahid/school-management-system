<?php

namespace Database\Seeders;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoEventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            ['title' => 'Annual Sports Day', 'description' => 'Annual sports competition with athletics, races, and team games.', 'start_date' => Carbon::now()->addDays(15), 'location' => 'School Playground'],
            ['title' => 'Cultural Program 2025', 'description' => 'Students showcase their talents in music, dance, and drama.', 'start_date' => Carbon::now()->addDays(45), 'location' => 'School Auditorium'],
            ['title' => 'Parent-Teacher Meeting', 'description' => 'Quarterly meeting to discuss student progress and development.', 'start_date' => Carbon::now()->addDays(7), 'location' => 'School Hall'],
            ['title' => 'Science Fair', 'description' => 'Students present their science projects and innovations.', 'start_date' => Carbon::now()->addMonths(2), 'location' => 'Science Building'],
            ['title' => 'Independence Day Celebration', 'description' => 'Celebrating Bangladesh Independence Day with parade and cultural events.', 'start_date' => Carbon::createFromDate(null, 3, 26), 'location' => 'School Field'],
            ['title' => 'Victory Day Program', 'description' => 'Celebrating Victory Day with special assembly and performances.', 'start_date' => Carbon::createFromDate(null, 12, 16), 'location' => 'School Field'],
            ['title' => 'Educational Tour', 'description' => 'Annual educational tour for senior students.', 'start_date' => Carbon::now()->addMonths(3), 'location' => 'Various Locations'],
            ['title' => 'Book Fair', 'description' => 'Annual book fair at school library with discounts from publishers.', 'start_date' => Carbon::now()->addDays(60), 'location' => 'School Library'],
            ['title' => 'ICT Olympiad', 'description' => 'Inter-school ICT competition including programming and robotics.', 'start_date' => Carbon::now()->addDays(30), 'location' => 'Computer Lab'],
            ['title' => 'Annual Prize Giving Ceremony', 'description' => 'Recognizing academic and extracurricular achievements.', 'start_date' => Carbon::now()->addMonths(4), 'location' => 'School Auditorium'],
            ['title' => 'Eid Reunion', 'description' => 'Eid celebration with teachers and students.', 'start_date' => Carbon::now()->addDays(90), 'location' => 'School Campus'],
            ['title' => 'Debate Competition', 'description' => 'Inter-class debate competition on current topics.', 'start_date' => Carbon::now()->addDays(20), 'location' => 'School Auditorium'],
            ['title' => 'Art Exhibition', 'description' => 'Showcasing student artwork from all classes.', 'start_date' => Carbon::now()->addDays(75), 'location' => 'Art Gallery'],
            ['title' => 'Health & Hygiene Workshop', 'description' => 'Workshop on personal hygiene and health awareness.', 'start_date' => Carbon::now()->addDays(10), 'location' => 'School Hall'],
            ['title' => 'Summer Sports Camp', 'description' => 'Summer camp featuring coaching in various sports.', 'start_date' => Carbon::now()->addMonths(1), 'location' => 'Sports Complex'],
        ];

        foreach ($events as $e) {
            Event::create([
                'title' => $e['title'],
                'description' => $e['description'],
                'start_date' => $e['start_date'],
                'location' => $e['location'],
                'status' => 'published',
                'created_by' => 1,
            ]);
        }
    }
}
