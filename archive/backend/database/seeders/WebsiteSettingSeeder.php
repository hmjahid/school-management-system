<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WebsiteSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if the website_settings table exists
        if (!\Schema::hasTable('website_settings')) {
            return;
        }

        // Check if there's already data in the table
        if (\DB::table('website_settings')->count() > 0) {
            return;
        }

        $now = now();

        // Default opening hours (Monday to Friday, 8 AM to 5 PM)
        $openingHours = [
            'monday' => ['open' => '08:00', 'close' => '17:00'],
            'tuesday' => ['open' => '08:00', 'close' => '17:00'],
            'wednesday' => ['open' => '08:00', 'close' => '17:00'],
            'thursday' => ['open' => '08:00', 'close' => '17:00'],
            'friday' => ['open' => '08:00', 'close' => '17:00'],
            'saturday' => ['open' => null, 'close' => null],
            'sunday' => ['open' => null, 'close' => null],
        ];

        \DB::table('website_settings')->insert([
            // School Information
            'school_name' => 'Example School',
            'tagline' => 'Empowering Future Leaders',
            'logo_path' => null,
            'favicon_path' => null,
            'established_year' => 1990,
            
            // Contact Information
            'address' => '123 Education Street',
            'city' => 'Learning City',
            'state' => 'Education State',
            'country' => 'United States',
            'postal_code' => '12345',
            'phone' => '+1 (555) 123-4567',
            'email' => 'info@exampleschool.edu',
            'website' => 'https://www.exampleschool.edu',
            
            // School Hours
            'opening_hours' => json_encode($openingHours),
            
            // Social Media Links
            'facebook_url' => 'https://facebook.com/exampleschool',
            'twitter_url' => 'https://twitter.com/exampleschool',
            'instagram_url' => 'https://instagram.com/exampleschool',
            'linkedin_url' => 'https://linkedin.com/school/exampleschool',
            'youtube_url' => 'https://youtube.com/exampleschool',
            
            // SEO
            'meta_title' => 'Example School - Quality Education for All',
            'meta_description' => 'Example School provides quality education with a focus on academic excellence and character development.',
            'meta_keywords' => 'school, education, learning, academy, students, teachers',
            
            // Additional Settings
            'timezone' => 'America/New_York',
            'date_format' => 'F j, Y',
            'time_format' => 'g:i A',
            
            // System Settings
            'maintenance_mode' => false,
            'maintenance_message' => 'Our website is currently under maintenance. Please check back soon!',
            
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
