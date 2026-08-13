<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Domain\Auth\Enums\UserRole;
use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string|object>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::in(UserRole::registerableValues())],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
