<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

class SearchDeveloperRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'           => 'nullable|string|max:100',
            'level'            => 'nullable|in:junior,mid,senior,lead',
            'work_mode'        => 'nullable|in:remote,hybrid,onsite,any',
            'contract_type'    => 'nullable|in:full_time,part_time,freelance,any',
            'location_country' => 'nullable|string|max:100',
            'skills'           => 'nullable|string',
            'certifications'   => 'nullable|string',
            'salary_max'       => 'nullable|integer|min:0',
            'is_available'     => 'nullable|boolean',
            'sort'             => 'nullable|in:score,recent,streak',
            'per_page'         => 'nullable|integer|min:5|max:50',
        ];
    }
}
