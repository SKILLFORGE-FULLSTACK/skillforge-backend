<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isRecruiter();
    }

    public function rules(): array
    {
        return [
            'title'           => 'required|string|max:255',
            'description'     => 'required|string|min:100',
            'contract_type'   => 'required|in:full_time,part_time,freelance',
            'work_mode'       => 'required|in:remote,hybrid,onsite',
            'min_level'       => 'nullable|in:junior,mid,senior,lead',
            'required_skills' => 'nullable|array',
            'required_certs'  => 'nullable|array',
            'location'        => 'nullable|string|max:255',
            'salary_min'      => 'nullable|integer|min:0',
            'salary_max'      => 'nullable|integer|min:0|gte:salary_min',
            'currency'        => 'nullable|string|size:3',
            'expires_at'      => 'nullable|date|after:today',
        ];
    }
}
