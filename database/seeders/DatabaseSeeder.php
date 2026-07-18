<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            DemoUsersSeeder::class,
            WebsiteSettingSeeder::class,
            WebsiteContentSeeder::class,
            AdmissionSeeder::class,
            PaymentGatewaySeeder::class,
            NewsSeeder::class,
            GallerySeeder::class,
            CareerSeeder::class,
            EventsTableSeeder::class,
        ]);
    }
}