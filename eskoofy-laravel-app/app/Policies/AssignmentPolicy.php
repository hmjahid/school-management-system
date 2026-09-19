<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssignmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasAnyPermission([
            'manage_assignments',
            'manage_exams',
        ]) || $user->hasRole('teacher');
    }

    public function view(User $user, Assignment $assignment)
    {
        return $this->viewAny($user);
    }

    public function create(User $user)
    {
        return $user->hasAnyPermission([
            'manage_assignments',
            'manage_exams',
        ]) || $user->hasRole('teacher');
    }

    public function update(User $user, Assignment $assignment)
    {
        return $this->create($user);
    }

    public function delete(User $user, Assignment $assignment)
    {
        return $this->create($user);
    }
}
