<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AboutContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if the about_contents table exists
        if (!\Schema::hasTable('about_contents')) {
            return;
        }

        // Check if there's already data in the table
        if (\DB::table('about_contents')->count() > 0) {
            return;
        }

        $now = now();

        $coreValues = [
            ['name' => 'Excellence', 'description' => 'Striving for the highest standards in education'],
            ['name' => 'Integrity', 'description' => 'Upholding honesty and strong moral principles'],
            ['name' => 'Innovation', 'description' => 'Encouraging creative thinking and new ideas'],
            ['name' => 'Diversity', 'description' => 'Valuing and respecting differences in people']
        ];

        $socialLinks = [
            ['platform' => 'facebook', 'url' => 'https://facebook.com/exampleschool'],
            ['platform' => 'twitter', 'url' => 'https://twitter.com/exampleschool'],
            ['platform' => 'instagram', 'url' => 'https://instagram.com/exampleschool'],
            ['platform' => 'linkedin', 'url' => 'https://linkedin.com/school/exampleschool']
        ];

        $contactInfo = [
            'principal' => [
                'name' => 'Dr. Jane Smith',
                'email' => 'principal@exampleschool.edu',
                'phone' => '+1 (555) 123-4567',
                'office_hours' => 'Monday-Friday, 8:00 AM - 4:00 PM'
            ],
            'admissions' => [
                'email' => 'admissions@exampleschool.edu',
                'phone' => '+1 (555) 123-4568'
            ],
            'general' => [
                'email' => 'info@exampleschool.edu',
                'phone' => '+1 (555) 123-4569'
            ]
        ];

        \DB::table('about_contents')->insert([
            'school_name' => 'Example School',
            'tagline' => 'Empowering Future Leaders Through Quality Education',
            'logo_path' => null,
            'favicon_path' => null,
            'established_year' => 1990,
            'about_summary' => 'Example School has been providing exceptional education for over 30 years, fostering academic excellence and personal growth in a supportive learning environment.',
            'mission' => 'Our mission is to provide a transformative educational experience that empowers students to achieve their full potential and make meaningful contributions to society.',
            'vision' => 'To be a leading institution recognized for academic excellence, innovative teaching methods, and the holistic development of our students.',
            'history' => 'Founded in 1990, Example School began as a small community school with just 50 students. Over the years, we have grown into a prestigious institution serving over 1,000 students annually, while maintaining our commitment to personalized education and community values.',
            'core_values' => json_encode($coreValues),
            'contact_info' => json_encode($contactInfo),
            'social_links' => json_encode($socialLinks),
            'address' => '123 Education Street, Learning City, 12345',
            'phone' => '+1 (555) 123-4567',
            'email' => 'info@exampleschool.edu',
            'website' => 'https://www.exampleschool.edu',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
