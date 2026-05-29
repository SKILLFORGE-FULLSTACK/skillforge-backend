<?php

namespace App\Http\Requests\Interview;

use Illuminate\Foundation\Http\FormRequest;

class RespondInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isDeveloper();
    }

    public function rules(): array
    {
        return [
            'question_id'    => 'required|uuid|exists:interview_questions,id',
            'text_answer'    => 'nullable|string|max:10000',
            'code_submitted' => 'nullable|string|max:50000',
            'language'       => 'nullable|string|max:50',
        ];
    }
}
