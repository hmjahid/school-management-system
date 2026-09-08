<?php

namespace App\Policies;

use App\Models\StudentIdCard;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentIdCardPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasAnyPermission([
            'manage_student_id_cards',
            'manage_exams',
        ]);
    }

    public function view(User $user, StudentIdCard $studentIdCard)
    {
        return $this->viewAny($user);
    }

    public function create(User $user)
    {
        return $user->hasAnyPermission([
            'manage_student_id_cards',
            'manage_exams',
        ]);
    }

    public function update(User $user, StudentIdCard $studentIdCard)
    {
        return $this->create($user);
    }

    public function delete(User $user, StudentIdCard $studentIdCard)
    {
        return $this->create($user);
    }
}
