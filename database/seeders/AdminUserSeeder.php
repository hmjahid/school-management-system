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

        // Seed a sample welcome notification (only if the user has no notifications yet)
        if ($admin->notifications()->count() === 0) {
            $admin->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'type' => 'App\\Notifications\\WelcomeNotification',
                'data' => [
                    'title' => __('Welcome to :school', ['school' => config('app.name', 'SchoolEase')]),
                    'message' => __('Your admin account is ready. Explore the dashboard, manage students, fees, and more.'),
                    'url' => route('dashboard'),
                ],
                'read_at' => null,
            ]);

            $admin->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'type' => 'App\\Notifications\\SystemNotification',
                'data' => [
                    'title' => __('Password reset is now available'),
                    'message' => __('Users can now reset their password from the login page via "Forgot password?".'),
                    'url' => \Illuminate\Support\Facades\Route::has('dashboard.settings.index') ? route('dashboard.settings.index') : route('dashboard.settings'),
                ],
                'read_at' => null,
            ]);
        }

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@school.com');
        $this->command->info('Password: password');
    }
}
