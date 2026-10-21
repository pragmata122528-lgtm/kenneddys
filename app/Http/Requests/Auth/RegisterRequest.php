<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
            'role' => ['sometimes', 'string', new Enum(UserRole::class)],
            'shop_name' => ['nullable', 'string', 'max:150'],
            'shop_description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Please provide a username.',
            'email.required' => 'Please provide a valid email address.',
            'email.email' => 'Please enter a valid email format.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Password is required.',
            'role.enum' => 'Role must be one of: customer, vendor, admin.',
        ];
    }
}
