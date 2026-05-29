<?php

namespace App\Http\Requests\Community;

use Illuminate\Foundation\Http\FormRequest;

class SubmitChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isDeveloper();
    }

    public function rules(): array
    {
        return [
            'code_submitted' => 'required|string|max:50000',
            'language'       => 'required|string|max:50',
        ];
    }
}
