<?php

namespace App\Http\Requests\Certification;

use Illuminate\Foundation\Http\FormRequest;

class SubmitProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isDeveloper();
    }

    public function rules(): array
    {
        return [
            'github_repo_url' => 'required|url|max:500',
            'live_url'        => 'nullable|url|max:500',
            'notes'           => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'github_repo_url.required' => 'Le lien GitHub est obligatoire.',
            'github_repo_url.url'      => 'Le lien GitHub doit être une URL valide.',
        ];
    }
}
