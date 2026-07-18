<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rules;

class RegisterRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $allowedRoles = config('api.self_register_roles', ['student', 'parent']);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['sometimes', 'string', 'in:'.implode(',', $allowedRoles)],
        ];
    }

    public function roleName(): string
    {
        return $this->validated('role', 'student');
    }
}
