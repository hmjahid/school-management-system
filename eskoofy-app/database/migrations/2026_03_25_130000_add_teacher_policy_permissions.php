<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * TeacherPolicy expects these permission names; seeders may not have run on existing DBs.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $names = [
            'view_teachers',
            'create_teachers',
            'update_teachers',
            'delete_teachers',
            'restore_teachers',
            'force_delete_teachers',
            'view_teacher_attendance',
            'manage_teacher_attendance',
            'view_teacher_salaries',
            'manage_teacher_salaries',
            'manage_teacher_subjects',
            'manage_class_assignments',
        ];

        foreach ($names as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
            );
        }

        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo($names);
        }
    }

    public function down(): void
    {
        // Leave rows in place; removing permissions can break role assignments.
    }
};
