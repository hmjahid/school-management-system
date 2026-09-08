<?php

namespace Database\Seeders;

use App\Models\Gallery;
use Illuminate\Database\Seeder;

class DemoGallerySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['title' => 'Annual Sports Day 2025', 'description' => 'Highlights from our annual sports competition', 'category' => 'sports'],
            ['title' => 'Cultural Program', 'description' => 'Students performing at the cultural event', 'category' => 'cultural'],
            ['title' => 'Science Fair Exhibition', 'description' => 'Innovative projects by our young scientists', 'category' => 'academic'],
            ['title' => 'Independence Day Celebration', 'description' => 'Patriotic programs and parade', 'category' => 'cultural'],
            ['title' => 'Classroom Activities', 'description' => 'Daily learning moments captured', 'category' => 'academic'],
            ['title' => 'Field Trip 2025', 'description' => 'Educational tour to the National Museum', 'category' => 'academic'],
            ['title' => 'Graduation Ceremony', 'description' => 'Class of 2025 graduation day', 'category' => 'cultural'],
            ['title' => 'Art & Craft Exhibition', 'description' => 'Creative works by our talented students', 'category' => 'cultural'],
        ];

        foreach ($items as $item) {
            Gallery::create([
                'title' => $item['title'],
                'description' => $item['description'],
                'category' => $item['category'],
                'image_path' => 'https://picsum.photos/seed/'.str_replace(' ', '-', $item['title']).'/800/600',
                'is_published' => true,
            ]);
        }
    }
}
