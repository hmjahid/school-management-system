<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // create permissions
        $permissions = [
            'user-list', 'user-create', 'user-edit', 'user-delete',
            'role-list', 'role-create', 'role-edit', 'role-delete',
            'student-list', 'student-create', 'student-edit', 'student-delete',
            'teacher-list', 'teacher-create', 'teacher-edit', 'teacher-delete',
            'parent-list', 'parent-create', 'parent-edit', 'parent-delete',
            'class-list', 'class-create', 'class-edit', 'class-delete',
            'subject-list', 'subject-create', 'subject-edit', 'subject-delete',
            'routine-list', 'routine-create', 'routine-edit', 'routine-delete',
            'attendance-list', 'attendance-create', 'attendance-edit', 'attendance-delete',
            'exam-list', 'exam-create', 'exam-edit', 'exam-delete',
            'result-list', 'result-create', 'result-edit', 'result-delete', 'result-publish',
            'certificate-list', 'certificate-create', 'certificate-edit', 'certificate-delete', 'certificate-generate',
            'notice-list', 'notice-create', 'notice-edit', 'notice-delete',
            'fee-list', 'fee-create', 'fee-edit', 'fee-delete',
            'invoice-list', 'invoice-create', 'invoice-edit', 'invoice-delete',
            'payment-list', 'payment-create', 'payment-edit', 'payment-delete',
            'setting-list', 'setting-edit',
            'profile-view', 'profile-edit'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // create roles and assign existing permissions
        $superAdminRole = Role::create(['name' => 'Super Admin']);
        $superAdminRole->givePermissionTo(Permission::all());

        $adminRole = Role::create(['name' => 'Admin']);
        $adminRole->givePermissionTo([
            'user-list', 'user-create', 'user-edit', 'user-delete',
            'role-list', 'role-create', 'role-edit', 'role-delete',
            'student-list', 'student-create', 'student-edit', 'student-delete',
            'teacher-list', 'teacher-create', 'teacher-edit', 'teacher-delete',
            'parent-list', 'parent-create', 'parent-edit', 'parent-delete',
            'class-list', 'class-create', 'class-edit', 'class-delete',
            'subject-list', 'subject-create', 'subject-edit', 'subject-delete',
            'routine-list', 'routine-create', 'routine-edit', 'routine-delete',
            'attendance-list', 'attendance-create', 'attendance-edit',
            'exam-list', 'exam-create', 'exam-edit', 'exam-delete',
            'result-list', 'result-create', 'result-edit', 'result-publish',
            'certificate-list', 'certificate-create', 'certificate-edit', 'certificate-delete', 'certificate-generate',
            'notice-list', 'notice-create', 'notice-edit', 'notice-delete',
            'fee-list', 'fee-create', 'fee-edit', 'fee-delete',
            'invoice-list', 'invoice-create', 'invoice-edit', 'invoice-delete',
            'payment-list', 'payment-create', 'payment-edit', 'payment-delete',
            'setting-list', 'setting-edit',
            'profile-view', 'profile-edit'
        ]);

        $teacherRole = Role::create(['name' => 'Teacher']);
        $teacherRole->givePermissionTo([
            'student-list', 'attendance-list', 'attendance-create',
            'result-list', 'result-create', 'result-edit',
            'routine-list', 'notice-list', 'profile-view', 'profile-edit'
        ]);

        $studentRole = Role::create(['name' => 'Student']);
        $studentRole->givePermissionTo([
            'routine-list', 'result-list', 'attendance-list', 'notice-list', 'fee-list', 'invoice-list', 'profile-view', 'profile-edit'
        ]);

        $parentRole = Role::create(['name' => 'Parent']);
        $parentRole->givePermissionTo([
            'student-list', 'routine-list', 'result-list', 'attendance-list', 'notice-list', 'fee-list', 'invoice-list', 'profile-view'
        ]);

        $accountantRole = Role::create(['name' => 'Accountant']);
        $accountantRole->givePermissionTo([
            'fee-list', 'fee-create', 'fee-edit', 'fee-delete',
            'invoice-list', 'invoice-create', 'invoice-edit', 'invoice-delete',
            'payment-list', 'payment-create', 'payment-edit', 'payment-delete',
            'profile-view', 'profile-edit'
        ]);

        $registrarRole = Role::create(['name' => 'Registrar']);
        $registrarRole->givePermissionTo([
            'student-list', 'student-create', 'student-edit', 'student-delete',
            'parent-list', 'parent-create', 'parent-edit', 'parent-delete',
            'profile-view', 'profile-edit'
        ]);

        // create demo users
        $user = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
        ]);
        $user->assignRole($superAdminRole);
    }
}
