<?php

namespace App\Http\Requests\Interview;

use Illuminate\Foundation\Http\FormRequest;

class StartVoiceInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isDeveloper();
    }

    public function rules(): array
    {
        return [
            'job_posting_id' => 'nullable|uuid|exists:job_postings,id',
            'language' => 'nullable|in:fr,en',
        ];
    }
}
