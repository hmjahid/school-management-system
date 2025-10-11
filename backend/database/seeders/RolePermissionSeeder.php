<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles if they don't exist
        $roles = [
            'admin' => 'Administrator',
            'teacher' => 'Teacher',
            'student' => 'Student',
            'parent' => 'Parent',
            'accountant' => 'Accountant',
            'librarian' => 'Librarian'
        ];

        foreach ($roles as $name => $description) {
            $role = Role::firstOrCreate(
                ['name' => $name],
                [
                    'guard_name' => 'web',
                    'description' => $description
                ]
            );
            
            // Store roles in variables for permission assignment
            if ($name === 'admin') $adminRole = $role;
            if ($name === 'teacher') $teacherRole = $role;
            if ($name === 'student') $studentRole = $role;
            if ($name === 'parent') $parentRole = $role;
            if ($name === 'accountant') $accountantRole = $role;
            if ($name === 'librarian') $librarianRole = $role;
        }

        // Define permissions
        $permissions = [
            // User Management
            'view_users' => 'View users',
            'create_users' => 'Create users',
            'edit_users' => 'Edit users',
            'delete_users' => 'Delete users',
            'view_roles' => 'View roles',
            'create_roles' => 'Create roles',
            'edit_roles' => 'Edit roles',
            'delete_roles' => 'Delete roles',
            'assign_roles' => 'Assign roles to users',
            'view_permissions' => 'View permissions',
            'edit_permissions' => 'Edit permissions',

            // Academic Management
            'manage_academic_years' => 'Manage academic years',
            'manage_classes' => 'Manage classes',
            'manage_sections' => 'Manage sections',
            'manage_subjects' => 'Manage subjects',
            'manage_class_routines' => 'Manage class routines',

            // Student Management
            'manage_students' => 'Manage students',
            'promote_students' => 'Promote students',
            'view_student_details' => 'View student details',
            'export_student_data' => 'Export student data',

            // Teacher Management
            'manage_teachers' => 'Manage teachers',
            'view_teacher_details' => 'View teacher details',
            'assign_subjects_to_teachers' => 'Assign subjects to teachers',

            // Attendance
            'manage_attendance' => 'Manage attendance',
            'view_attendance_reports' => 'View attendance reports',
            'export_attendance' => 'Export attendance data',

            // Examination
            'manage_exams' => 'Manage examinations',
            'manage_exam_terms' => 'Manage exam terms',
            'manage_grade_scales' => 'Manage grade scales',
            'manage_marks' => 'Manage exam marks',
            'publish_results' => 'Publish exam results',
            'view_results' => 'View exam results',
            'print_results' => 'Print exam results',

            // Finance
            'manage_fee_categories' => 'Manage fee categories',
            'manage_fee_types' => 'Manage fee types',
            'collect_fees' => 'Collect fees',
            'view_financial_reports' => 'View financial reports',
            'manage_expenses' => 'Manage expenses',
            'approve_payments' => 'Approve payments',

            // Library
            'manage_books' => 'Manage books',
            'issue_books' => 'Issue books',
            'collect_dues' => 'Collect library dues',
            'view_library_reports' => 'View library reports',

            // Hostel
            'manage_hostels' => 'Manage hostels',
            'manage_rooms' => 'Manage rooms',
            'assign_rooms' => 'Assign rooms to students',
            'manage_hostel_fees' => 'Manage hostel fees',

            // Transport
            'manage_vehicles' => 'Manage vehicles',
            'manage_routes' => 'Manage transport routes',
            'assign_vehicles' => 'Assign vehicles to routes',
            'manage_transport_fees' => 'Manage transport fees',

            // Notices
            'manage_notices' => 'Manage notices',
            'publish_notices' => 'Publish notices',

            // Settings
            'manage_school_settings' => 'Manage school settings',
            'manage_email_templates' => 'Manage email templates',
            'manage_sms_templates' => 'Manage SMS templates',
            'backup_database' => 'Backup database',
            'restore_database' => 'Restore database',
        ];

        // Create permissions
        foreach (array_keys($permissions) as $name) {
            Permission::create([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        // Assign all permissions to admin role
        $adminRole->givePermissionTo(Permission::all());

        // Assign permissions to teacher role
        $teacherPermissions = [
            'view_users',
            'view_student_details',
            'manage_attendance',
            'view_attendance_reports',
            'manage_marks',
            'view_results',
            'print_results',
            'publish_notices',
        ];
        $teacherRole->givePermissionTo($teacherPermissions);

        // Assign permissions to student role
        $studentPermissions = [
            'view_attendance_reports',
            'view_results',
            'print_results',
        ];
        $studentRole->givePermissionTo($studentPermissions);

        // Assign permissions to parent role
        $parentPermissions = [
            'view_attendance_reports',
            'view_results',
            'print_results',
        ];
        $parentRole->givePermissionTo($parentPermissions);

        // Assign permissions to accountant role
        $accountantPermissions = [
            'manage_fee_categories',
            'manage_fee_types',
            'collect_fees',
            'view_financial_reports',
            'manage_expenses',
            'approve_payments',
        ];
        $accountantRole->givePermissionTo($accountantPermissions);

        // Assign permissions to librarian role
        $librarianPermissions = [
            'manage_books',
            'issue_books',
            'collect_dues',
            'view_library_reports',
        ];
        $librarianRole->givePermissionTo($librarianPermissions);
    }
}
