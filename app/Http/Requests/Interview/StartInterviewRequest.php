<?php

namespace App\Http\Requests\Interview;

use Illuminate\Foundation\Http\FormRequest;

class StartInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isDeveloper();
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:algo,system_design,behavioral,code_review,debug,tech_stack,live_coding',
            'difficulty' => 'nullable|in:easy,medium,hard,expert',
            'stack_focus' => 'nullable|string|max:100',
            'company_target' => 'nullable|string|max:100',
            'duration_min' => 'nullable|integer|in:20,30,45,60',
            'mode' => 'nullable|in:practice,mock,company_sim',
        ];
    }
}
