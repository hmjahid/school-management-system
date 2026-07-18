<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdmissionSeeder extends Seeder
{
    public function run(): void
    {
        $staffRole = \Spatie\Permission\Models\Role::firstOrCreate(
            ['name' => 'staff'],
            ['guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]
        );

        $staffRole->syncPermissions([
            'view_admissions', 'create_admissions', 'edit_admissions', 'delete_admissions',
            'view_students', 'create_students', 'edit_students',
        ]);

        $admissionUser = User::firstOrCreate(
            ['email' => 'admission@example.com'],
            [
                'name' => 'Admission Officer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role_id' => $staffRole->id,
            ]
        );
        $admissionUser->assignRole('staff');

        $session = AcademicSession::firstOrCreate(
            ['name' => '2024-2025'],
            [
                'code' => 'AY2024-25',
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'is_active' => true,
                'is_current' => true,
                'status' => 'active',
                'description' => 'Academic Year 2024-2025',
            ]
        );

        SchoolClass::firstOrCreate(
            ['name' => 'Class I'],
            [
                'code' => 'C1',
                'description' => 'First Grade',
                'grade_level' => 1,
                'academic_session_id' => $session->id,
                'max_students' => 40,
                'is_active' => true,
                'monthly_fee' => 1000,
                'admission_fee' => 500,
                'exam_fee' => 200,
                'other_fees' => 100,
            ]
        );

        Batch::firstOrCreate(
            ['name' => 'Class I - A', 'academic_session_id' => $session->id],
            [
                'description' => 'First Grade - Section A',
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'capacity' => 40,
                'status' => 'active',
                'is_active' => true,
            ]
        );

        $this->command->info('Admission base data seeded.');
    }
}
