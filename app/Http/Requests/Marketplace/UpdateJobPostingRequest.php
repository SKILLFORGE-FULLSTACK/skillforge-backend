<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isRecruiter();
    }

    public function rules(): array
    {
        return [
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string|min:100',
            'status'      => 'sometimes|in:active,paused,closed',
            'salary_min'  => 'nullable|integer|min:0',
            'salary_max'  => 'nullable|integer|min:0',
            'expires_at'  => 'nullable|date|after:today',
        ];
    }
}
