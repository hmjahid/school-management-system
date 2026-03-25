<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SchoolClass;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchoolClassPolicy
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
        return $user->hasAnyPermission(['view_classes', 'manage_classes']);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SchoolClass  $class
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, SchoolClass $class)
    {
        // Admin and staff with permission can view any class
        if ($user->hasAnyPermission(['view_classes', 'manage_classes'])) {
            return true;
        }

        // Teachers can view classes they are assigned to
        if ($user->hasRole('teacher')) {
            return $user->teacher->classes()->where('class_id', $class->id)->exists();
        }

        // Students can view their own class
        if ($user->hasRole('student') && $user->student->class_id === $class->id) {
            return true;
        }

        // Parents can view their children's classes
        if ($user->hasRole('parent')) {
            return $user->guardian->students()
                ->where('class_id', $class->id)
                ->exists();
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
        return $user->hasAnyPermission(['create_classes', 'manage_classes']);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SchoolClass  $class
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, SchoolClass $class)
    {
        return $user->hasAnyPermission(['update_classes', 'manage_classes']);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SchoolClass  $class
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, SchoolClass $class = null)
    {
        // For route model binding, we need to handle null class for create/any operations
        if ($class === null) {
            return $user->hasAnyPermission(['delete_classes', 'manage_classes']);
        }
        
        // Only allow deletion if there are no students or sections
        if ($class->students()->exists() || $class->sections()->exists()) {
            return false;
        }
        
        return $user->hasAnyPermission(['delete_classes', 'manage_classes']);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SchoolClass  $class
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, SchoolClass $class)
    {
        return $user->hasAnyPermission(['restore_classes', 'manage_classes']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SchoolClass  $class
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, SchoolClass $class)
    {
        return $user->hasAnyPermission(['force_delete_classes', 'manage_classes']);
    }

    /**
     * Determine whether the user can view students in the class.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SchoolClass  $class
     * @return bool
     */
    public function viewStudents(User $user, SchoolClass $class)
    {
        // Admin and staff with permission can view any class students
        if ($user->hasAnyPermission(['view_students', 'manage_students', 'manage_classes'])) {
            return true;
        }

        // Teachers can view students in their classes
        if ($user->hasRole('teacher')) {
            return $user->teacher->classes()->where('class_id', $class->id)->exists();
        }

        // Students can view their own class students
        if ($user->hasRole('student') && $user->student->class_id === $class->id) {
            return true;
        }

        // Parents can view their children's class students
        if ($user->hasRole('parent')) {
            return $user->guardian->students()
                ->where('class_id', $class->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can manage class subjects.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SchoolClass  $class
     * @return bool
     */
    public function manageSubjects(User $user, SchoolClass $class = null)
    {
        // For route model binding, we need to handle null class for create/any operations
        if ($class === null) {
            return $user->hasAnyPermission(['manage_class_subjects', 'manage_classes']);
        }
        
        return $user->hasAnyPermission(['manage_class_subjects', 'manage_classes']);
    }

    /**
     * Determine whether the user can manage class teachers.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SchoolClass  $class
     * @return bool
     */
    public function manageTeachers(User $user, SchoolClass $class = null)
    {
        // For route model binding, we need to handle null class for create/any operations
        if ($class === null) {
            return $user->hasAnyPermission(['manage_class_teachers', 'manage_classes']);
        }
        
        return $user->hasAnyPermission(['manage_class_teachers', 'manage_classes']);
    }

    /**
     * Determine whether the user can view the class timetable.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SchoolClass  $class
     * @return bool
     */
    public function viewTimetable(User $user, SchoolClass $class)
    {
        // Admin and staff with permission can view any class timetable
        if ($user->hasAnyPermission(['view_timetables', 'manage_timetables', 'manage_classes'])) {
            return true;
        }

        // Teachers can view timetable for classes they teach
        if ($user->hasRole('teacher')) {
            return $user->teacher->classes()->where('class_id', $class->id)->exists();
        }

        // Students can view their own class timetable
        if ($user->hasRole('student') && $user->student->class_id === $class->id) {
            return true;
        }

        // Parents can view their children's class timetable
        if ($user->hasRole('parent')) {
            return $user->guardian->students()
                ->where('class_id', $class->id)
                ->exists();
        }

        return false;
    }
}
