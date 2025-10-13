<?php

namespace Database\Seeders;

use App\Models\Gallery;
use Illuminate\Database\Seeder;

class GallerySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $galleries = [
            [
                'title' => 'Annual Sports Day 2025',
                'description' => 'Students participating in various sports activities',
                'image_path' => 'https://source.unsplash.com/random/800x600/?sports',
                'category' => 'Sports',
                'is_published' => true
            ],
            [
                'title' => 'Science Fair Exhibition',
                'description' => 'Students showcasing their science projects',
                'image_path' => 'https://source.unsplash.com/random/800x600/?science',
                'category' => 'Academics',
                'is_published' => true
            ],
            [
                'title' => 'Cultural Fest 2025',
                'description' => 'Annual cultural festival celebrations',
                'image_path' => 'https://source.unsplash.com/random/800x600/?festival',
                'category' => 'Events',
                'is_published' => true
            ],
            [
                'title' => 'Field Trip to Museum',
                'description' => 'Educational trip to the national museum',
                'image_path' => 'https://source.unsplash.com/random/800x600/?museum',
                'category' => 'Field Trips',
                'is_published' => true
            ],
            [
                'title' => 'Art Competition',
                'description' => 'Annual inter-school art competition',
                'image_path' => 'https://source.unsplash.com/random/800x600/?art',
                'category' => 'Arts',
                'is_published' => true
            ],
            [
                'title' => 'Graduation Ceremony',
                'description' => 'Class of 2025 graduation ceremony',
                'image_path' => 'https://source.unsplash.com/random/800x600/?graduation',
                'category' => 'Events',
                'is_published' => true
            ]
        ];

        foreach ($galleries as $gallery) {
            Gallery::create($gallery);
        }
    }
}
