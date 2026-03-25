<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Student;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
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
        return $user->hasAnyPermission(['view_students', 'manage_students']);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Student $student)
    {
        // Allow if user has permission to view any student or is viewing their own profile
        if ($user->hasPermissionTo('view_students') || $user->hasPermissionTo('manage_students')) {
            return true;
        }

        // Allow students to view their own profile
        if ($user->hasRole('student') && $user->id === $student->user_id) {
            return true;
        }

        // Allow parents to view their children's profiles
        if ($user->hasRole('parent') && $student->guardian_id === $user->guardian?->id) {
            return true;
        }

        // Allow teachers to view students in their classes
        if ($user->hasRole('teacher')) {
            return $user->teacher->classes()->where('class_id', $student->class_id)->exists() ||
                   $user->teacher->subjects()->whereHas('classSections', function($q) use ($student) {
                       $q->where('class_id', $student->class_id)
                         ->where('section_id', $student->section_id);
                   })->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasAnyPermission(['create_students', 'manage_students']);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Student $student)
    {
        // Allow if user has permission to update any student
        if ($user->hasPermissionTo('update_students') || $user->hasPermissionTo('manage_students')) {
            return true;
        }

        // Allow students to update their own profile
        if ($user->hasRole('student') && $user->id === $student->user_id) {
            return true;
        }

        // Allow parents to update their children's profiles
        if ($user->hasRole('parent') && $student->guardian_id === $user->guardian?->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Student $student = null)
    {
        // For route model binding, we need to handle null student for create/any operations
        if ($student === null) {
            return $user->hasAnyPermission(['delete_students', 'manage_students']);
        }
        
        return $user->hasAnyPermission(['delete_students', 'manage_students']);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Student $student)
    {
        return $user->hasAnyPermission(['restore_students', 'manage_students']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Student $student)
    {
        return $user->hasAnyPermission(['force_delete_students', 'manage_students']);
    }

    /**
     * Determine whether the user can view student's attendance.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Student  $student
     * @return bool
     */
    public function viewAttendance(User $user, Student $student)
    {
        // Admin and staff with permission can view any student's attendance
        if ($user->hasAnyPermission(['view_attendance', 'manage_attendance', 'manage_students'])) {
            return true;
        }

        // Students can view their own attendance
        if ($user->hasRole('student') && $user->id === $student->user_id) {
            return true;
        }

        // Parents can view their children's attendance
        if ($user->hasRole('parent') && $student->guardian_id === $user->guardian?->id) {
            return true;
        }

        // Teachers can view attendance for students in their classes
        if ($user->hasRole('teacher')) {
            return $user->teacher->classes()->where('class_id', $student->class_id)->exists() ||
                   $user->teacher->subjects()->whereHas('classSections', function($q) use ($student) {
                       $q->where('class_id', $student->class_id)
                         ->where('section_id', $student->section_id);
                   })->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can view student's exam results.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Student  $student
     * @return bool
     */
    public function viewResults(User $user, Student $student)
    {
        // Admin and staff with permission can view any student's results
        if ($user->hasAnyPermission(['view_results', 'manage_results', 'manage_students'])) {
            return true;
        }

        // Students can view their own results
        if ($user->hasRole('student') && $user->id === $student->user_id) {
            return true;
        }

        // Parents can view their children's results
        if ($user->hasRole('parent') && $student->guardian_id === $user->guardian?->id) {
            return true;
        }

        // Teachers can view results for students in their classes
        if ($user->hasRole('teacher')) {
            return $user->teacher->classes()->where('class_id', $student->class_id)->exists() ||
                   $user->teacher->subjects()->whereHas('classSections', function($q) use ($student) {
                       $q->where('class_id', $student->class_id)
                         ->where('section_id', $student->section_id);
                   })->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can view student's fee information.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Student  $student
     * @return bool
     */
    public function viewFees(User $user, Student $student)
    {
        // Admin and account staff can view any student's fee information
        if ($user->hasAnyPermission(['view_fees', 'manage_fees', 'manage_students'])) {
            return true;
        }

        // Students can view their own fee information
        if ($user->hasRole('student') && $user->id === $student->user_id) {
            return true;
        }

        // Parents can view their children's fee information
        if ($user->hasRole('parent') && $student->guardian_id === $user->guardian?->id) {
            return true;
        }

        return false;
    }
}
