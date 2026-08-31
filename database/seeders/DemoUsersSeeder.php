<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    private const DEMO_ACCOUNTS = [
        ['admin', 'principal@school.com', 'School Principal', 'principal123', 'female', '+1234567891', '1985-05-15'],
        ['teacher', 'teacher.john@school.com', 'John Smith', 'teach1234', 'male', '+1234567892', '1990-07-20'],
        ['teacher', 'teacher.sarah@school.com', 'Sarah Johnson', 'teach5678', 'female', '+1234567893', '1992-03-10'],
        ['accountant', 'accountant@school.com', 'Demo Accountant', 'accountant123', 'male', '+1234567894', '1988-11-02'],
        ['librarian', 'librarian@school.com', 'Demo Librarian', 'librarian123', 'female', '+1234567895', '1991-09-18'],
    ];

    public function run(): void
    {
        if (app()->isProduction() && ! env('ALLOW_DEMO_DATA', false)) {
            $this->command?->warn('Demo users skipped in production. Set ALLOW_DEMO_DATA=true to seed demo accounts.');

            return;
        }

        foreach (self::DEMO_ACCOUNTS as [$roleName, $email, $name, $password, $gender, $phone, $dob]) {
            $role = Role::where('name', $roleName)->firstOrFail();

            $user = User::where('email', $email)->first();
            if (! $user) {
                $user = User::createWithCredential([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'phone' => $phone,
                    'gender' => $gender,
                    'date_of_birth' => $dob,
                    'address' => 'School Campus',
                    'role_id' => $role->id,
                ]);
            }

            if (! $user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }
        }

        $this->printCredentials();
    }

    private function printCredentials(): void
    {
        if (! $this->command) {
            return;
        }

        $rows = [['admin', 'admin@school.com', 'ADMIN_PASSWORD env (default: password)']];

        foreach (self::DEMO_ACCOUNTS as [$roleName, $email, , $password]) {
            $rows[] = [$roleName, $email, $password];
        }

        $rows[] = ['teacher', 'teacher1@school.com … teacher30@school.com', 'password'];
        $rows[] = ['student', 'student1@school.com … student5@school.com', 'password'];
        $rows[] = ['parent', 'parent1@school.com … parent10@school.com', 'password'];

        $this->command->table(['Role', 'Email', 'Password'], $rows);
        $this->command->info('Demo users created successfully!');
    }
}
