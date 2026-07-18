<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\Guardian;
use App\Models\User;
use App\Models\Batch;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class DemoStudentSeeder extends Seeder
{
    public function run(): void
    {
        $firstNamesEn = ['Rahim', 'Karim', 'Fahim', 'Nabila', 'Tanvir', 'Sumaiya', 'Jubayer', 'Mahira', 'Sakib', 'Tasnuva', 'Arif', 'Farzana', 'Mizan', 'Samira', 'Rakib', 'Maliha', 'Shakil', 'Nasrin', 'Mamun', 'Shahana', 'Hasan', 'Runa', 'Jahid', 'Sharmin', 'Sohel', 'Parvin', 'Anwar', 'Laily', 'Shahid', 'Rokeya', 'Tariq', 'Sultana', 'Morshed', 'Jannat', 'Kabir', 'Shila', 'Nazmul', 'Mousumi', 'Firoz', 'Tahmina', 'Riaz', 'Maksuda', 'Selim', 'Rahima', 'Kawsar', 'Jesmin', 'Shahin', 'Nargis', 'Jamil', 'Rowshan'];
        $lastNamesEn = ['Hossain', 'Rahman', 'Khan', 'Islam', 'Ahmed', 'Sarker', 'Mollah', 'Chowdhury', 'Siddique', 'Haque'];

        $guardianFirstNames = ['Md. Abdul', 'Fatima', 'Mohammad', 'Ayesha', 'Md. Kamal', 'Shahinur', 'Md. Rafiq', 'Nasrin', 'Md. Jashim', 'Shamima', 'Hasan', 'Parvin', 'Mizanur', 'Shahnaz', 'Jahangir', 'Laili', 'Tariqul', 'Rokeya', 'Shahidul', 'Mahbuba'];

        $schoolClasses = SchoolClass::all();
        $batches = Batch::all();

        $studentRoleId = \Spatie\Permission\Models\Role::where('name', 'student')->value('id');
        $parentRoleId = \Spatie\Permission\Models\Role::where('name', 'parent')->value('id');

        $totalStudents = 100;

        $studentIndex = 0;
        foreach ($schoolClasses as $class) {
            $classBatches = $batches->shuffle();
            if ($classBatches->isEmpty()) continue;

            $studentsPerClass = max(1, intdiv($totalStudents, $schoolClasses->count()));

            $count = min($studentsPerClass, $totalStudents - $studentIndex);
            for ($i = 0; $i < $count; $i++) {
                $batch = $classBatches->random();
                $rollNo = $studentIndex + 1;

                $firstName = $firstNamesEn[$studentIndex % count($firstNamesEn)];
                $lastName = $lastNamesEn[$studentIndex % count($lastNamesEn)];
                $fullName = $firstName . ' ' . $lastName;
                $email = 'student' . ($studentIndex + 1) . '@school.com';
                $address = rand(1, 500) . ', ' . ['Mirpur', 'Uttara', 'Banani', 'Gulshan', 'Mohammadpur', 'Dhanmondi', 'Shyamoli', 'Bashundhara', 'Motijheel', 'Malibagh'][array_rand(['Mirpur', 'Uttara', 'Banani', 'Gulshan', 'Mohammadpur', 'Dhanmondi', 'Shyamoli', 'Bashundhara', 'Motijheel', 'Malibagh'])] . ', Dhaka';
                $dob = now()->subYears(rand(4, 17))->subMonths(rand(1, 11))->format('Y-m-d');
                $phone = '017' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);

                $user = User::create([
                    'name' => $fullName,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => bcrypt('password'),
                    'gender' => $studentIndex % 2 === 0 ? 'male' : 'female',
                    'address' => $address,
                    'date_of_birth' => $dob,
                    'role_id' => $studentRoleId,
                ]);
                $user->assignRole('student');

                $admissionNumber = 'STU-' . str_pad($studentIndex + 1, 5, '0', STR_PAD_LEFT);

                $student = Student::create([
                    'user_id' => $user->id,
                    'class_id' => $class->id,
                    'batch_id' => $batch->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'admission_number' => $admissionNumber,
                    'roll_number' => (string) $rollNo,
                    'admission_date' => now()->subMonths(rand(1, 24))->format('Y-m-d'),
                    'blood_group' => ['A+', 'B+', 'AB+', 'O+', 'A-', 'B-', 'AB-', 'O-'][rand(0, 7)],
                    'present_address' => $address,
                    'status' => 'active',
                    'gender' => $studentIndex % 2 === 0 ? 'male' : 'female',
                    'date_of_birth' => $dob,
                ]);

                $numGuardians = rand(1, 2);
                for ($g = 0; $g < $numGuardians; $g++) {
                    $gName = $guardianFirstNames[array_rand($guardianFirstNames)] . ' ' . $lastName;
                    $gEmail = 'parent' . (($studentIndex * 2) + $g + 1) . '@school.com';
                    $gPhone = '017' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);

                    $guardianUser = User::firstOrCreate(
                        ['email' => $gEmail],
                        [
                            'name' => $gName,
                            'phone' => $gPhone,
                            'password' => bcrypt('password'),
                            'gender' => $g === 0 ? 'male' : 'female',
                            'address' => $address,
                            'role_id' => $parentRoleId,
                        ]
                    );
                    if (!$guardianUser->hasRole('parent')) {
                        $guardianUser->assignRole('parent');
                    }

                    $guardian = Guardian::firstOrCreate(
                        ['user_id' => $guardianUser->id],
                        [
                            'phone' => $gPhone,
                            'occupation' => ['Service', 'Business', 'Doctor', 'Engineer', 'Teacher', 'Lawyer', 'Banker'][rand(0, 6)],
                            'relationship' => $g === 0 ? 'father' : 'mother',
                        ]
                    );

                    $guardian->students()->syncWithoutDetaching([$student->id]);
                }

                $studentIndex++;
            }
        }
    }
}
