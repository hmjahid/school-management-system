<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\AcademicYear;
use App\Models\Batch;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class DemoAcademicSeeder extends Seeder
{
    public function run(): void
    {
        $sessions = ['2024', '2025', '2026'];
        foreach ($sessions as $s) {
            AcademicSession::firstOrCreate(
                ['code' => $s],
                ['name' => $s, 'start_date' => $s.'-01-01', 'end_date' => $s.'-12-31', 'is_active' => $s === '2026']
            );
        }

        foreach ($sessions as $s) {
            AcademicYear::firstOrCreate(
                ['session' => $s],
                ['name' => 'Academic Year '.$s, 'start_date' => $s.'-01-01', 'end_date' => $s.'-12-31', 'is_current' => $s === '2026']
            );
        }

        $classNames = ['Play', 'Nursery', 'KG-1', 'KG-2', 'Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5', 'Class 6', 'Class 7', 'Class 8', 'Class 9', 'Class 10'];
        $batchesPerClass = ['Play' => 2, 'Nursery' => 2, 'KG-1' => 2, 'KG-2' => 2, 'Class 1' => 3, 'Class 2' => 3, 'Class 3' => 3, 'Class 4' => 3, 'Class 5' => 3, 'Class 6' => 3, 'Class 7' => 3, 'Class 8' => 3, 'Class 9' => 2, 'Class 10' => 2];
        $batchNames = ['Alpha', 'Beta', 'Gamma'];
        $sectionNames = ['A', 'B'];
        $sectionIndex = 0;

        $allSubjects = ['Bangla', 'English', 'Mathematics', 'Science', 'Social Studies', 'Religion', 'ICT', 'Arts & Crafts', 'Physical Education', 'Music', 'General Knowledge', 'Bangladesh Studies'];

        $levelSubjects = [
            'Play' => ['Bangla', 'English', 'Mathematics', 'Arts & Crafts', 'Physical Education', 'General Knowledge'],
            'Nursery' => ['Bangla', 'English', 'Mathematics', 'Science', 'Arts & Crafts', 'Physical Education', 'General Knowledge'],
            'KG-1' => ['Bangla', 'English', 'Mathematics', 'Science', 'Social Studies', 'Religion', 'Arts & Crafts', 'Physical Education'],
            'KG-2' => ['Bangla', 'English', 'Mathematics', 'Science', 'Social Studies', 'Religion', 'Arts & Crafts', 'Physical Education'],
            'Class 1' => ['Bangla', 'English', 'Mathematics', 'Science', 'Social Studies', 'Religion', 'ICT', 'Arts & Crafts', 'Physical Education'],
            'Class 2' => ['Bangla', 'English', 'Mathematics', 'Science', 'Social Studies', 'Religion', 'ICT', 'Arts & Crafts', 'Physical Education'],
            'Class 3' => ['Bangla', 'English', 'Mathematics', 'Science', 'Social Studies', 'Religion', 'ICT', 'Arts & Crafts', 'Physical Education', 'Music'],
            'Class 4' => ['Bangla', 'English', 'Mathematics', 'Science', 'Social Studies', 'Religion', 'ICT', 'Arts & Crafts', 'Physical Education', 'Music'],
            'Class 5' => ['Bangla', 'English', 'Mathematics', 'Science', 'Social Studies', 'Religion', 'ICT', 'Arts & Crafts', 'Physical Education', 'Music'],
            'Class 6' => ['Bangla', 'English', 'Mathematics', 'Science', 'Social Studies', 'Religion', 'ICT', 'Physical Education', 'Bangladesh Studies'],
            'Class 7' => ['Bangla', 'English', 'Mathematics', 'Science', 'Social Studies', 'Religion', 'ICT', 'Physical Education', 'Bangladesh Studies'],
            'Class 8' => ['Bangla', 'English', 'Mathematics', 'Science', 'Social Studies', 'Religion', 'ICT', 'Physical Education', 'Bangladesh Studies'],
            'Class 9' => ['Bangla', 'English', 'Mathematics', 'Science', 'Social Studies', 'Religion', 'ICT', 'Bangladesh Studies'],
            'Class 10' => ['Bangla', 'English', 'Mathematics', 'Science', 'Social Studies', 'Religion', 'ICT', 'Bangladesh Studies'],
        ];

        foreach ($classNames as $index => $className) {
            $class = SchoolClass::create([
                'name' => $className,
                'grade_level' => $index + 1,
                'description' => "{$className} section - ".($index < 5 ? 'Primary' : ($index < 10 ? 'Junior' : 'Secondary')).' level',
            ]);

            $numBatches = $batchesPerClass[$className] ?? 2;
            for ($b = 0; $b < $numBatches; $b++) {
                $batch = Batch::create([
                    'name' => $batchNames[$b] ?? 'Batch '.($b + 1),
                ]);

                foreach ($sectionNames as $sn) {
                    $sectionIndex++;
                    Section::create([
                        'class_id' => $class->id,
                        'name' => $sn,
                        'slug' => 'section-'.$sectionIndex,
                        'academic_year_id' => 1,
                    ]);
                }
            }

            $subjects = $levelSubjects[$className] ?? $allSubjects;
            foreach ($subjects as $subjName) {
                $subject = Subject::firstOrCreate(
                    ['name' => $subjName],
                    ['code' => strtoupper(substr(str_replace(' ', '', $subjName), 0, 4)).$class->id, 'credit_hours' => rand(2, 5)]
                );
                if (! $subject->classes()->where('school_class_id', $class->id)->exists()) {
                    $subject->classes()->attach($class->id);
                }
            }
        }
    }
}
