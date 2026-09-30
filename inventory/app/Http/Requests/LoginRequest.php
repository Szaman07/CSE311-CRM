<?php

namespace App\Http\Requests;

class LoginRequest extends DomainRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email', 'max:150'], 'password' => ['required', 'string', 'max:4096']];
    }
}
