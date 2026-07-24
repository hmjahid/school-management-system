<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Routine;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoutinePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasAnyPermission([
            'routine-list',
            'manage_exams',
        ]);
    }

    public function view(User $user, Routine $routine)
    {
        return $this->viewAny($user);
    }

    public function create(User $user)
    {
        return $user->hasAnyPermission([
            'routine-create',
            'manage_exams',
        ]);
    }

    public function update(User $user, Routine $routine)
    {
        return $user->hasAnyPermission([
            'routine-edit',
            'manage_exams',
        ]);
    }

    public function delete(User $user, Routine $routine)
    {
        return $user->hasAnyPermission([
            'routine-delete',
            'manage_exams',
        ]);
    }
}
