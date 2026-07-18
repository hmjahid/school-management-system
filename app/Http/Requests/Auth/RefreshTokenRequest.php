<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;

class RefreshTokenRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'refresh_token' => ['required', 'string'],
        ];
    }
}
