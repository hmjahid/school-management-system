<?php

namespace Database\Seeders;

use App\Models\Career;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CareerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $careers = [
            [
                'title' => 'Mathematics Teacher',
                'description' => 'We are looking for an experienced Mathematics teacher to join our faculty. The ideal candidate will have a passion for teaching and a strong background in mathematics education.',
                'requirements' => "- Bachelor's degree in Mathematics or related field\n- Teaching certification\n- 3+ years of teaching experience\n- Strong communication skills",
                'type' => 'full-time',
                'location' => 'Dhaka, Bangladesh',
                'salary_min' => 50000,
                'salary_max' => 80000,
                'deadline' => Carbon::now()->addMonths(1),
                'is_published' => true,
            ],
            [
                'title' => 'Science Teacher (Physics/Chemistry)',
                'description' => 'Join our science department as a Physics/Chemistry teacher. We are looking for an enthusiastic educator who can make science engaging and accessible to all students.',
                'requirements' => "- Master's degree in Physics, Chemistry, or related field\n- Teaching certification\n- Experience with laboratory instruction\n- Strong classroom management skills",
                'type' => 'full-time',
                'location' => 'Chittagong, Bangladesh',
                'salary_min' => 45000,
                'salary_max' => 75000,
                'deadline' => Carbon::now()->addWeeks(3),
                'is_published' => true,
            ],
            [
                'title' => 'IT Support Specialist',
                'description' => 'We are seeking an IT Support Specialist to provide technical assistance to our staff and students. This role involves maintaining computer systems and networks.',
                'requirements' => "- Bachelor's degree in Computer Science or related field\n- 2+ years of IT support experience\n- Knowledge of network administration\n- Strong problem-solving skills",
                'type' => 'full-time',
                'location' => 'Dhaka, Bangladesh',
                'salary_min' => 40000,
                'salary_max' => 60000,
                'deadline' => Carbon::now()->addMonths(2),
                'is_published' => true,
            ],
            [
                'title' => 'English Language Instructor (Part-time)',
                'description' => 'Part-time position for an English Language Instructor to teach evening classes. Ideal for experienced educators looking for flexible hours.',
                'requirements' => "- Bachelor's degree in English or related field\n- TEFL/TESOL certification preferred\n- 2+ years of teaching experience\n- Excellent communication skills",
                'type' => 'part-time',
                'location' => 'Sylhet, Bangladesh',
                'salary_min' => 250,
                'salary_max' => 400,
                'deadline' => Carbon::now()->addWeeks(2),
                'is_published' => true,
            ],
            [
                'title' => 'School Counselor',
                'description' => 'We are looking for a compassionate School Counselor to support the educational and social development of our students.',
                'requirements' => "- Master's degree in School Counseling or related field\n- Relevant certification/license\n- 3+ years of counseling experience\n- Strong interpersonal skills",
                'type' => 'full-time',
                'location' => 'Rajshahi, Bangladesh',
                'salary_min' => 45000,
                'salary_max' => 70000,
                'deadline' => Carbon::now()->addMonth(),
                'is_published' => true,
            ],
        ];

        foreach ($careers as $career) {
            Career::create($career);
        }
    }
}
