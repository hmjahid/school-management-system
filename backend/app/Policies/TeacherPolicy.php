<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Teacher;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeacherPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->hasAnyPermission(['view_teachers', 'manage_teachers']);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Teacher  $teacher
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Teacher $teacher)
    {
        // Allow if user has permission to view any teacher
        if ($user->hasPermissionTo('view_teachers') || $user->hasPermissionTo('manage_teachers')) {
            return true;
        }

        // Teachers can view their own profile
        if ($user->hasRole('teacher') && $user->id === $teacher->user_id) {
            return true;
        }

        // Allow admins and staff with permission to view any teacher
        return $user->hasRole(['admin', 'staff']) && 
               $user->hasPermissionTo('view_teachers');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasAnyPermission(['create_teachers', 'manage_teachers']);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Teacher  $teacher
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Teacher $teacher)
    {
        // Allow if user has permission to update any teacher
        if ($user->hasPermissionTo('update_teachers') || $user->hasPermissionTo('manage_teachers')) {
            return true;
        }

        // Teachers can update their own profile
        if ($user->hasRole('teacher') && $user->id === $teacher->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Teacher  $teacher
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Teacher $teacher = null)
    {
        // For route model binding, we need to handle null teacher for create/any operations
        if ($teacher === null) {
            return $user->hasAnyPermission(['delete_teachers', 'manage_teachers']);
        }
        
        // Prevent deleting self
        if ($user->id === $teacher->user_id) {
            return false;
        }
        
        return $user->hasAnyPermission(['delete_teachers', 'manage_teachers']);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Teacher  $teacher
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Teacher $teacher)
    {
        return $user->hasAnyPermission(['restore_teachers', 'manage_teachers']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Teacher  $teacher
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Teacher $teacher)
    {
        return $user->hasAnyPermission(['force_delete_teachers', 'manage_teachers']);
    }

    /**
     * Determine whether the user can view the teacher's attendance.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Teacher  $teacher
     * @return bool
     */
    public function viewAttendance(User $user, Teacher $teacher)
    {
        // Admin and staff with permission can view any teacher's attendance
        if ($user->hasAnyPermission(['view_teacher_attendance', 'manage_teacher_attendance', 'manage_teachers'])) {
            return true;
        }

        // Teachers can view their own attendance
        return $user->hasRole('teacher') && $user->id === $teacher->user_id;
    }

    /**
     * Determine whether the user can view the teacher's salary information.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Teacher  $teacher
     * @return bool
     */
    public function viewSalary(User $user, Teacher $teacher)
    {
        // Only admin and account staff can view salary information
        if ($user->hasAnyPermission(['view_teacher_salaries', 'manage_teacher_salaries', 'manage_teachers'])) {
            return true;
        }

        // Teachers can view their own salary information
        return $user->hasRole('teacher') && $user->id === $teacher->user_id;
    }

    /**
     * Determine whether the user can manage the teacher's subjects.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Teacher  $teacher
     * @return bool
     */
    public function manageSubjects(User $user, Teacher $teacher = null)
    {
        // For route model binding, we need to handle null teacher for create/any operations
        if ($teacher === null) {
            return $user->hasAnyPermission(['manage_teacher_subjects', 'manage_teachers']);
        }
        
        return $user->hasAnyPermission(['manage_teacher_subjects', 'manage_teachers']);
    }

    /**
     * Determine whether the user can manage the teacher's class assignments.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Teacher  $teacher
     * @return bool
     */
    public function manageClassAssignments(User $user, Teacher $teacher = null)
    {
        // For route model binding, we need to handle null teacher for create/any operations
        if ($teacher === null) {
            return $user->hasAnyPermission(['manage_class_assignments', 'manage_teachers']);
        }
        
        return $user->hasAnyPermission(['manage_class_assignments', 'manage_teachers']);
    }
}
