<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AdmitCard;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdmitCardPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasAnyPermission([
            'manage_admit_cards',
            'manage_exams',
        ]);
    }

    public function view(User $user, AdmitCard $admitCard)
    {
        return $this->viewAny($user);
    }

    public function create(User $user)
    {
        return $user->hasAnyPermission([
            'manage_admit_cards',
            'manage_exams',
        ]);
    }

    public function update(User $user, AdmitCard $admitCard)
    {
        return $this->create($user);
    }

    public function delete(User $user, AdmitCard $admitCard)
    {
        return $this->create($user);
    }
}
