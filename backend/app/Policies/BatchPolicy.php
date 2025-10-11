<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Batch;
use Illuminate\Auth\Access\HandlesAuthorization;

class BatchPolicy
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
        return $user->hasAnyPermission(['view_batches', 'manage_batches']);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Batch $batch)
    {
        // Admin and staff with permission can view any batch
        if ($user->hasAnyPermission(['view_batches', 'manage_batches'])) {
            return true;
        }

        // Teachers can view batches they teach
        if ($user->hasRole('teacher')) {
            return $batch->teacher_id === $user->teacher->id || 
                   $batch->assistant_teacher_id === $user->teacher->id;
        }

        // Students can view batches they are enrolled in
        if ($user->hasRole('student')) {
            return $batch->students()->where('student_id', $user->student->id)->exists();
        }

        // Parents can view batches their children are enrolled in
        if ($user->hasRole('parent')) {
            return $batch->students()
                ->whereIn('student_id', $user->guardian->students->pluck('id'))
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
        return $user->hasAnyPermission(['create_batches', 'manage_batches']);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Batch $batch = null)
    {
        // For route model binding, we need to handle null batch for create/any operations
        if ($batch === null) {
            return $user->hasAnyPermission(['update_batches', 'manage_batches']);
        }
        
        // Admin and staff with permission can update any batch
        if ($user->hasAnyPermission(['update_batches', 'manage_batches'])) {
            return true;
        }

        // Teachers can update batches they teach
        if ($user->hasRole('teacher')) {
            return $batch->teacher_id === $user->teacher->id || 
                   $batch->assistant_teacher_id === $user->teacher->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Batch $batch = null)
    {
        // For route model binding, we need to handle null batch for create/any operations
        if ($batch === null) {
            return $user->hasAnyPermission(['delete_batches', 'manage_batches']);
        }
        
        // Only allow deletion if there are no students, payments, or attendances
        if ($batch->students()->exists() || 
            $batch->payments()->exists() || 
            $batch->attendances()->exists()) {
            return false;
        }
        
        return $user->hasAnyPermission(['delete_batches', 'manage_batches']);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Batch $batch)
    {
        return $user->hasAnyPermission(['restore_batches', 'manage_batches']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Batch $batch)
    {
        return $user->hasAnyPermission(['force_delete_batches', 'manage_batches']);
    }

    /**
     * Determine whether the user can view students in the batch.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Batch  $batch
     * @return bool
     */
    public function viewStudents(User $user, Batch $batch)
    {
        // Admin and staff with permission can view any batch's students
        if ($user->hasAnyPermission(['view_students', 'manage_students', 'manage_batches'])) {
            return true;
        }

        // Teachers can view students in batches they teach
        if ($user->hasRole('teacher') && 
            ($batch->teacher_id === $user->teacher->id || 
             $batch->assistant_teacher_id === $user->teacher->id)) {
            return true;
        }

        // Students can view their own batch's students
        if ($user->hasRole('student') && 
            $batch->students()->where('student_id', $user->student->id)->exists()) {
            return true;
        }

        // Parents can view students in their children's batches
        if ($user->hasRole('parent')) {
            return $batch->students()
                ->whereIn('student_id', $user->guardian->students->pluck('id'))
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can manage batch subjects.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Batch  $batch
     * @return bool
     */
    public function manageSubjects(User $user, Batch $batch = null)
    {
        // For route model binding, we need to handle null batch for create/any operations
        if ($batch === null) {
            return $user->hasAnyPermission(['manage_batch_subjects', 'manage_batches']);
        }
        
        // Admin and staff with permission can manage any batch's subjects
        if ($user->hasAnyPermission(['manage_batch_subjects', 'manage_batches'])) {
            return true;
        }

        // Teachers can manage subjects for batches they teach
        if ($user->hasRole('teacher') && 
            ($batch->teacher_id === $user->teacher->id || 
             $batch->assistant_teacher_id === $user->teacher->id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage batch teachers.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Batch  $batch
     * @return bool
     */
    public function manageTeachers(User $user, Batch $batch = null)
    {
        // For route model binding, we need to handle null batch for create/any operations
        if ($batch === null) {
            return $user->hasAnyPermission(['manage_batch_teachers', 'manage_batches']);
        }
        
        // Admin and staff with permission can manage any batch's teachers
        if ($user->hasAnyPermission(['manage_batch_teachers', 'manage_batches'])) {
            return true;
        }

        // Main teacher can manage assistant teacher
        if ($user->hasRole('teacher') && $batch->teacher_id === $user->teacher->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can enroll students in the batch.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Batch  $batch
     * @return bool
     */
    public function enrollStudents(User $user, Batch $batch = null)
    {
        // For route model binding, we need to handle null batch for create/any operations
        if ($batch === null) {
            return $user->hasAnyPermission(['enroll_students', 'manage_students', 'manage_batches']);
        }
        
        // Admin and staff with permission can enroll students in any batch
        if ($user->hasAnyPermission(['enroll_students', 'manage_students', 'manage_batches'])) {
            return true;
        }

        // Teachers can enroll students in batches they teach
        if ($user->hasRole('teacher') && 
            ($batch->teacher_id === $user->teacher->id || 
             $batch->assistant_teacher_id === $user->teacher->id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage batch students.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Batch  $batch
     * @return bool
     */
    public function manageStudents(User $user, Batch $batch = null)
    {
        // For route model binding, we need to handle null batch for create/any operations
        if ($batch === null) {
            return $user->hasAnyPermission(['manage_students', 'manage_batches']);
        }
        
        // Admin and staff with permission can manage any batch's students
        if ($user->hasAnyPermission(['manage_students', 'manage_batches'])) {
            return true;
        }

        // Teachers can manage students in batches they teach
        if ($user->hasRole('teacher') && 
            ($batch->teacher_id === $user->teacher->id || 
             $batch->assistant_teacher_id === $user->teacher->id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage batch attendance.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Batch  $batch
     * @return bool
     */
    public function manageAttendance(User $user, Batch $batch = null)
    {
        // For route model binding, we need to handle null batch for create/any operations
        if ($batch === null) {
            return $user->hasAnyPermission(['manage_attendance', 'manage_batches']);
        }
        
        // Admin and staff with permission can manage any batch's attendance
        if ($user->hasAnyPermission(['manage_attendance', 'manage_batches'])) {
            return true;
        }

        // Teachers can manage attendance for batches they teach
        if ($user->hasRole('teacher') && 
            ($batch->teacher_id === $user->teacher->id || 
             $batch->assistant_teacher_id === $user->teacher->id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage batch exams.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Batch  $batch
     * @return bool
     */
    public function manageExams(User $user, Batch $batch = null)
    {
        // For route model binding, we need to handle null batch for create/any operations
        if ($batch === null) {
            return $user->hasAnyPermission(['manage_exams', 'manage_batches']);
        }
        
        // Admin and staff with permission can manage any batch's exams
        if ($user->hasAnyPermission(['manage_exams', 'manage_batches'])) {
            return true;
        }

        // Teachers can manage exams for batches they teach
        if ($user->hasRole('teacher') && 
            ($batch->teacher_id === $user->teacher->id || 
             $batch->assistant_teacher_id === $user->teacher->id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage batch study materials.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Batch  $batch
     * @return bool
     */
    public function manageStudyMaterials(User $user, Batch $batch = null)
    {
        // For route model binding, we need to handle null batch for create/any operations
        if ($batch === null) {
            return $user->hasAnyPermission(['manage_study_materials', 'manage_batches']);
        }
        
        // Admin and staff with permission can manage any batch's study materials
        if ($user->hasAnyPermission(['manage_study_materials', 'manage_batches'])) {
            return true;
        }

        // Teachers can manage study materials for batches they teach
        if ($user->hasRole('teacher') && 
            ($batch->teacher_id === $user->teacher->id || 
             $batch->assistant_teacher_id === $user->teacher->id)) {
            return true;
        }

        return false;
    }
}
