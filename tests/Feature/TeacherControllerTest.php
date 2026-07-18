<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Exam;
use App\Models\AcademicYear;
use App\Models\Guardian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TeacherControllerTest extends TestCase
{
    use RefreshDatabase;

    private $teacher;
    private $class;
    private $student;
    private $subject;
    private $section;
    private $exam;
    private $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a role for the teacher
        $teacherRole = \Spatie\Permission\Models\Role::create(['name' => 'teacher']);
        
        // Create a teacher user
        $this->teacher = User::factory()->create([
            'name' => 'Test Teacher',
            'email' => 'teacher@example.com',
            'password' => Hash::make('password'),
            'role_id' => $teacherRole->id
        ]);
        
        // Assign the teacher role to the user
        $this->teacher->assignRole('teacher');

        // Create an academic year first
        $academicYear = \App\Models\AcademicYear::create([
            'name' => '2023-2024',
            'session' => '2023-2024',
            'start_date' => '2023-01-01',
            'end_date' => '2023-12-31',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a subject
        $this->subject = Subject::create([
            'name' => 'Mathematics',
            'code' => 'MATH101',
            'type' => 'core',
            'short_name' => 'MATH',
            'credit_hours' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a section
        $this->section = Section::create([
            'name' => 'Section A',
            'slug' => 'section-a',
            'capacity' => 30,
            'academic_year_id' => $academicYear->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a class
        $this->class = ClassModel::create([
            'name' => 'Test Class',
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
            'academic_year' => '2023-2024',
            'schedule' => 'Mon, Wed, Fri 10:00 AM - 11:00 AM',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a role for the student
        $studentRole = Role::firstOrCreate(['name' => 'student']);
        
        // Create a student user
        $studentUser = User::create([
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'password' => Hash::make('password'),
            'role_id' => $studentRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign the student role
        $studentUser->assignRole('student');

        // First, ensure the guardian role exists
        $guardianRole = \Spatie\Permission\Models\Role::firstOrCreate(
            ['name' => 'guardian'],
            ['guard_name' => 'web', 'description' => 'Guardian Role']
        );

        // Create a guardian user
        $guardianUser = User::create([
            'name' => 'Test Guardian',
            'email' => 'guardian@example.com',
            'password' => Hash::make('password'),
            'role_id' => $guardianRole->id, // Use the role ID from the created role
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create guardian record with only the required fields
        $guardian = Guardian::create([
            'user_id' => $guardianUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create student record with all required fields from the migration
        $studentData = [
            'user_id' => $studentUser->id,
            'admission_no' => 'ADM' . rand(1000, 9999), // Required and unique
            'admission_number' => 'ADMNUM' . rand(1000, 9999), // Required and unique
            'roll_no' => 'S' . rand(100, 999), // Required
            'roll_number' => 'ROLL' . rand(1000, 9999), // Required
            'class_id' => $this->class->id, // Required foreign key
            'school_class_id' => $this->class->id, // Required foreign key
            'guardian_id' => $guardian->id, // Required foreign key
            'address' => '123 Test St, Test City',
            'documents' => json_encode([]), // Required as JSON
            'admission_date' => now()->subYear()->format('Y-m-d'),
            'date_of_birth' => now()->subYears(15)->format('Y-m-d'),
            'dob' => now()->subYears(15)->format('Y-m-d'), // Required
            'gender' => 'male',
            'blood_group' => 'A+',
            'religion' => 'Islam',
            'phone' => '01' . rand(300000000, 999999999),
            'city' => 'Test City',
            'state' => 'Test State',
            'country' => 'Bangladesh', // Default value
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        // Debug: Log the data being used to create the student
        fwrite(STDERR, "Creating student with data: " . print_r($studentData, true) . "\n");
        
        try {
            $this->student = Student::create($studentData);
            fwrite(STDERR, "Student created successfully with ID: " . $this->student->id . "\n");
        } catch (\Exception $e) {
            fwrite(STDERR, "Error creating student: " . $e->getMessage() . "\n");
            fwrite(STDERR, "SQL: " . $e->getSql() . "\n");
            fwrite(STDERR, "Bindings: " . print_r($e->getBindings(), true) . "\n");
            throw $e;
        }

        // Create an exam
        $this->exam = Exam::create([
            'name' => 'Midterm Exam',
            'description' => 'Midterm examination for the first half of the semester',
            'exam_date' => now()->addDays(30),
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'total_marks' => 100,
            'passing_marks' => 40,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a grade
        Grade::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'exam_id' => $this->exam->id,
            'marks_obtained' => 85,
            'total_marks' => 100,
            'grade' => 'A',
            'grade_point' => 4.0,
            'remarks' => 'Excellent performance',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Login and get token
        $response = $this->postJson('/api/auth/login', [
            'email' => 'teacher@example.com',
            'password' => 'password'
        ]);

        $this->token = $response->json('access_token');
    }

    /** @test */
    public function it_can_get_teacher_classes()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->get('/api/teacher/classes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'subject' => ['id', 'name'],
                        'section' => ['id', 'name'],
                        'students_count',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'meta' => [
                    'total', 'per_page', 'current_page', 'last_page', 'from', 'to'
                ]
            ]);
    }

    /** @test */
    public function it_can_get_class_students()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->get("/api/teacher/classes/{$this->class->id}/students");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'class_id',
                        'roll_number',
                        'attendance_percentage',
                        'created_at',
                        'updated_at',
                        'user' => ['id', 'name', 'email'],
                        'class' => ['id', 'name']
                    ]
                ],
                'meta' => [
                    'total', 'per_page', 'current_page', 'last_page', 'from', 'to'
                ]
            ]);
    }

    /** @test */
    public function it_can_get_class_grades()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->get("/api/teacher/classes/{$this->class->id}/grades");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'student_id',
                        'student_name',
                        'roll_number',
                        'subjects' => [
                            '*' => [
                                'id',
                                'name',
                                'marks_obtained',
                                'total_marks',
                                'grade',
                                'exam',
                                'exam_date'
                            ]
                        ],
                        'overall_percentage',
                        'overall_grade',
                        'total_marks_obtained',
                        'total_max_marks'
                    ]
                ],
                'meta' => [
                    'total', 'per_page', 'current_page', 'last_page', 'from', 'to'
                ]
            ]);
    }

    /** @test */
    public function it_validates_teacher_ownership()
    {
        // Create another teacher
        $otherTeacher = User::factory()->create([
            'name' => 'Other Teacher',
            'email' => 'other@example.com',
            'password' => Hash::make('password'),
            'role_id' => $teacherRole->id
        ]);
        $otherTeacher->assignRole('teacher');

        // Try to access the class as the other teacher
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ])->get("/api/teacher/classes/999999/students");

        $response->assertStatus(422);
    }
}
