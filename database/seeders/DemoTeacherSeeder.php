<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\User;
use App\Models\Subject;
use App\Models\SchoolClass;
use App\Models\SalaryStructure;
use Illuminate\Database\Seeder;

class DemoTeacherSeeder extends Seeder
{
    public function run(): void
    {
        $firstNames = ['Md. Abdul', 'Fatima', 'Mohammad', 'Ayesha', 'Md. Kamal', 'Shahinur', 'Md. Rafiq', 'Nasrin', 'Md. Jashim', 'Shamima', 'Hasan', 'Parvin', 'Mizanur', 'Shahnaz', 'Jahangir', 'Laili', 'Tariqul', 'Rokeya', 'Shahidul', 'Mahbuba', 'Anwar', 'Selina', 'Kawsar', 'Jannatul', 'Riaz', 'Maksuda', 'Firoz', 'Tahmina', 'Nazmul', 'Sharmin'];
        $lastNames = ['Rahman', 'Begum', 'Hossain', 'Islam', 'Khan', 'Akter', 'Haque', 'Mollah', 'Sarker', 'Chowdhury', 'Ahmed', 'Khatun', 'Siddique', 'Jahan', 'Mahmud', 'Pervin', 'Kabir', 'Nahar', 'Bhuiyan', 'Hasan'];

        $qualifications = ['M.Ed', 'B.Ed', 'M.Sc in Mathematics', 'M.A in English', 'M.Sc in Physics', 'M.Sc in Chemistry', 'M.A in Bengali', 'M.Sc in ICT', 'B.Sc in Computer Science', 'M.Phil in Education', 'PhD in Education', 'MBA', 'MSS in Economics', 'BSS in Political Science', 'M.Sc in Biology'];

        $designations = ['Senior Teacher', 'Assistant Teacher', 'Associate Teacher', 'Head Teacher', 'Subject Coordinator', 'Department Head', 'Junior Teacher', 'Demonstrator', 'Lecturer', 'Senior Lecturer'];

        $classes = SchoolClass::all();
        $subjects = Subject::all();

        $roleId = \Spatie\Permission\Models\Role::where('name', 'teacher')->value('id');

        for ($i = 0; $i < 30; $i++) {
            $firstName = $firstNames[$i % count($firstNames)];
            $lastName = $lastNames[$i % count($lastNames)];
            $fullName = $firstName . ' ' . $lastName;
            $email = 'teacher' . ($i + 1) . '@school.com';
            $phone = '019' . str_pad((string)(10000000 + $i), 8, '0', STR_PAD_LEFT);

            $user = User::create([
                'name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'password' => bcrypt('password'),
                'gender' => $i % 2 === 0 ? 'male' : 'female',
                'address' => 'House ' . rand(1, 500) . ', Road ' . rand(1, 20) . ', Dhaka ' . rand(1200, 1400),
                'role_id' => $roleId,
            ]);
            $user->assignRole('teacher');

            $teacher = Teacher::create([
                'user_id' => $user->id,
                'employee_id' => 'TCH-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'qualification' => $qualifications[$i % count($qualifications)],
                'joining_date' => now()->subMonths(rand(6, 60))->format('Y-m-d'),
                'salary' => rand(25000, 80000),
                'phone' => $phone,
                'present_address' => $user->address,
                'status' => 'active',
            ]);

            $teacherSubjects = $subjects->random(rand(2, 4));
            foreach ($teacherSubjects as $subj) {
                $teacher->subjects()->attach($subj->id, ['class_id' => $subj->classes->first()?->id]);
                $classIds = $subj->classes->pluck('id')->toArray();
                if (!empty($classIds)) {
                    foreach ($classIds as $cid) {
                        try {
                            $teacher->classes()->syncWithoutDetaching([$cid => ['academic_session_id' => 2]]);
                        } catch (\Exception $e) {
                            // pivot may exist
                        }
                    }
                }
            }

            SalaryStructure::create([
                'teacher_id' => $teacher->id,
                'basic' => rand(20000, 50000),
                'allowances' => [
                    ['name' => 'House Rent', 'amount' => rand(5000, 15000)],
                    ['name' => 'Medical', 'amount' => rand(1000, 3000)],
                    ['name' => 'Transport', 'amount' => rand(1000, 3000)],
                ],
                'deductions' => [],
                'effective_from' => now()->startOfYear()->format('Y-m-d'),
            ]);
        }
    }
}
