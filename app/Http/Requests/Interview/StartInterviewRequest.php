<?php

namespace App\Http\Requests\Interview;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isDeveloper();
    }

    public function rules(): array
    {
        return [
            // "type" doit correspondre à une catégorie active — plus figé dans
            // le code, gérable depuis l'admin (voir InterviewCategory).
            'type' => ['required', Rule::exists('interview_categories', 'key')->where('is_active', true)],
            'difficulty' => 'nullable|in:easy,medium,hard,expert',
            'stack_focus' => 'nullable|string|max:100',
            'company_target' => 'nullable|string|max:100',
            'duration_min' => 'nullable|integer|in:20,30,45,60',
            'mode' => 'nullable|in:practice,mock,company_sim',
        ];
    }
}
