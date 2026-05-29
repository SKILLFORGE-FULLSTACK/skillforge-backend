<?php

namespace App\Http\Requests\Community;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'    => 'required|string|min:10|max:500',
            'body'     => 'required|string|min:20|max:10000',
            'category' => 'nullable|string|max:100',
            'tags'     => 'nullable|array|max:5',
            'tags.*'   => 'string|max:50',
        ];
    }
}
