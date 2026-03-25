<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $adminRole = Role::where('name', 'admin')->firstOrFail();
        
        $admin = User::firstOrCreate(
            ['email' => 'admin@school.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'email_verified_at' => now(),
                'phone' => '+1234567890',
                'gender' => 'male',
                'date_of_birth' => '1990-01-01',
                'address' => 'School Address',
            ]
        );

        // Assign admin role to the user
        $admin->assignRole('admin');

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@school.com');
        $this->command->info('Password: password');
    }
}
