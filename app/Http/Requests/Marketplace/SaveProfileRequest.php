<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

class SaveProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isRecruiter();
    }

    public function rules(): array
    {
        return [
            'note' => 'nullable|string|max:500',
        ];
    }
}
