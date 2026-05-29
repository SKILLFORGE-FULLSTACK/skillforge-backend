<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

class ContactDeveloperRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isRecruiter();
    }

    public function rules(): array
    {
        return [
            'developer_id'   => 'required|uuid|exists:users,id',
            'message'        => 'required|string|min:20|max:2000',
            'job_posting_id' => 'nullable|uuid|exists:job_postings,id',
        ];
    }

    public function failedAuthorization()
    {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'Seuls les recruteurs peuvent contacter des développeurs.'
        );
    }
}
