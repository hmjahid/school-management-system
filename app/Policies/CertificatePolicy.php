<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Certificate;
use Illuminate\Auth\Access\HandlesAuthorization;

class CertificatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasAnyPermission([
            'certificate-list',
            'manage_certificates',
            'manage_exams',
        ]);
    }

    public function view(User $user, Certificate $certificate)
    {
        return $this->viewAny($user);
    }

    public function create(User $user)
    {
        return $user->hasAnyPermission([
            'certificate-create',
            'manage_certificates',
            'manage_exams',
        ]);
    }

    public function update(User $user, Certificate $certificate)
    {
        return $user->hasAnyPermission([
            'certificate-edit',
            'manage_certificates',
            'manage_exams',
        ]);
    }

    public function delete(User $user, Certificate $certificate)
    {
        return $user->hasAnyPermission([
            'certificate-delete',
            'manage_certificates',
            'manage_exams',
        ]);
    }
}
