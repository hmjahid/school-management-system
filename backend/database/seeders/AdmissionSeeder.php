<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Admission;
use App\Models\AcademicSession;
use App\Models\Batch;
use App\Models\AdmissionDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdmissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create test user if not exists
        // Create or get the staff role
        $staffRole = \Spatie\Permission\Models\Role::firstOrCreate(
            ['name' => 'staff'],
            [
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
        
        // Ensure the role has the necessary permissions
        $permissions = [
            'view_admissions',
            'create_admissions',
            'edit_admissions',
            'delete_admissions',
            'view_students',
            'create_students',
            'edit_students'
        ];
        
        foreach ($permissions as $permission) {
            $staffRole->givePermissionTo($permission);
        }
        
        $user = User::firstOrCreate(
            ['email' => 'admission@example.com'],
            [
                'name' => 'Admission Officer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role_id' => $staffRole->id,
            ]
        );

        // Assign role if not assigned
        if (!$user->hasRole('staff')) {
            $user->assignRole('staff');
        }

        // Get or create academic session
        $academicSession = AcademicSession::firstOrCreate(
            ['name' => '2024-2025'],
            [
                'code' => 'AY2024-25',
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'is_active' => true,
                'is_current' => true,
                'status' => 'active',
                'description' => 'Academic Year 2024-2025'
            ]
        );

        // Get or create a school class
        $schoolClass = \App\Models\SchoolClass::firstOrCreate(
            ['name' => 'Class I'],
            [
                'code' => 'C1',
                'description' => 'First Grade',
                'grade_level' => 1,
                'academic_session_id' => $academicSession->id,
                'max_students' => 40,
                'is_active' => true,
                'monthly_fee' => 1000.00,
                'admission_fee' => 500.00,
                'exam_fee' => 200.00,
                'other_fees' => 100.00,
            ]
        );

        // Get or create batch
        $batch = Batch::firstOrCreate(
            [
                'name' => 'Class I - A', 
                'academic_session_id' => $academicSession->id,
                'school_class_id' => $schoolClass->id
            ],
            [
                'description' => 'First Grade - Section A',
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'capacity' => 40,
                'status' => 'active',
            ]
        );

        // Create sample admissions with different statuses
        $statuses = [
            'draft',
            'submitted',
            'under_review',
            'approved',
            'rejected',
            'waitlisted',
            'enrolled',
        ];

        foreach ($statuses as $status) {
            $admission = Admission::create([
                'application_number' => 'APP' . date('Y') . str_pad(rand(1, 999), 5, '0', STR_PAD_LEFT),
                'academic_session_id' => $academicSession->id,
                'batch_id' => $batch->id,
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'gender' => fake()->randomElement(['male', 'female']),
                'date_of_birth' => fake()->dateTimeBetween('-10 years', '-3 years')->format('Y-m-d'),
                'blood_group' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', null]),
                'religion' => fake()->randomElement(['Islam', 'Hinduism', 'Christianity', 'Buddhism']),
                'nationality' => 'Bangladeshi',
                'email' => 'admission_' . Str::random(8) . '@example.com',
                'phone' => '01' . rand(3, 9) . rand(10000000, 99999999),
                'address' => fake()->address(),
                'city' => fake()->city(),
                'state' => fake()->state(),
                'country' => 'Bangladesh',
                'postal_code' => fake()->postcode(),
                'father_name' => fake()->name('male'),
                'father_phone' => '01' . rand(3, 9) . rand(10000000, 99999999),
                'father_occupation' => fake()->jobTitle(),
                'mother_name' => fake()->name('female'),
                'mother_phone' => '01' . rand(3, 9) . rand(10000000, 99999999),
                'mother_occupation' => fake()->jobTitle(),
                'previous_school' => fake()->company() . ' School',
                'previous_class' => 'Nursery',
                'previous_grade' => 'A',
                'status' => $status,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Create sample documents for each admission
            $this->createDocuments($admission, $user->id);

            // Update timestamps based on status
            $this->updateStatusTimestamps($admission, $status);
        }

        $this->command->info('Admission test data seeded successfully!');
    }

    /**
     * Create sample documents for an admission.
     *
     * @param  \App\Models\Admission  $admission
     * @param  int  $userId
     * @return void
     */
    protected function createDocuments($admission, $userId)
    {
        $documentTypes = [
            'birth_certificate' => 'Birth Certificate',
            'transfer_certificate' => 'Transfer Certificate',
            'photo' => 'Student Photo',
            'mark_sheet' => 'Previous Mark Sheet',
        ];

        foreach ($documentTypes as $type => $name) {
            AdmissionDocument::create([
                'admission_id' => $admission->id,
                'type' => $type,
                'name' => $name,
                'file_path' => "admissions/{$admission->id}/{$type}.pdf",
                'file_type' => 'application/pdf',
                'file_size' => rand(100000, 5000000), // 100KB to 5MB
                'is_approved' => $admission->status !== 'draft' ? fake()->boolean(80) : false,
                'review_notes' => $admission->status !== 'draft' ? fake()->sentence() : null,
                'reviewed_by' => $admission->status !== 'draft' ? $userId : null,
                'reviewed_at' => $admission->status !== 'draft' ? now() : null,
            ]);
        }
    }

    /**
     * Update status timestamps based on admission status.
     *
     * @param  \App\Models\Admission  $admission
     * @param  string  $status
     * @return void
     */
    protected function updateStatusTimestamps($admission, $status)
    {
        $updates = [];
        $now = now();

        switch ($status) {
            case 'submitted':
                $updates['submitted_at'] = $now->copy()->subDays(rand(1, 30));
                break;
                
            case 'under_review':
                $updates['submitted_at'] = $now->copy()->subDays(rand(5, 10));
                $updates['status'] = 'under_review';
                break;
                
            case 'approved':
                $updates['submitted_at'] = $now->copy()->subDays(rand(10, 15));
                $updates['approved_at'] = $now->copy()->subDays(rand(1, 5));
                $updates['approved_by'] = $admission->created_by;
                break;
                
            case 'rejected':
                $updates['submitted_at'] = $now->copy()->subDays(rand(10, 15));
                $updates['rejected_at'] = $now->copy()->subDays(rand(1, 5));
                $updates['rejection_reason'] = fake()->sentence();
                $updates['rejected_by'] = $admission->created_by;
                break;
                
            case 'waitlisted':
                $updates['submitted_at'] = $now->copy()->subDays(rand(10, 20));
                $updates['status'] = 'waitlisted';
                break;
                
            case 'enrolled':
                $updates['submitted_at'] = $now->copy()->subDays(rand(15, 30));
                $updates['approved_at'] = $now->copy()->subDays(rand(10, 14));
                $updates['enrolled_at'] = $now->copy()->subDays(rand(1, 5));
                $updates['approved_by'] = $admission->created_by;
                
                // Create a user for the student
                $user = User::create([
                    'name' => $admission->first_name . ' ' . $admission->last_name,
                    'email' => $admission->email,
                    'password' => bcrypt('password'), // Default password
                    'role_id' => 4, // Assuming 4 is the role_id for students
                    'email_verified_at' => now(),
                ]);
                
                // Assign the student role to the user
                $user->assignRole('student');
                
                // Get a random school class ID
                $schoolClass = \App\Models\SchoolClass::inRandomOrder()->first();
                
                if (!$schoolClass) {
                    // If no school class exists, create one
                    $schoolClass = \App\Models\SchoolClass::create([
                        'name' => 'Class ' . rand(1, 12) . chr(64 + rand(1, 3)), // Random class like "5A", "10B", etc.
                        'code' => 'CLS' . rand(100, 999),
                        'description' => 'Sample class for testing',
                        'grade_level' => rand(1, 12),
                        'is_active' => true,
                    ]);
                }
                
                // Get or create guardian role
                $guardianRole = \Spatie\Permission\Models\Role::firstOrCreate(
                    ['name' => 'guardian'],
                    ['guard_name' => 'web']
                );
                
                // Create a guardian user
                $guardianUser = \App\Models\User::create([
                    'name' => $admission->father_name,
                    'email' => 'guardian_' . $admission->email,
                    'password' => bcrypt('password'),
                    'role_id' => $guardianRole->id,
                    'email_verified_at' => now(),
                ]);
                
                // Create guardian record
                $guardian = \App\Models\Guardian::create([
                    'user_id' => $guardianUser->id,
                    'relation_type' => 'father',
                    'occupation' => $admission->father_occupation,
                    'company_name' => 'N/A',
                    'office_phone' => $admission->phone,
                    'emergency_contact_name' => $admission->father_name,
                    'emergency_contact_phone' => $admission->phone,
                    'emergency_contact_relation' => 'Father',
                    'is_primary' => true,
                ]);
                
                // Assign the guardian role to the user
                $guardianUser->assignRole('guardian');
                
                // Create a student record for enrolled admission
                $admission->student()->create([
                    'user_id' => $user->id,
                    'admission_id' => $admission->id,
                    'guardian_id' => $guardian->id,
                    'admission_no' => 'ADM' . $admission->id,
                    'school_class_id' => $schoolClass->id,
                    'roll_no' => 'STD' . rand(1000, 9999),
                    'admission_date' => now()->subDays(rand(1, 5)),
                    'first_name' => $admission->first_name,
                    'last_name' => $admission->last_name,
                    'gender' => $admission->gender,
                    'dob' => $admission->date_of_birth,
                    'blood_group' => $admission->blood_group,
                    'religion' => $admission->religion,
                    'nationality' => $admission->nationality,
                    'email' => $admission->email,
                    'phone' => $admission->phone,
                    'address' => $admission->address,
                    'city' => $admission->city,
                    'state' => $admission->state,
                    'country' => $admission->country,
                    'postal_code' => $admission->postal_code,
                    'father_name' => $admission->father_name,
                    'father_phone' => $admission->father_phone,
                    'father_occupation' => $admission->father_occupation,
                    'mother_name' => $admission->mother_name,
                    'mother_phone' => $admission->mother_phone,
                    'mother_occupation' => $admission->mother_occupation,
                    'status' => 'active',
                ]);
                break;
        }

        if (!empty($updates)) {
            $admission->update($updates);
        }
    }
}
