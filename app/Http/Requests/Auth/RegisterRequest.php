<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
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
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => ['required', 'confirmed', Password::min(8)],
            'role'         => 'required|in:developer,recruiter',
            'company_name' => 'required_if:role,recruiter|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'              => 'Cet email est déjà utilisé.',
            'password.confirmed'        => 'Les mots de passe ne correspondent pas.',
            'company_name.required_if' => 'Le nom de l\'entreprise est requis pour les recruteurs.',
        ];
    }
}
