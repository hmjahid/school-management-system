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
            DemoUsersSeeder::class,  // Add demo users
            AdmissionSeeder::class,
            PaymentGatewaySeeder::class,
            NewsSeeder::class,
            GallerySeeder::class,
            CareerSeeder::class,
        ]);
    }
}