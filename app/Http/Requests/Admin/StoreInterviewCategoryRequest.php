<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreInterviewCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'key' => 'required|string|max:100|alpha_dash|unique:interview_categories,key',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'default_difficulty' => 'required|in:easy,medium,hard,expert',
            'stack_focus' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ];
    }
}
