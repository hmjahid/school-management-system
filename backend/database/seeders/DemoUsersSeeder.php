<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // School Administrator
        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $principal = User::firstOrCreate(
            ['email' => 'principal@school.com'],
            [
                'name' => 'School Principal',
                'password' => Hash::make('principal123'),
                'role_id' => $adminRole->id,
                'email_verified_at' => now(),
                'phone' => '+1234567891',
                'gender' => 'female',
                'date_of_birth' => '1985-05-15',
                'address' => 'School Office',
            ]
        );
        $principal->assignRole('admin');

        // Senior Teacher
        $teacherRole = Role::where('name', 'teacher')->firstOrFail();
        $seniorTeacher = User::firstOrCreate(
            ['email' => 'teacher.john@school.com'],
            [
                'name' => 'John Smith',
                'password' => Hash::make('teach1234'),
                'role_id' => $teacherRole->id,
                'email_verified_at' => now(),
                'phone' => '+1234567892',
                'gender' => 'male',
                'date_of_birth' => '1990-07-20',
                'address' => 'Teacher Quarters',
            ]
        );
        $seniorTeacher->assignRole('teacher');

        // Junior Teacher
        $juniorTeacher = User::firstOrCreate(
            ['email' => 'teacher.sarah@school.com'],
            [
                'name' => 'Sarah Johnson',
                'password' => Hash::make('teach5678'),
                'role_id' => $teacherRole->id,
                'email_verified_at' => now(),
                'phone' => '+1234567893',
                'gender' => 'female',
                'date_of_birth' => '1992-03-10',
                'address' => 'Teacher Quarters',
            ]
        );
        $juniorTeacher->assignRole('teacher');

        // Add more demo users as needed...

        $this->command->info('Demo users created successfully!');
    }
}
