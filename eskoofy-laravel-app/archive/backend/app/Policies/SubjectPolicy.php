<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubjectPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->hasAnyPermission(['view_subjects', 'manage_subjects']);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Subject $subject)
    {
        // Admin and staff with permission can view any subject
        if ($user->hasAnyPermission(['view_subjects', 'manage_subjects'])) {
            return true;
        }

        // Teachers can view subjects they teach
        if ($user->hasRole('teacher')) {
            return $subject->teachers()->where('teacher_id', $user->teacher->id)->exists();
        }

        // Students can view subjects in their class
        if ($user->hasRole('student')) {
            return $subject->classes()
                ->where('class_id', $user->student->class_id)
                ->exists();
        }

        // Parents can view subjects of their children's classes
        if ($user->hasRole('parent')) {
            return $subject->classes()
                ->whereIn('class_id', $user->guardian->students()->pluck('class_id'))
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasAnyPermission(['create_subjects', 'manage_subjects']);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, ?Subject $subject = null)
    {
        // For route model binding, we need to handle null subject for create/any operations
        if ($subject === null) {
            return $user->hasAnyPermission(['update_subjects', 'manage_subjects']);
        }

        return $user->hasAnyPermission(['update_subjects', 'manage_subjects']);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, ?Subject $subject = null)
    {
        // For route model binding, we need to handle null subject for create/any operations
        if ($subject === null) {
            return $user->hasAnyPermission(['delete_subjects', 'manage_subjects']);
        }

        // Only allow deletion if there are no related records
        if ($subject->examResults()->exists() || $subject->attendances()->exists()) {
            return false;
        }

        return $user->hasAnyPermission(['delete_subjects', 'manage_subjects']);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Subject $subject)
    {
        return $user->hasAnyPermission(['restore_subjects', 'manage_subjects']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Subject $subject)
    {
        return $user->hasAnyPermission(['force_delete_subjects', 'manage_subjects']);
    }

    /**
     * Determine whether the user can manage subject classes.
     *
     * @return bool
     */
    public function manageClasses(User $user, ?Subject $subject = null)
    {
        // For route model binding, we need to handle null subject for create/any operations
        if ($subject === null) {
            return $user->hasAnyPermission(['manage_subject_classes', 'manage_subjects']);
        }

        return $user->hasAnyPermission(['manage_subject_classes', 'manage_subjects']);
    }

    /**
     * Determine whether the user can manage subject teachers.
     *
     * @return bool
     */
    public function manageTeachers(User $user, ?Subject $subject = null)
    {
        // For route model binding, we need to handle null subject for create/any operations
        if ($subject === null) {
            return $user->hasAnyPermission(['manage_subject_teachers', 'manage_subjects']);
        }

        return $user->hasAnyPermission(['manage_subject_teachers', 'manage_subjects']);
    }

    /**
     * Determine whether the user can manage subject syllabus.
     *
     * @return bool
     */
    public function manageSyllabus(User $user, ?Subject $subject = null)
    {
        // For route model binding, we need to handle null subject for create/any operations
        if ($subject === null) {
            return $user->hasAnyPermission(['manage_subject_syllabus', 'manage_subjects']);
        }

        // Admin and staff with permission can manage any subject syllabus
        if ($user->hasAnyPermission(['manage_subject_syllabus', 'manage_subjects'])) {
            return true;
        }

        // Teachers can manage syllabus for subjects they teach
        if ($user->hasRole('teacher')) {
            return $subject->teachers()->where('teacher_id', $user->teacher->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can manage subject study materials.
     *
     * @return bool
     */
    public function manageStudyMaterials(User $user, ?Subject $subject = null)
    {
        // For route model binding, we need to handle null subject for create/any operations
        if ($subject === null) {
            return $user->hasAnyPermission(['manage_study_materials', 'manage_subjects']);
        }

        // Admin and staff with permission can manage any subject study materials
        if ($user->hasAnyPermission(['manage_study_materials', 'manage_subjects'])) {
            return true;
        }

        // Teachers can manage study materials for subjects they teach
        if ($user->hasRole('teacher')) {
            return $subject->teachers()->where('teacher_id', $user->teacher->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can manage subject assignments.
     *
     * @return bool
     */
    public function manageAssignments(User $user, ?Subject $subject = null)
    {
        // For route model binding, we need to handle null subject for create/any operations
        if ($subject === null) {
            return $user->hasAnyPermission(['manage_assignments', 'manage_subjects']);
        }

        // Admin and staff with permission can manage any subject assignments
        if ($user->hasAnyPermission(['manage_assignments', 'manage_subjects'])) {
            return true;
        }

        // Teachers can manage assignments for subjects they teach
        if ($user->hasRole('teacher')) {
            return $subject->teachers()->where('teacher_id', $user->teacher->id)->exists();
        }

        return false;
    }
}
