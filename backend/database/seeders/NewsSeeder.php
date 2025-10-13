<?php

namespace Database\Seeders;

use App\Models\News;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now();
        
        // Sample news articles
        $news = [
            [
                'title' => 'New Academic Year Begins',
                'slug' => 'new-academic-year-begins',
                'content' => 'We are excited to welcome all students to the new academic year. Classes begin on October 1st.',
                'image_url' => 'https://via.placeholder.com/800x400?text=New+Academic+Year',
                'category' => 'Academics',
                'is_published' => true,
                'is_event' => false,
                'published_at' => $now->subDays(5),
                'author_name' => 'School Admin',
                'author_avatar' => 'https://via.placeholder.com/100?text=SA'
            ],
            [
                'title' => 'Annual Sports Day',
                'slug' => 'annual-sports-day',
                'content' => 'Join us for our annual sports day on November 15th. All students are encouraged to participate!',
                'image_url' => 'https://via.placeholder.com/800x400?text=Sports+Day',
                'category' => 'Events',
                'is_published' => true,
                'is_event' => true,
                'published_at' => $now->subDays(3),
                'event_date' => $now->copy()->addDays(30),
                'event_location' => 'School Ground',
                'author_name' => 'Sports Department',
                'author_avatar' => 'https://via.placeholder.com/100?text=SD'
            ],
            [
                'title' => 'Science Fair 2025',
                'slug' => 'science-fair-2025',
                'content' => 'The annual science fair will be held on December 5th. Students can register their projects until November 20th.',
                'image_url' => 'https://via.placeholder.com/800x400?text=Science+Fair',
                'category' => 'Events',
                'is_published' => true,
                'is_event' => true,
                'published_at' => $now->subDays(2),
                'event_date' => $now->copy()->addDays(45),
                'event_location' => 'School Auditorium',
                'author_name' => 'Science Department',
                'author_avatar' => 'https://via.placeholder.com/100?text=SC'
            ],
            [
                'title' => 'School Library Renovation',
                'slug' => 'school-library-renovation',
                'content' => 'The school library will be undergoing renovation from November 1st to November 15th. Alternative arrangements have been made for students.',
                'image_url' => 'https://via.placeholder.com/800x400?text=Library+Renovation',
                'category' => 'Announcements',
                'is_published' => true,
                'is_event' => false,
                'published_at' => $now->subDay(),
                'author_name' => 'School Admin',
                'author_avatar' => 'https://via.placeholder.com/100?text=SA'
            ],
            [
                'title' => 'Parent-Teacher Meeting',
                'slug' => 'parent-teacher-meeting',
                'content' => 'The next parent-teacher meeting is scheduled for November 10th. Please check the schedule for your time slot.',
                'image_url' => 'https://via.placeholder.com/800x400?text=PTM',
                'category' => 'Meetings',
                'is_published' => true,
                'is_event' => true,
                'published_at' => $now->subDay(),
                'event_date' => $now->copy()->addDays(25),
                'event_location' => 'School Campus',
                'author_name' => 'Administration',
                'author_avatar' => 'https://via.placeholder.com/100?text=ADMIN'
            ]
        ];

        foreach ($news as $item) {
            News::updateOrCreate(['slug' => $item['slug']], $item);
        }
    }
}
