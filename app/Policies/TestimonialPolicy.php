<?php

namespace App\Policies;

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TestimonialPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasAnyPermission([
            'manage_certificates',
            'manage_exams',
        ]) || $user->hasRole('admin');
    }

    public function view(User $user, Testimonial $testimonial)
    {
        return $this->viewAny($user);
    }

    public function create(User $user)
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Testimonial $testimonial)
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Testimonial $testimonial)
    {
        return $this->viewAny($user);
    }
}
