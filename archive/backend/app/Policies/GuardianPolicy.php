<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Guardian;
use Illuminate\Auth\Access\HandlesAuthorization;

class GuardianPolicy
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
        return $user->hasAnyPermission(['view_guardians', 'manage_guardians']);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Guardian  $guardian
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Guardian $guardian)
    {
        // Admin and staff with permission can view any guardian
        if ($user->hasAnyPermission(['view_guardians', 'manage_guardians'])) {
            return true;
        }

        // Guardians can view their own profile
        if ($user->hasRole('guardian') && $user->guardian->id === $guardian->id) {
            return true;
        }

        // Teachers can view guardians of their students
        if ($user->hasRole('teacher')) {
            return $guardian->students()
                ->whereHas('class.teachers', function($q) use ($user) {
                    $q->where('teacher_id', $user->teacher->id);
                })
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
        return $user->hasAnyPermission(['create_guardians', 'manage_guardians']);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Guardian  $guardian
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Guardian $guardian = null)
    {
        // For route model binding, we need to handle null guardian for create/any operations
        if ($guardian === null) {
            return $user->hasAnyPermission(['update_guardians', 'manage_guardians']);
        }
        
        // Admin and staff with permission can update any guardian
        if ($user->hasAnyPermission(['update_guardians', 'manage_guardians'])) {
            return true;
        }

        // Guardians can update their own profile
        if ($user->hasRole('guardian') && $user->guardian->id === $guardian->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Guardian  $guardian
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Guardian $guardian = null)
    {
        // For route model binding, we need to handle null guardian for create/any operations
        if ($guardian === null) {
            return $user->hasAnyPermission(['delete_guardians', 'manage_guardians']);
        }
        
        // Only allow deletion if there are no students or fee payments
        if ($guardian->students()->exists() || $guardian->feePayments()->exists()) {
            return false;
        }
        
        return $user->hasAnyPermission(['delete_guardians', 'manage_guardians']);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Guardian  $guardian
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Guardian $guardian)
    {
        return $user->hasAnyPermission(['restore_guardians', 'manage_guardians']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Guardian  $guardian
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Guardian $guardian)
    {
        return $user->hasAnyPermission(['force_delete_guardians', 'manage_guardians']);
    }

    /**
     * Determine whether the user can view students of the guardian.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Guardian  $guardian
     * @return bool
     */
    public function viewStudents(User $user, Guardian $guardian)
    {
        // Admin and staff with permission can view any guardian's students
        if ($user->hasAnyPermission(['view_students', 'manage_students', 'manage_guardians'])) {
            return true;
        }

        // Guardians can view their own students
        if ($user->hasRole('guardian') && $user->guardian->id === $guardian->id) {
            return true;
        }

        // Teachers can view students of guardians in their classes
        if ($user->hasRole('teacher')) {
            return $guardian->students()
                ->whereHas('class.teachers', function($q) use ($user) {
                    $q->where('teacher_id', $user->teacher->id);
                })
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can manage guardian's students.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Guardian  $guardian
     * @return bool
     */
    public function manageStudents(User $user, Guardian $guardian = null)
    {
        // For route model binding, we need to handle null guardian for create/any operations
        if ($guardian === null) {
            return $user->hasAnyPermission(['manage_guardian_students', 'manage_guardians']);
        }
        
        return $user->hasAnyPermission(['manage_guardian_students', 'manage_guardians']);
    }

    /**
     * Determine whether the user can view guardian's payments.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Guardian  $guardian
     * @return bool
     */
    public function viewPayments(User $user, Guardian $guardian)
    {
        // Admin and staff with permission can view any guardian's payments
        if ($user->hasAnyPermission(['view_payments', 'manage_payments', 'manage_guardians'])) {
            return true;
        }

        // Guardians can view their own payments
        if ($user->hasRole('guardian') && $user->guardian->id === $guardian->id) {
            return true;
        }

        // Accountants can view payments of guardians
        if ($user->hasRole('accountant')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage guardian's payments.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Guardian  $guardian
     * @return bool
     */
    public function managePayments(User $user, Guardian $guardian = null)
    {
        // For route model binding, we need to handle null guardian for create/any operations
        if ($guardian === null) {
            return $user->hasAnyPermission(['manage_payments', 'manage_guardians']);
        }
        
        // Admin and staff with permission can manage any guardian's payments
        if ($user->hasAnyPermission(['manage_payments', 'manage_guardians'])) {
            return true;
        }

        // Accountants can manage payments of guardians
        if ($user->hasRole('accountant')) {
            return true;
        }

        return false;
    }
}
